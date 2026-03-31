<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Public Routes ────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// TV Display Board — public kiosk, no auth needed
Route::get('/queue-display', [QueueController::class, 'display'])->name('queue.display');

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
        });

    // ── Laboratory ────────────────────────────────────
    Route::middleware('role:admin|lab_technician|doctor')
        ->prefix('laboratory')->name('laboratory.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\LaboratoryController::class, 'index'])->name('index');
            Route::get('/enter/{visit}',  [\App\Http\Controllers\LaboratoryController::class, 'enter'])->name('enter');
            Route::post('/enter/{visit}', [\App\Http\Controllers\LaboratoryController::class, 'saveResults'])->name('save');
            Route::get('/print/{visit}',  [\App\Http\Controllers\LaboratoryController::class, 'print'])->name('print'); // ← INSIDE prefix now
        });

    // ── Admin ─────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->names('users');
        Route::post('/users/{user}/reset-password',   [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::patch('/users/{user}/toggle-active',   [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::resource('services', \App\Http\Controllers\Admin\ServiceCatalogController::class)->names('services');
        Route::patch('/services/{service}/toggle-active', [\App\Http\Controllers\Admin\ServiceCatalogController::class, 'toggleActive'])->name('services.toggle-active');

        Route::get('/audit', fn() => inertia('Admin/Audit'))->name('audit');
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
    Route::get('/print/{visit}', [\App\Http\Controllers\DoctorController::class, 'printExam'])->name('print');
});
