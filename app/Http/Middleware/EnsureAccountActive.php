<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            throw new ApiException('ACCOUNT_FROZEN_FRAUD', __('api.account_frozen'), 403);
        }

        return $next($request);
    }
}
