<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $module = AdminAccess::moduleForRoute($request->route()?->getName());

        if ($module && ! AdminAccess::can($request->user(), $module)) {
            // Docs-only accounts land on the documentation instead of a 403 after login
            if ($request->routeIs('admin.dashboard') && AdminAccess::can($request->user(), 'api_docs')) {
                return redirect()->route('admin.api-docs.index');
            }

            abort(403, __('admin.access_denied'));
        }

        return $next($request);
    }
}
