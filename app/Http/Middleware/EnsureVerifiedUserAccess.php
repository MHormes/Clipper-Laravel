<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedUserAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->routeIs('verification.notice', 'verification.send', 'verification.verify', 'logout')) {
            if ($request->routeIs('verification.send') && RateLimiter::tooManyAttempts($user->emailVerificationRateLimitKey(), 1)) {
                return redirect()
                    ->route('verification.notice')
                    ->with('status', 'verification-link-throttled');
            }

            return $next($request);
        }

        return redirect()->route('verification.notice');
    }
}
