<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRequestWantsJson
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->wantsJson()) {
            abort(404);
        }

        return $next($request);
    }
}
