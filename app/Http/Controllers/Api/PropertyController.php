<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PropertyController extends Controller
{
    /**
     * POST /api/scraper/properties
     * Upserts properties for a given url_id + scrape_date.
     * Expected body:
     *   {
     *     "url_id": 1,
     *     "scrape_date": "2026-04-14",   (optional; defaults to today)
     *     "properties": [ { property_name, rent, beds, beds_no, baths, baths_no,
     *                       area, listing_url, listed_by, availability, specials } ]
     *   }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'url_id'      => 'required|integer|exists:scrape_urls,id',
            'scrape_date' => 'nullable|date',
            'properties'  => 'required|array',
            'properties.*.property_name' => 'nullable|string|max:255',
            'properties.*.rent'          => 'nullable|integer',
            'properties.*.beds'          => 'nullable|string|max:50',
            'properties.*.beds_no'       => 'nullable|integer',
            'properties.*.baths'         => 'nullable|string|max:50',
            'properties.*.baths_no'      => 'nullable|numeric',
            'properties.*.area'          => 'nullable|string|max:50',
            'properties.*.listing_url'   => 'nullable|string',
            'properties.*.listed_by'     => 'nullable|string|max:255',
            'properties.*.availability'  => 'nullable|string|max:100',
            'properties.*.specials'      => 'nullable|string',
        ]);

        $scrapeDate = !empty($data['scrape_date'])
            ? Carbon::parse($data['scrape_date'])->toDateString()
            : now()->toDateString();

        $inserted = 0;
        $updated = 0;

        foreach ($data['properties'] as $p) {
            $existing = Property::where('url_id', $data['url_id'])
                ->where('scrape_date', $scrapeDate)
                ->where('property_name', $p['property_name'] ?? '')
                ->first();

            $payload = array_merge($p, [
                'url_id' => $data['url_id'],
                'scrape_date' => $scrapeDate,
                'original' => 1,
            ]);

            if ($existing) {
                $existing->fill($payload)->save();
                $updated++;
            } else {
                Property::create($payload);
                $inserted++;
            }
        }

        return response()->json([
            'ok' => true,
            'inserted' => $inserted,
            'updated' => $updated,
        ]);
    }
}
