<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\ScrapeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $activeUrls = ScrapeUrl::where('is_active', 1)->count();
        $scrapedToday = ScrapeUrl::whereDate('last_scraped_at', $today)->count();
        $propertiesToday = Property::whereDate('scrape_date', $today)->count();
        $propertiesYesterday = Property::whereDate('scrape_date', $today->copy()->subDay())->count();
        $totalTracked = Property::count();

        $lastScrape = ScrapeUrl::orderByDesc('last_scraped_at')->first();

        // 30-day activity counts for the chart
        $activity = Property::selectRaw('scrape_date, COUNT(*) as c')
            ->whereBetween('scrape_date', [$today->copy()->subDays(29), $today])
            ->groupBy('scrape_date')
            ->orderBy('scrape_date')
            ->pluck('c', 'scrape_date')
            ->toArray();

        $activityData = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i)->toDateString();
            $activityData[] = ['date' => $d, 'count' => (int)($activity[$d] ?? 0)];
        }

        $recentScrapes = ScrapeUrl::orderByDesc('last_scraped_at')
            ->whereNotNull('last_scraped_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'activeUrls', 'scrapedToday', 'propertiesToday', 'propertiesYesterday',
            'totalTracked', 'lastScrape', 'activityData', 'recentScrapes'
        ));
    }
}
