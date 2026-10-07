<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // API clients always get JSON, even when they forget the Accept header
        $request->headers->set('Accept', 'application/json');

        $locale = $request->header('X-App-Locale');
        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        return $next($request);
    }
}
