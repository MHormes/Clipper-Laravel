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
        // Only include accepted series in the sitemap
        $series = Series::accepted()->latest()->get();

        return response()->view('sitemap', [
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
