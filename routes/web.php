<?php

use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Clipper\CollectionController;
use App\Http\Controllers\Clipper\DashboardController;
use App\Http\Controllers\Clipper\SeriesController;
use App\Http\Controllers\Clipper\UserDirectoryController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/manifest.webmanifest', function () {
    return response(
        json_encode([
            'name' => 'Clipper-MS',
            'short_name' => 'Clipper-MS',
            'description' => 'Track and manage your Clipper lighter collection.',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => 'rgb(250 250 250)',
            'theme_color' => 'rgb(245 48 3)',
            'icons' => [
                [
                    'src' => '/pwa-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/pwa-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/pwa-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        200,
        ['Content-Type' => 'application/manifest+json']
    );
})->name('manifest');

Route::get('/privacy', fn() => Inertia::render('Privacy'))->name('privacy');
Route::get('/terms', fn() => Inertia::render('Terms'))->name('terms');

// SEO & Utility
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Publicly viewable pages (Guest-smart)
Route::middleware(['crawler.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
    Route::get('/mapview', [CollectionController::class, 'mapview'])->name('mapview.index');
    Route::get('/collection', fn() => redirect()->route('series.index', ['filter' => 'collected'], 301))->name('collection.index');
    Route::get('/collection/clippers', [CollectionController::class, 'clippers'])->name('collection.clippers');
});


// Protected Routes (Require Login)
Route::middleware(['auth', 'verified'])->group(function () {
    // Pending Requests for Users
    Route::get('/requests/pending/series', [\App\Http\Controllers\Settings\PendingRequestController::class, 'seriesIndex'])->name('pending-requests.series');
    Route::get('/requests/pending/clippers', [\App\Http\Controllers\Settings\PendingRequestController::class, 'clippersIndex'])->name('pending-requests.clippers');

    Route::get('/users', [UserDirectoryController::class, 'index'])->name('users.index');
    Route::get('/users/following', [UserDirectoryController::class, 'following'])->name('users.following');
    Route::post('/users/{user}/toggle-follow', [UserDirectoryController::class, 'toggleFollow'])->name('users.toggle-follow');
    Route::get('/users/{user}/series/{series}/{slug?}', [SeriesController::class, 'showFromProfile'])->name('users.series.show');
    Route::get('/users/{user}', [UserDirectoryController::class, 'show'])->name('users.show');

    //Clipper Management
    Route::post('/clippers/{clipper}/toggle', [CollectionController::class, 'toggle'])->name('clippers.toggle');
    Route::post('/series/{series}/toggle-collection', [CollectionController::class, 'toggleCollection'])->name('series.toggle-collection');
    Route::patch('/collection/{clipper}', [CollectionController::class, 'update'])->name('collection.update');

    // Series Management - Write
    Route::get('/series/create', [SeriesController::class, 'create'])->name('series.create');
    Route::post('/series', [SeriesController::class, 'store'])->name('series.store');
    
    // Existing series requests
    Route::get('/series/{series}/request-clippers', [SeriesController::class, 'requestClippers'])->name('series.request-clippers');
    Route::post('/series/{series}/request-clippers', [SeriesController::class, 'storeClipperRequest'])->name('series.store-clipper-request');

    // Admin Role Required
    Route::middleware(['admin'])->group(function () {
        Route::get('/series/{series}/edit', [SeriesController::class, 'edit'])->name('series.edit');
        Route::put('/series/{series}', [SeriesController::class, 'update'])->name('series.update');
        Route::delete('/series/{series}', [SeriesController::class, 'destroy'])->name('series.destroy');
    });

    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        // User Management
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Request Management
        Route::get('/requests/series', [RequestController::class, 'pendingSeriesIndex'])->name('requests.series.index');
        Route::get('/requests/series/{series}', [RequestController::class, 'pendingSeriesShow'])->name('requests.series.show');
        Route::post('/requests/series/{series}/accept', [RequestController::class, 'acceptSeries'])->name('requests.series.accept');
        Route::delete('/requests/series/{series}', [RequestController::class, 'declineSeries'])->name('requests.series.decline');
        
        Route::get('/requests/clippers', [RequestController::class, 'pendingClippersIndex'])->name('requests.clippers.index');
        Route::post('/requests/clippers/{clipper}/accept', [RequestController::class, 'acceptClipper'])->name('requests.clippers.accept');
        Route::delete('/requests/clippers/{clipper}', [RequestController::class, 'declineClipper'])->name('requests.clippers.decline');
    });

});

Route::get('/series/{series}/{slug?}', [SeriesController::class, 'show'])
    ->middleware(['crawler.access'])
    ->name('series.show');

require __DIR__.'/settings.php';
