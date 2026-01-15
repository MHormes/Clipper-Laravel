<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Protected Routes (Require Login)
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');

    // Collection
    Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
    Route::get('/collection/clippers', [CollectionController::class, 'clippers'])->name('collection.clippers');
    Route::get('/collection/{series}', [CollectionController::class, 'show'])->name('collection.show');

    // Series Management - Write (Admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/series/create', [SeriesController::class, 'create'])->name('series.create');
        Route::post('/series', [SeriesController::class, 'store'])->name('series.store');

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
    });

});

require __DIR__.'/settings.php';
