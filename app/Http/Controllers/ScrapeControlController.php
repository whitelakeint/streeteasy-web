<?php

namespace App\Http\Controllers;

use App\Models\ScrapeLog;
use App\Models\ScrapeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ScrapeControlController extends Controller
{
    // Python backend HTTP control API (set in .env)
    protected function base(): string
    {
        return rtrim(config('services.scraper.control_url', 'http://127.0.0.1:8766'), '/');
    }

    public function index()
    {
        $activeUrls = ScrapeUrl::where('is_active', 1)->orderBy('id')->get();

        $status = ['extension' => 'unknown', 'scraper' => 'unknown'];
        $online = false;
        try {
            $resp = Http::timeout(3)->get($this->base() . '/status');
            if ($resp->ok()) {
                $status = array_merge($status, $resp->json() ?? []);
                $online = true;
            }
        } catch (Throwable $e) {
            // backend offline — show warning in UI
        }
        $status['active_urls'] = $activeUrls->count();

        $lastScrape = ScrapeUrl::orderByDesc('last_scraped_at')->first();

        // Recent logs (filtered by ?level= if provided)
        $logsQuery = ScrapeLog::with('scrapeUrl')->orderByDesc('created_at');
        if ($level = request('level')) {
            $logsQuery->where('level', $level);
        }
        $logs = $logsQuery->limit(100)->get();

        $logCounts = [
            'info'  => ScrapeLog::where('level', 'info')->count(),
            'warn'  => ScrapeLog::where('level', 'warn')->count(),
            'error' => ScrapeLog::where('level', 'error')->count(),
        ];

        return view('scrape.index', compact('activeUrls','status','online','lastScrape','logs','logCounts'));
    }

    /**
     * GET /scrape-control/logs.json — AJAX polling endpoint.
     * Returns the latest log entries + live counts for in-place refresh.
     */
    public function logsJson(Request $request)
    {
        $q = ScrapeLog::with('scrapeUrl:id,name')->orderByDesc('created_at');
        if ($level = $request->query('level')) {
            $q->where('level', $level);
        }
        $logs = $q->limit(100)->get()->map(function ($log) {
            return [
                'id'            => $log->id,
                'created_at'    => $log->created_at->format('M j H:i:s'),
                'level'         => $log->level,
                'event'         => $log->event,
                'building_name' => $log->scrapeUrl?->name,
                'message'       => $log->message,
            ];
        });

        return response()->json([
            'logs'   => $logs,
            'counts' => [
                'info'  => ScrapeLog::where('level', 'info')->count(),
                'warn'  => ScrapeLog::where('level', 'warn')->count(),
                'error' => ScrapeLog::where('level', 'error')->count(),
            ],
            'scraper_state' => [
                'running' => false, // overridden live by the front-end's /status probe if needed
            ],
        ]);
    }

    public function run(Request $request)
    {
        try {
            // Longer timeout — Python's event loop can briefly block during
            // synchronous outbound API calls while a scrape is already running.
            $resp = Http::connectTimeout(5)->timeout(30)->post($this->base() . '/scrape');
            $msg = $resp->json('message', 'Scrape triggered');
            return back()->with('status', $msg);
        } catch (Throwable $e) {
            return back()->with('status', 'Could not reach backend: ' . $e->getMessage());
        }
    }
}
