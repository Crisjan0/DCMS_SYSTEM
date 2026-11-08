<?php

use App\Http\Controllers\Admin\DentistController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Clinic\AppointmentStatusController;
use App\Http\Controllers\Clinic\InvoiceController;
use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Clinic\PaymentController;
use App\Http\Controllers\Clinic\ToothRecordController;
use App\Http\Controllers\Clinic\TreatmentRecordController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\FlowController;
use App\Http\Controllers\Dashboard\ForecastController;
use App\Http\Controllers\Dentist\ScheduleController;
use App\Http\Controllers\Patient\MyInvoiceController;
use App\Http\Controllers\Patient\MyRecordController;
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\StaffAppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Forecasting module (JSON feed for the dashboard chart)
Route::get('/dashboard/forecast', ForecastController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard.forecast');

// Real-time patient-flow feed for the admin dashboard (auto-refreshed).
Route::get('/dashboard/flow', FlowController::class)
    ->middleware(['auth', 'verified', 'role:staff,admin,dentist'])
    ->name('dashboard.flow');

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

    // Self-service rescheduling of their own upcoming visits.
    Route::get('/appointments/{appointment}/reschedule', [AppointmentController::class, 'rescheduleForm'])
        ->name('appointments.reschedule.form');
    Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])
        ->name('appointments.reschedule');
});

// Dentist personal schedule (users linked to a dentist profile)
Route::get('/schedule', [ScheduleController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:staff,admin,dentist'])
    ->name('dentist.schedule');

// Patient self-service health record + invoices
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/my-record', [MyRecordController::class, 'index'])
        ->name('my-record.index');
    Route::post('/my-record/medical-history', [MyRecordController::class, 'saveMedicalHistory'])
        ->name('my-record.medical-history.save');
    Route::get('/my-invoices', [MyInvoiceController::class, 'index'])
        ->name('my-invoices.index');
});

// Clinic records (staff + admin + dentists)
Route::middleware(['auth', 'verified', 'role:staff,admin,dentist'])
    ->prefix('clinic')
    ->name('clinic.')
    ->group(function () {
        Route::get('/patients', [PatientController::class, 'index'])
            ->name('patients.index');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])
            ->name('patients.show');
        Route::post('/patients/{patient}/medical-history', [PatientController::class, 'storeMedicalHistory'])
            ->name('patients.medical-history.store');

        Route::post('/patients/{patient}/tooth-records', [ToothRecordController::class, 'update'])
            ->name('patients.tooth-records.update');

        Route::get('/patients/{patient}/treatments/create', [TreatmentRecordController::class, 'create'])
            ->name('patients.treatments.create');
        Route::get('/treatments/{treatment}/edit', [TreatmentRecordController::class, 'edit'])
            ->name('treatments.edit');
        Route::patch('/treatments/{treatment}', [TreatmentRecordController::class, 'update'])
            ->name('treatments.update');
        Route::delete('/treatments/{treatment}', [TreatmentRecordController::class, 'destroy'])
            ->name('treatments.destroy');
        Route::post('/patients/{patient}/treatments', [TreatmentRecordController::class, 'store'])
            ->name('patients.treatments.store');

        Route::patch('/appointments/{appointment}/status', [AppointmentStatusController::class, 'update'])
            ->name('appointments.status.update');
    });

// Billing (staff + admin handle money)
Route::middleware(['auth', 'verified', 'role:staff,admin'])
    ->prefix('clinic')
    ->name('clinic.')
    ->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->name('invoices.index');
        Route::get('/invoices/create', [InvoiceController::class, 'create'])
            ->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->name('invoices.store');
        Route::patch('/invoices/{invoice}/void', [InvoiceController::class, 'void'])
            ->name('invoices.void');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
            ->name('invoices.payments.store');
    });

// Invoice detail: staff/admin/dentists can view any; patients only their own.
Route::get('/clinic/invoices/{invoice}', [InvoiceController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('clinic.invoices.show');

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
        Route::get('/appointments/{appointment}/reschedule', [StaffAppointmentController::class, 'rescheduleForm'])
            ->name('appointments.reschedule.form');
        Route::patch('/appointments/{appointment}/reschedule', [StaffAppointmentController::class, 'reschedule'])
            ->name('appointments.reschedule');
        Route::patch('/appointments/{appointment}/cancel', [StaffAppointmentController::class, 'cancel'])
            ->name('appointments.cancel');
    });

// Dentist management (admin only)
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('dentists', DentistController::class)->except(['show']);
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/patient', [PatientProfileController::class, 'update'])->name('profile.patient.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
