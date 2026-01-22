<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RequestController; // Added for request management

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/privacy', function(){
    return Inertia::render('Privacy');
})->name('privacy');

Route::get('/terms', function(){
    return Inertia::render('Terms');
})->name('terms');

// Protected Routes (Require Login)
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');

    //Map view
    Route::get('/mapview', [CollectionController::class, 'mapview'])->name('mapview.index');

    // Collection
    Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
    Route::get('/collection/clippers', [CollectionController::class, 'clippers'])->name('collection.clippers');

    // Series Management - Write
    // create and store are now open to all authenticated users for requests
    Route::get('/series/create', [SeriesController::class, 'create'])->name('series.create');
    Route::post('/series', [SeriesController::class, 'store'])->name('series.store');
    
    // Existing series requests
    Route::get('/series/{series}/request-clippers', [SeriesController::class, 'requestClippers'])->name('series.request-clippers');
    Route::post('/series/{series}/request-clippers', [SeriesController::class, 'storeClipperRequest'])->name('series.store-clipper-request');

    Route::middleware(['admin'])->group(function () {
        Route::get('/series/{series}/edit', [SeriesController::class, 'edit'])->name('series.edit');
        Route::put('/series/{series}', [SeriesController::class, 'update'])->name('series.update');
        Route::delete('/series/{series}', [SeriesController::class, 'destroy'])->name('series.destroy');
    });

    // Series Management - Read (Auth required)
    Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
    Route::get('/series/{series}', [SeriesController::class, 'show'])->name('series.show');

    //Clipper Management
    Route::post('/clippers/{clipper}/toggle', [CollectionController::class, 'toggle'])->name('clippers.toggle');
    Route::post('/series/{series}/toggle-collection', [CollectionController::class, 'toggleCollection'])->name('series.toggle-collection');

    Route::patch('/collection/{clipper}', [CollectionController::class, 'update'])
    ->name('collection.update')
    ->middleware('auth');

    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
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

require __DIR__.'/settings.php';
