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
    
});

require __DIR__.'/settings.php';
