<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tok routes
    Route::get('/toks/search', [\App\Http\Controllers\TokController::class, 'search'])->name('toks.search');
    Route::get('/toks', [\App\Http\Controllers\TokController::class, 'index'])->name('toks.index');
    Route::post('/toks', [\App\Http\Controllers\TokController::class, 'store'])->name('toks.store');
    Route::patch('/toks/{tok}', [\App\Http\Controllers\TokController::class, 'update'])->name('toks.update');
    Route::delete('/toks/{tok}', [\App\Http\Controllers\TokController::class, 'destroy'])->name('toks.destroy');

    // TokCategory routes
    Route::get('/tok-categories', [\App\Http\Controllers\TokCategoryController::class, 'index'])->name('tok-categories.index');
    Route::post('/tok-categories', [\App\Http\Controllers\TokCategoryController::class, 'store'])->name('tok-categories.store');
    Route::delete('/tok-categories/{tokCategory}', [\App\Http\Controllers\TokCategoryController::class, 'destroy'])->name('tok-categories.destroy');
});

require __DIR__.'/auth.php';

// Public user pages by username (placed at the end to avoid shadowing)
Route::get('/{username}', [\App\Http\Controllers\PublicUserController::class, 'show'])
    ->where('username', '^[A-Za-z0-9\-_. %]+$')
    ->name('users.show');
