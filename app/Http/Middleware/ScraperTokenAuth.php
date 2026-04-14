<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScraperTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.scraper.token');
        $provided = $request->header('X-Api-Token');

        if (!$expected || $provided !== $expected) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
