<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScrapeUrlController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ScrapeLogController;

/**
 * Scraper API — consumed by the Python scraper backend (server.py).
 * Auth: static token in the X-Api-Token header (set SCRAPER_API_TOKEN in .env).
 */

Route::middleware('scraper.token')->prefix('scraper')->group(function () {
    // URLs to scrape
    Route::get('/urls', [ScrapeUrlController::class, 'index']);
    Route::post('/urls/{id}/status', [ScrapeUrlController::class, 'updateStatus']);

    // Property data ingestion
    Route::post('/properties', [PropertyController::class, 'store']);

    // Log ingestion (Python scraper -> Laravel)
    Route::post('/logs', [ScrapeLogController::class, 'store']);
});
