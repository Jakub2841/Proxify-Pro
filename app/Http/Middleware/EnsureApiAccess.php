<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('api_access', true)) {
            return response()->json(['message' => 'API access is disabled.'], 403);
        }

        return $next($request);
    }
}
