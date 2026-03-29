<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

    // Public
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    });

    // ── Queue Routes ──────────────────────────────────────────
Route::prefix('queue')->name('queue.')->group(function () {

    // Receptionist dashboard
    Route::get('/', [App\Http\Controllers\QueueController::class, 'index'])
        ->name('index');

    // Issue new ticket
    Route::post('/issue', [App\Http\Controllers\QueueController::class, 'issueTicket'])
        ->name('issue');

    // Cancel ticket
    Route::patch('/{ticket}/cancel', [App\Http\Controllers\QueueController::class, 'cancelTicket'])
        ->name('cancel');

    // Room actions
    Route::post('/call-next', [App\Http\Controllers\QueueController::class, 'callNext'])
        ->name('call-next');
    Route::patch('/assignments/{assignment}/serving', [App\Http\Controllers\QueueController::class, 'markServing'])
        ->name('serving');
    Route::patch('/assignments/{assignment}/complete', [App\Http\Controllers\QueueController::class, 'markComplete'])
        ->name('complete');
    Route::patch('/assignments/{assignment}/no-show', [App\Http\Controllers\QueueController::class, 'markNoShow'])
        ->name('no-show');
    Route::patch('/assignments/{assignment}/skip', [App\Http\Controllers\QueueController::class, 'skip'])
        ->name('skip');
    Route::patch('/assignments/{assignment}/recall', [App\Http\Controllers\QueueController::class, 'recall'])
        ->name('recall');

    // Room screens (per department)
    Route::get('/room/{room}', [App\Http\Controllers\QueueController::class, 'roomScreen'])
        ->name('room');

    // Patient search API
    Route::get('/search-patient', [App\Http\Controllers\QueueController::class, 'searchPatient'])
        ->name('search-patient');
});

// TV Display Board — public, no auth needed
Route::get('/queue-display', [App\Http\Controllers\QueueController::class, 'display'])
    ->name('queue.display');


    // Authenticated
    Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Force password change
    Route::get('/change-password', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/change-password', [PasswordChangeController::class, 'update'])->name('password.update');

    // Role-based dashboards
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Patient routes
    Route::middleware('permission:patients.view')
        ->group(function () {
            Route::resource('patients', \App\Http\Controllers\PatientController::class);
        });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', fn() => inertia('Admin/Users/Index'))->name('users.index');
    });

    // Receptionist routes
    Route::middleware('role:admin|receptionist')->prefix('reception')->name('reception.')->group(function () {
        Route::get('/', fn() => inertia('Reception/Index'))->name('index');
    });

    // Lab routes
    Route::middleware('role:admin|lab_technician|doctor')->prefix('laboratory')->name('lab.')->group(function () {
        Route::get('/', fn() => inertia('Laboratory/Index'))->name('index');
    });

    // X-Ray routes
    Route::middleware('role:admin|xray_tech|doctor')->prefix('xray')->name('xray.')->group(function () {
        Route::get('/', fn() => inertia('XRay/Index'))->name('index');
    });

    // Drug Test routes
    Route::middleware('role:admin|drug_test_staff')->prefix('drug-test')->name('drug_test.')->group(function () {
        Route::get('/', fn() => inertia('DrugTest/Index'))->name('index');
    });
});
