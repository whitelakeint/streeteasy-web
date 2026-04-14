<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScrapeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScrapeLogController extends Controller
{
    /**
     * POST /api/scraper/logs
     * Body: { url_id?, property_name?, level, event, message, context? }
     * Accepts a single log entry OR an array under "entries".
     */
    public function store(Request $request)
    {
        $entries = $request->input('entries');
        $payloads = is_array($entries) && count($entries) > 0 ? $entries : [$request->all()];

        $validLevels = ['info', 'warn', 'error'];
        $inserted = 0;

        foreach ($payloads as $p) {
            $level = in_array(($p['level'] ?? 'info'), $validLevels) ? $p['level'] : 'info';
            ScrapeLog::create([
                'url_id'        => $p['url_id'] ?? null,
                'property_name' => $p['property_name'] ?? null,
                'level'         => $level,
                'event'         => substr($p['event'] ?? 'event', 0, 64),
                'message'       => (string) ($p['message'] ?? ''),
                'context'       => $p['context'] ?? null,
                'created_at'    => Carbon::now(),
            ]);
            $inserted++;
        }

        // Retention: prune anything older than 10 days on every ingest (cheap + self-maintaining)
        ScrapeLog::where('created_at', '<', Carbon::now()->subDays(10))->delete();

        return response()->json(['ok' => true, 'inserted' => $inserted]);
    }
}
