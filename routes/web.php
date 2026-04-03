<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

// ── Public Routes ────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// TV Display Board — public kiosk, no auth needed
Route::get('/queue-display', [QueueController::class, 'display'])->name('queue.display');

// Online Appointment Booking — public, no auth needed
Route::get('/book-appointment',  [AppointmentController::class, 'bookingForm'])->name('appointments.book');
Route::post('/book-appointment', [AppointmentController::class, 'bookingStore'])->name('appointments.book.store');

// ── Authenticated Routes ─────────────────────────────
Route::middleware('auth')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout'])->name('logout');
    Route::get('/change-password',  [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/change-password', [PasswordChangeController::class, 'update'])->name('password.update');

    // Dashboard
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Patients ──────────────────────────────────────
    Route::resource('patients', PatientController::class);

    // ── Queue ─────────────────────────────────────────
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/',                                     [QueueController::class, 'index'])->name('index');
        Route::post('/issue',                               [QueueController::class, 'issueTicket'])->name('issue');
        Route::patch('/{ticket}/cancel',                    [QueueController::class, 'cancelTicket'])->name('cancel');
        Route::post('/call-next',                           [QueueController::class, 'callNext'])->name('call-next');
        Route::patch('/assignments/{assignment}/serving',   [QueueController::class, 'markServing'])->name('serving');
        Route::patch('/assignments/{assignment}/complete',  [QueueController::class, 'markComplete'])->name('complete');
        Route::patch('/assignments/{assignment}/no-show',   [QueueController::class, 'markNoShow'])->name('no-show');
        Route::patch('/assignments/{assignment}/skip',      [QueueController::class, 'skip'])->name('skip');
        Route::patch('/assignments/{assignment}/recall',    [QueueController::class, 'recall'])->name('recall');
        Route::get('/room/{room}',                          [QueueController::class, 'roomScreen'])->name('room');
        Route::get('/search-patient',                       [QueueController::class, 'searchPatient'])->name('search-patient');
    });

    // ── Reception & Billing ───────────────────────────
    Route::prefix('reception')->name('reception.')->group(function () {
        Route::get('/',                           [\App\Http\Controllers\ReceptionController::class, 'index'])->name('index');
        Route::get('/create',                     [\App\Http\Controllers\ReceptionController::class, 'create'])->name('create');
        Route::post('/',                          [\App\Http\Controllers\ReceptionController::class, 'store'])->name('store');
        Route::get('/invoice/{invoice}',          [\App\Http\Controllers\ReceptionController::class, 'show'])->name('show');
        Route::post('/invoice/{invoice}/payment', [\App\Http\Controllers\ReceptionController::class, 'recordPayment'])->name('payment');
        Route::get('/search-patient',             [\App\Http\Controllers\ReceptionController::class, 'searchPatient'])->name('search-patient');
    });

    // ── Nurse Intake ──────────────────────────────────
    Route::middleware('role:admin|nurse|doctor')
        ->prefix('nurse')->name('nurse.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\NurseController::class, 'index'])->name('index');
            Route::get('/vitals/{visit}', [\App\Http\Controllers\NurseController::class, 'vitals'])->name('vitals');
            Route::post('/vitals/{visit}',[\App\Http\Controllers\NurseController::class, 'storeVitals'])->name('vitals.store');
        });

    // ── Doctor Dashboard ──────────────────────────────
    Route::middleware('role:admin|doctor|nurse')
        ->prefix('doctor')->name('doctor.')->group(function () {
            Route::get('/',                 [\App\Http\Controllers\DoctorController::class, 'index'])->name('index');
            Route::get('/consult/{visit}',  [\App\Http\Controllers\DoctorController::class, 'consult'])->name('consult');
            Route::post('/consult/{visit}', [\App\Http\Controllers\DoctorController::class, 'store'])->name('store');
            Route::get('/print/{visit}',    [\App\Http\Controllers\DoctorController::class, 'printExam'])->name('print');

            // ── Prescriptions ─────────────────────────
            Route::post('/prescription/{visit}',        [\App\Http\Controllers\DoctorController::class, 'storePrescription'])->name('prescription.store');
            Route::delete('/prescription/{prescription}',[\App\Http\Controllers\DoctorController::class, 'destroyPrescription'])->name('prescription.destroy');
            Route::get('/prescription/{prescription}/print',[\App\Http\Controllers\DoctorController::class, 'printPrescription'])->name('prescription.print');
        });

    // ── Laboratory ────────────────────────────────────
    Route::middleware('role:admin|lab_technician|doctor|nurse')
        ->prefix('laboratory')->name('laboratory.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\LaboratoryController::class, 'index'])->name('index');
            Route::get('/enter/{visit}',  [\App\Http\Controllers\LaboratoryController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}', [\App\Http\Controllers\LaboratoryController::class, 'saveResults'])->name('save');
            Route::get('/print/{visit}',  [\App\Http\Controllers\LaboratoryController::class, 'print'])->name('print'); // ← INSIDE prefix now
        });

    // ── Reports ───────────────────────────────────────
    Route::middleware('role:admin')
        ->get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])
        ->name('reports');

    // ── Admin ─────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->names('users');
        Route::post('/users/{user}/reset-password',   [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::patch('/users/{user}/toggle-active',   [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::resource('services', \App\Http\Controllers\Admin\ServiceCatalogController::class)->names('services');
        Route::patch('/services/{service}/toggle-active', [\App\Http\Controllers\Admin\ServiceCatalogController::class, 'toggleActive'])->name('services.toggle-active');

        Route::get('/audit', fn() => inertia('Admin/Audit'))->name('audit');

        // Booking page photo management
        Route::get('/booking-photos',                      [\App\Http\Controllers\Admin\BookingPhotoController::class, 'index'])->name('booking-photos.index');
        Route::post('/booking-photos',                     [\App\Http\Controllers\Admin\BookingPhotoController::class, 'store'])->name('booking-photos.store');
        Route::patch('/booking-photos/{photo}',            [\App\Http\Controllers\Admin\BookingPhotoController::class, 'update'])->name('booking-photos.update');
        Route::patch('/booking-photos/{photo}/toggle',     [\App\Http\Controllers\Admin\BookingPhotoController::class, 'toggleActive'])->name('booking-photos.toggle');
        Route::post('/booking-photos/reorder',             [\App\Http\Controllers\Admin\BookingPhotoController::class, 'reorder'])->name('booking-photos.reorder');
        Route::delete('/booking-photos/{photo}',           [\App\Http\Controllers\Admin\BookingPhotoController::class, 'destroy'])->name('booking-photos.destroy');
    });

    // ── X-Ray & Ultrasound ────────────────────────────
    Route::middleware('role:admin|xray_tech|doctor')
        ->prefix('xray')->name('xray.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\XRayController::class, 'index'])->name('index');
            Route::get('/enter/{visit}',  [\App\Http\Controllers\XRayController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}', [\App\Http\Controllers\XRayController::class, 'saveFindings'])->name('save');
            Route::get('/print/{visit}',  [\App\Http\Controllers\XRayController::class, 'print'])->name('print');
        });

    // ── Drug Test ─────────────────────────────────────
    Route::middleware('role:admin|drug_test_staff|doctor')
        ->prefix('drug-test')->name('drug-test.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\DrugTestController::class, 'index'])->name('index');
            Route::get('/enter/{visit}',  [\App\Http\Controllers\DrugTestController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}', [\App\Http\Controllers\DrugTestController::class, 'save'])->name('save');
            Route::get('/print/{visit}',  [\App\Http\Controllers\DrugTestController::class, 'print'])->name('print');
        });

        // ── Prescriptions ─────────────────────────────────────
    Route::middleware('role:admin|doctor|nurse')
        ->prefix('prescriptions')->name('prescriptions.')->group(function () {
            Route::get('/',                    [\App\Http\Controllers\PrescriptionController::class, 'index'])->name('index');
            Route::get('/create',              [\App\Http\Controllers\PrescriptionController::class, 'create'])->name('create');
            Route::post('/',                   [\App\Http\Controllers\PrescriptionController::class, 'store'])->name('store');
            Route::get('/print/{prescription}',[\App\Http\Controllers\PrescriptionController::class, 'print'])->name('print');
            Route::delete('/{prescription}',   [\App\Http\Controllers\PrescriptionController::class, 'destroy'])->name('destroy');
        });
        // ── Billing ───────────────────────────────────────────
    Route::middleware('role:admin|receptionist|billing')
        ->prefix('billing')->name('billing.')->group(function () {
            Route::get('/',                          [\App\Http\Controllers\BillingController::class, 'index'])->name('index');
            Route::get('/reports',                   [\App\Http\Controllers\BillingReportController::class, 'index'])->name('reports');
            Route::get('/invoice/{invoice}',         [\App\Http\Controllers\BillingController::class, 'show'])->name('show');
            Route::post('/invoice/{invoice}/payment',[\App\Http\Controllers\BillingController::class, 'recordPayment'])->name('payment');
            Route::post('/invoice/{invoice}/discount',[\App\Http\Controllers\BillingController::class, 'applyDiscount'])->name('discount');
            Route::post('/invoice/{invoice}/void',   [\App\Http\Controllers\BillingController::class, 'voidInvoice'])->name('void');
        });

    // ── Appointments (Staff Management) ──────────────────
    Route::middleware('role:admin|receptionist|billing|doctor|nurse')
        ->prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/',                                  [AppointmentController::class, 'index'])->name('index');
            Route::post('/{appointment}/confirm',            [AppointmentController::class, 'confirm'])->name('confirm');
            Route::post('/{appointment}/cancel',             [AppointmentController::class, 'cancel'])->name('cancel');
            Route::post('/{appointment}/complete',           [AppointmentController::class, 'complete'])->name('complete');
            Route::post('/{appointment}/no-show',            [AppointmentController::class, 'noShow'])->name('no-show');
            Route::post('/{appointment}/notes',              [AppointmentController::class, 'updateNotes'])->name('notes');
        });

});
