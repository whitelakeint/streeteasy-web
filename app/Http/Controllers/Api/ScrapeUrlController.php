<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScrapeUrl;
use Illuminate\Http\Request;

class ScrapeUrlController extends Controller
{
    /**
     * GET /api/scraper/urls
     * Returns all active URLs to scrape.
     */
    public function index()
    {
        $urls = ScrapeUrl::where('is_active', 1)
            ->orderBy('id')
            ->get(['id', 'name', 'url', 'last_status', 'last_scraped_at']);

        return response()->json(['urls' => $urls]);
    }

    /**
     * POST /api/scraper/urls/{id}/status
     * Updates last_status (and last_scraped_at if status = completed).
     */
    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:in_progress,completed,failed',
        ]);

        $url = ScrapeUrl::findOrFail($id);
        $url->last_status = $data['status'];

        if ($data['status'] === 'completed') {
            $url->last_scraped_at = now();
        }

        $url->save();

        return response()->json(['ok' => true]);
    }
}
