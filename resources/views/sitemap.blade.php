<?= '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->timezone('UTC')->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/privacy') }}</loc>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ url('/terms') }}</loc>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ url('/dashboard') }}</loc>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/series') }}</loc>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ url('/collection') }}</loc>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ url('/collection/clippers') }}</loc>
        <priority>0.7</priority>
    </url>
    @foreach ($series as $s)
        <url>
            <loc>{{ route('series.show', ['series' => $s->id, 'slug' => str($s->name)->slug()]) }}</loc>
            <lastmod>{{ $s->updated_at->timezone('UTC')->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
</urlset>
