<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScrapeUrlController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PriceHistoryController;
use App\Http\Controllers\ScrapeControlController;
use Illuminate\Support\Facades\Route;

// Public — login
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Root redirect
Route::get('/', fn() => redirect()->route('dashboard'));

// Protected admin dashboard
Route::middleware('auth')->group(function () {
    Route::get('/dashboard',    [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/urls',         [ScrapeUrlController::class, 'index'])->name('urls.index');
    Route::post('/urls',        [ScrapeUrlController::class, 'store'])->name('urls.store');
    Route::post('/urls/{url}/toggle', [ScrapeUrlController::class, 'toggle'])->name('urls.toggle');
    Route::post('/urls/{url}/scrape', [ScrapeUrlController::class, 'scrape'])->name('urls.scrape');
    Route::delete('/urls/{url}', [ScrapeUrlController::class, 'destroy'])->name('urls.destroy');

    Route::get('/properties',            [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/export.csv', [PropertyController::class, 'exportCsv'])->name('properties.export');

    Route::get('/price-history/{property}',            [PriceHistoryController::class, 'show'])->name('price-history.show');
    Route::get('/price-history/{property}/export.csv', [PriceHistoryController::class, 'exportCsv'])->name('price-history.export');

    Route::get('/scrape-control', [ScrapeControlController::class, 'index'])->name('scrape.index');
    Route::get('/scrape-control/logs.json', [ScrapeControlController::class, 'logsJson'])->name('scrape.logs');
    Route::post('/scrape-control/run', [ScrapeControlController::class, 'run'])->name('scrape.run');
});
