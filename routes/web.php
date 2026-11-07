<?php

use App\Http\Controllers\Admin\DentistController;
use App\Http\Controllers\Dashboard\ForecastController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Forecasting module (JSON feed for the dashboard chart)
Route::get('/dashboard/forecast', ForecastController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard.forecast');

// Dentist management (admin only)
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('dentists', DentistController::class);
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
