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

// Authenticated
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Force password change
    Route::get('/change-password', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/change-password', [PasswordChangeController::class, 'update'])->name('password.update');

    // Role-based dashboards
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
