<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate the XML sitemap.
     *
     * @return Response
     */
    public function index(): Response
    {
        $staticPages = [
            ['url' => url('/'), 'priority' => '1.0', 'freq' => 'daily'],
            ['url' => url('/dashboard'), 'priority' => '0.9', 'freq' => 'daily'],
            ['url' => url('/series'), 'priority' => '0.8', 'freq' => 'weekly'],
            ['url' => url('/my-series'), 'priority' => '0.7', 'freq' => 'weekly'],
            ['url' => url('/clippers'), 'priority' => '0.7', 'freq' => 'weekly'],
            ['url' => url('/privacy'), 'priority' => '0.3', 'freq' => 'monthly'],
            ['url' => url('/terms'), 'priority' => '0.3', 'freq' => 'monthly'],
        ];

        $series = Series::accepted()->latest()->get();

        return response()->view('sitemap', [
            'staticPages' => $staticPages,
            'series' => $series,
        ])->header('Content-Type', 'text/xml');
    }

    /**
     * Generate dynamic robots.txt.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /series/create\n";
        $content .= "Disallow: /series/*/request-clippers\n\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";

        return response($content)->header('Content-Type', 'text/plain');
    }
}
