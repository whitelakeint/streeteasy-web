<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\ScrapeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : (Property::max('scrape_date') ?: Carbon::today()->toDateString());

        $q = Property::with('scrapeUrl')->whereDate('scrape_date', $date);

        if ($b = $request->query('building')) {
            $q->where('url_id', $b);
        }
        if ($beds = $request->query('beds')) {
            $q->where('beds_no', $beds);
        }
        if ($min = $request->query('min_rent')) {
            $q->where('rent', '>=', (int)$min);
        }
        if ($max = $request->query('max_rent')) {
            $q->where('rent', '<=', (int)$max);
        }

        $props = $q->orderBy('url_id')->orderBy('property_name')->paginate(25)->withQueryString();
        $buildings = ScrapeUrl::orderBy('name')->get(['id','name']);

        return view('properties.index', compact('props','buildings','date'));
    }
}
