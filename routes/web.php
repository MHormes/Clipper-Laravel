<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClipperController;

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
    Route::get('/collection', [App\Http\Controllers\CollectionController::class, 'index'])->name('collection.index');
    Route::get('/collection/clippers', [App\Http\Controllers\CollectionController::class, 'clippers'])->name('collection.clippers');
    Route::get('/collection/{series}', [App\Http\Controllers\CollectionController::class, 'show'])->name('collection.show');

    // Series Management

    //Create
    Route::get('/series/create', [SeriesController::class, 'create'])->name('series.create');
    Route::post('/series', [SeriesController::class, 'store'])->name('series.store');

    //Read
    Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
    Route::get('/series/{series}', [SeriesController::class, 'show'])->name('series.show');

    //Update
    Route::get('/series/{series}/edit', [SeriesController::class, 'edit'])->name('series.edit');
    Route::put('/series/{series}', [SeriesController::class, 'update'])->name('series.update');

    //Delete
    Route::delete('/series/{series}', [SeriesController::class, 'destroy'])->name('series.destroy');

    //Clipper Management
    Route::post('/clippers/{clipper}/toggle', [ClipperController::class, 'toggle'])->name('clippers.toggle');
    Route::post('/series/{series}/toggle-collection', [SeriesController::class, 'toggleCollection'])->name('series.toggle-collection');

    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    });

});

require __DIR__.'/settings.php';
