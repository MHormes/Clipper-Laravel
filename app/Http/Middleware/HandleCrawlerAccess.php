<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HandleCrawlerAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // If the user is authenticated, let them through
        if (Auth::check()) {
            return $next($request);
        }

        // List of common social media and search engine crawlers
        $crawlers = [
            'facebookexternalhit',
            'WhatsApp',
            'Twitterbot',
            'Slackbot',
            'LinkedInBot',
            'Googlebot',
            'bingbot',
            'Baidu',
            'yacybot',
            'YandexBot',
            'Sogou',
            'Exabot',
            'ia_archiver'
        ];

        $userAgent = $request->header('User-Agent', '');

        // Check if the current request is coming from a crawler
        foreach ($crawlers as $crawler) {
            if (stripos($userAgent, $crawler) !== false) {
                return $next($request);
            }
        }

        // Otherwise, redirect to login
        return redirect()->guest(route('login'));
    }
}
