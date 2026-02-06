<?php

namespace App\Support;

use App\Models\Series;

/**
 * Value object for SEO metadata.
 * 
 * Centralizes the creation and management of SEO/OG meta tags
 * to avoid duplication between controllers and Vue components.
 */
class SeoMetadata
{
    /**
     * Create a new SeoMetadata instance.
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $image,
        public ?string $canonical = null
    ) {}

    /**
     * Get default SEO metadata for the application.
     */
    public static function default(): self
    {
        return new self(
            title: 'Track Your Clipper Lighter Collection | Clipper-MS',
            description: 'Discover new series, catalog your clippers, and complete your series. Create your free account and start tracking your collection today!',
            image: url('/images/default-og.jpg')
        );
    }

    /**
     * Get SEO metadata for the dashboard.
     */
    public static function forDashboard(): self
    {
        return new self(
            title: 'My Clipper Lighter Collection Dashboard | Clipper-MS',
            description: 'Manage your Clipper collection dashboard. View collection stats, track series progress, and discover new Clipper designs.',
            image: url('/images/dash-og.jpg'),
            canonical: route('dashboard')
        );
    }

    /**
     * Get SEO metadata for a specific series.
     */
    public static function forSeries(Series $series): self
    {
        return new self(
            title: $series->name . ' Clipper Lighter Series | Clipper-MS',
            description: 'View the ' . $series->name . ' Clipper series. Track your collection progress, add notes, and complete this legendary Clipper series.',
            image: $series->image_data ?? url('/images/default-og.jpg'),
            canonical: route('series.show', ['series' => $series->id, 'slug' => $series->slug])
        );
    }

    /**
     * Get SEO metadata for the series index page.
     */
    public static function forSeriesIndex(): self
    {
        return new self(
            title: 'Browse All Clipper Lighter Series | Clipper-MS',
            description: 'Explore hundreds of Clipper series and designs. Find rare series, track your collection progress, and discover new Clipper releases.',
            image: url('/images/default-og.jpg'),
            canonical: route('series.index')
        );
    }

    /**
     * Get SEO metadata for the user's collection index (My Series).
     */
    public static function forCollectionIndex(): self
    {
        return new self(
            title: 'My Clipper Lighter Series Collection | Clipper-MS',
            description: 'View your Clipper series collection. Track completion progress, manage your series, and see which designs you still need to collect.',
            image: url('/images/default-og.jpg'),
            canonical: route('collection.index')
        );
    }

    /**
     * Get SEO metadata for the user's clippers board view.
     */
    public static function forClippersBoard(): self
    {
        return new self(
            title: 'My Clipper Lighters Collection | Clipper-MS',
            description: 'Browse all your collected Clipper in one place. View every design you own, search your collection, and track your progress.',
            image: url('/images/board-og.jpg'),
            canonical: route('collection.clippers')
        );
    }

    /**
     * Get SEO metadata for the collection map view.
     */
    public static function forMapView(): self
    {
        return new self(
            title: 'Collection Map | Clipper-MS',
            description: 'Interactive map showing where you found each Clipper. Visualize your collection journey and discover collecting hotspots.',
            image: url('/images/default-og.jpg'),
            canonical: route('mapview.index')
        );
    }

    /**
     * Convert to array format expected by withViewData().
     */
    public function toArray(): array
    {
        return [
            'metaTitle' => $this->title,
            'metaDescription' => $this->description,
            'metaImage' => $this->image,
            'metaCanonical' => $this->canonical ?? str_replace('http://', 'https://', url()->current())
        ];
    }
}
