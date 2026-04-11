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

// TV Display Board — public, no auth needed
Route::get('/queue-display', [QueueController::class, 'display'])->name('queue.display');

// Lab Results TV Display — public, no auth needed
Route::get('/lab-results-display', [\App\Http\Controllers\LaboratoryController::class, 'resultsDisplay'])->name('laboratory.results-display');

// Kiosk — public self-service terminal, no auth needed
Route::get('/queue/kiosk',          [QueueController::class, 'kiosk'])->name('queue.kiosk');
Route::post('/queue/kiosk-checkin', [QueueController::class, 'kioskCheckIn'])->name('queue.kiosk-checkin');
Route::get('/queue/search-patient-kiosk', [QueueController::class, 'searchPatient'])->name('queue.search-patient-kiosk');

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
        Route::patch('/kiosk-checkins/{checkin}/cancel',    [QueueController::class, 'cancelKioskCheckIn'])->name('kiosk-checkin.cancel');
        // Counter management (admin only)
        Route::post('/counters',                            [QueueController::class, 'storeCounter'])->name('counters.store');
        Route::patch('/counters/{counter}/toggle',          [QueueController::class, 'toggleCounter'])->name('counters.toggle');
        Route::delete('/counters/{counter}',                [QueueController::class, 'destroyCounter'])->name('counters.destroy');
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
            Route::get('/',                          [\App\Http\Controllers\LaboratoryController::class, 'index'])->name('index');
            Route::post('/collect/{visit}',          [\App\Http\Controllers\LaboratoryController::class, 'markCollected'])->name('collect');
            Route::get('/enter/{visit}',             [\App\Http\Controllers\LaboratoryController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}',            [\App\Http\Controllers\LaboratoryController::class, 'saveResults'])->name('save');
            Route::get('/print/{visit}',             [\App\Http\Controllers\LaboratoryController::class, 'print'])->name('print');
            Route::post('/claim/{labRequest}',       [\App\Http\Controllers\LaboratoryController::class, 'markClaimed'])->name('claim');
            Route::post('/unclaim/{labRequest}',     [\App\Http\Controllers\LaboratoryController::class, 'markUnclaimed'])->name('unclaim');
            Route::post('/bulk-claim',               [\App\Http\Controllers\LaboratoryController::class, 'bulkClaim'])->name('bulk-claim');
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

        Route::get('/audit', [\App\Http\Controllers\AuditController::class, 'index'])->name('audit');

        // Database Backup & Recovery
        Route::prefix('backup')->name('backup.')->group(function () {
            Route::get('/',                         [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
            Route::post('/',                        [\App\Http\Controllers\Admin\BackupController::class, 'create'])->name('create');
            Route::get('/{filename}/download',      [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('download');
            Route::post('/{filename}/restore',      [\App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('restore');
            Route::delete('/{filename}',            [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('destroy');
        });

        // Field Visit Sync
        Route::prefix('field-sync')->name('field-sync.')->group(function () {
            Route::get('/',                         [\App\Http\Controllers\Admin\FieldSyncController::class, 'index'])->name('index');
            Route::get('/export',                   [\App\Http\Controllers\Admin\FieldSyncController::class, 'export'])->name('export');
            Route::post('/preview',                 [\App\Http\Controllers\Admin\FieldSyncController::class, 'preview'])->name('preview');
            Route::post('/import',                  [\App\Http\Controllers\Admin\FieldSyncController::class, 'import'])->name('import');
            Route::post('/cancel-preview',          [\App\Http\Controllers\Admin\FieldSyncController::class, 'cancelPreview'])->name('cancel-preview');
        });

        // Staff E-Signatures
        Route::get('/esignatures',                           [\App\Http\Controllers\Admin\EsignatureController::class, 'index'])->name('esignatures.index');
        Route::post('/esignatures',                          [\App\Http\Controllers\Admin\EsignatureController::class, 'store'])->name('esignatures.store');
        Route::delete('/esignatures/{esignature}',           [\App\Http\Controllers\Admin\EsignatureController::class, 'destroy'])->name('esignatures.destroy');
        Route::patch('/esignatures/{esignature}/toggle',     [\App\Http\Controllers\Admin\EsignatureController::class, 'toggleActive'])->name('esignatures.toggle');

        // Package Discounts
        Route::get('/package-discounts', [\App\Http\Controllers\Admin\PackageDiscountController::class, 'index'])->name('package-discounts.index');
        Route::patch('/package-discounts/{packageDiscount}', [\App\Http\Controllers\Admin\PackageDiscountController::class, 'update'])->name('package-discounts.update');
        Route::patch('/package-discounts/{packageDiscount}/toggle', [\App\Http\Controllers\Admin\PackageDiscountController::class, 'toggleActive'])->name('package-discounts.toggle');

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
            Route::get('/',                   [\App\Http\Controllers\XRayController::class, 'index'])->name('index');
            Route::post('/collect/{visit}',   [\App\Http\Controllers\XRayController::class, 'markCollected'])->name('collect');
            Route::get('/enter/{visit}',      [\App\Http\Controllers\XRayController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}',     [\App\Http\Controllers\XRayController::class, 'saveFindings'])->name('save');
            Route::get('/print/{visit}',      [\App\Http\Controllers\XRayController::class, 'print'])->name('print');
        });

    // ── Drug Test ─────────────────────────────────────
    Route::middleware('role:admin|drug_test_staff|doctor')
        ->prefix('drug-test')->name('drug-test.')->group(function () {
            Route::get('/',                   [\App\Http\Controllers\DrugTestController::class, 'index'])->name('index');
            Route::post('/collect/{visit}',   [\App\Http\Controllers\DrugTestController::class, 'markCollected'])->name('collect');
            Route::get('/enter/{visit}',      [\App\Http\Controllers\DrugTestController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}',     [\App\Http\Controllers\DrugTestController::class, 'save'])->name('save');
            Route::get('/print/{visit}',      [\App\Http\Controllers\DrugTestController::class, 'print'])->name('print');
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
            Route::post('/invoice/{invoice}/void',          [\App\Http\Controllers\BillingController::class, 'voidInvoice'])->name('void');
            Route::post('/invoice/{invoice}/toggle-company',  [\App\Http\Controllers\BillingController::class, 'toggleCompanyBilling'])->name('toggle-company');
            Route::get('/company-billing',                    [\App\Http\Controllers\BillingController::class, 'companyBilling'])->name('company-billing');
            Route::post('/company-billing/payment',           [\App\Http\Controllers\BillingController::class, 'recordCompanyPayment'])->name('company-payment');
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

// Google OAuth — must be outside auth middleware
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Sitemap — public
Route::get('/sitemap.xml', function () {
    return \Spatie\Sitemap\Sitemap::create()
        ->add(\Spatie\Sitemap\Tags\Url::create('https://saintpeterdiagnosticsandlaboratory.com/'))
        ->add(\Spatie\Sitemap\Tags\Url::create('https://saintpeterdiagnosticsandlaboratory.com/book-appointment'))
        ->toResponse(request());
});
