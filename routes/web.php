<?php

use App\Http\Controllers\Admin\DentistController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Dashboard\ForecastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\Staff\StaffAppointmentController;
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

// Appointment booking (patients)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');
    Route::get('/appointments/book', [AppointmentController::class, 'create'])
        ->name('appointments.create');
    Route::get('/appointments/availability', [AppointmentController::class, 'availability'])
        ->name('appointments.availability');
    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->name('appointments.store');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');
});

// Staff appointment management (staff + admin)
Route::middleware(['auth', 'verified', 'role:staff,admin'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/appointments', [StaffAppointmentController::class, 'index'])
            ->name('appointments.index');
        Route::get('/appointments/{appointment}/assign', [StaffAppointmentController::class, 'assignForm'])
            ->name('appointments.assign.form');
        Route::patch('/appointments/{appointment}/assign', [StaffAppointmentController::class, 'assign'])
            ->name('appointments.assign');
        Route::patch('/appointments/{appointment}/cancel', [StaffAppointmentController::class, 'cancel'])
            ->name('appointments.cancel');
    });

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
    Route::patch('/profile/patient', [PatientProfileController::class, 'update'])->name('profile.patient.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
