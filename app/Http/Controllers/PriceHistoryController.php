<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\ScrapeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PriceHistoryController extends Controller
{
    public function show(Request $request, Property $property)
    {
        $allBuildings = ScrapeUrl::orderBy('name')->get(['id', 'name']);

        // For each building, get distinct units with the ID of their latest record
        $rows = Property::select('url_id', 'property_name', DB::raw('MAX(id) as latest_id'))
            ->groupBy('url_id', 'property_name')
            ->orderBy('property_name')
            ->get();

        $unitsByBuilding = [];
        foreach ($rows as $r) {
            $unitsByBuilding[$r->url_id][] = [
                'id'   => $r->latest_id,
                'name' => $r->property_name,
            ];
        }

        // Determine date range (default: 7 days)
        $range = $request->query('range', '7');
        $toDate = Carbon::today();
        $fromDate = match ($range) {
            '30'     => $toDate->copy()->subDays(29),
            '90'     => $toDate->copy()->subDays(89),
            'custom' => $request->filled('from') ? Carbon::parse($request->query('from')) : $toDate->copy()->subDays(6),
            default  => $toDate->copy()->subDays(6), // '7'
        };
        if ($range === 'custom' && $request->filled('to')) {
            $toDate = Carbon::parse($request->query('to'));
        }

        $history = Property::where('url_id', $property->url_id)
            ->where('property_name', $property->property_name)
            ->whereBetween('scrape_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderBy('scrape_date')
            ->get(['scrape_date','rent','original']);

        $rents = $history->pluck('rent')->filter()->values();
        $min = $rents->min();
        $max = $rents->max();
        $current = $property->rent;

        // Period change: first vs last rent within the selected range
        $firstRent = $rents->first();
        $lastRent  = $rents->last();
        $change = ($firstRent && $lastRent)
            ? round((($lastRent - $firstRent) / $firstRent) * 100, 1)
            : 0;

        $firstSeen   = $history->first()?->scrape_date;
        $lastUpdated = $history->last()?->scrape_date;

        $rangeLabel = match ($range) {
            '30'     => 'Last 30 days',
            '90'     => 'Last 90 days',
            'custom' => $fromDate->format('m-d-Y') . ' – ' . $toDate->format('m-d-Y'),
            default  => 'Last 7 days',
        };

        return view('price-history.show', compact(
            'property','history','min','max','current','change','firstSeen','lastUpdated',
            'allBuildings','unitsByBuilding','range','fromDate','toDate','rangeLabel'
        ));
    }

    public function exportCsv(Request $request, Property $property)
    {
        $range = $request->query('range', '7');
        $toDate = Carbon::today();
        $fromDate = match ($range) {
            '30'     => $toDate->copy()->subDays(29),
            '90'     => $toDate->copy()->subDays(89),
            'custom' => $request->filled('from') ? Carbon::parse($request->query('from')) : $toDate->copy()->subDays(6),
            default  => $toDate->copy()->subDays(6),
        };
        if ($range === 'custom' && $request->filled('to')) {
            $toDate = Carbon::parse($request->query('to'));
        }

        $history = Property::where('url_id', $property->url_id)
            ->where('property_name', $property->property_name)
            ->whereBetween('scrape_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderByDesc('scrape_date')
            ->get(['scrape_date','rent','original']);

        $unit = preg_replace('/[^a-zA-Z0-9_-]/', '_', $property->property_name);
        $filename = "price_history_{$unit}.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($history) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['Date', 'Rent', 'Source']);
            $prev = null;
            foreach ($history as $row) {
                $dateStr = $row->scrape_date instanceof Carbon
                    ? $row->scrape_date->format('Y-m-d')
                    : (string) $row->scrape_date;
                fputcsv($fp, [
                    $dateStr,
                    $row->rent,
                    $row->original ? 'Scraped' : 'Estimated',
                ]);
            }
            fclose($fp);
        };

        return response()->stream($callback, 200, $headers);
    }
}
