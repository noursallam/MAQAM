<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetWebLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdmin = $request->is('admin') || $request->is('admin/*');
        $sessionKey = $isAdmin ? 'admin_locale' : 'store_locale';
        $locale = Session::get($sessionKey, config('app.locale', 'ar'));

        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
