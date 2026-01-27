<?= '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Loop through static pages --}}
    @foreach ($staticPages as $page)
        <url>
            <loc>{{ $page['url'] }}</loc>
            <lastmod>{{ now()->timezone('UTC')->toAtomString() }}</lastmod>
            <changefreq>{{ $page['freq'] }}</changefreq>
            <priority>{{ $page['priority'] }}</priority>
        </url>
    @endforeach

    {{-- Loop through dynamic series --}}
    @foreach ($series as $s)
        <url>
            <loc>{{ route('series.show', ['series' => $s->id, 'slug' => str($s->name)->slug()]) }}</loc>
            <lastmod>{{ $s->updated_at->timezone('UTC')->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
</urlset>