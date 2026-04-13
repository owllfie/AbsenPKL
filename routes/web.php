<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminTableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordChangeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->password_changed_at === null
        ? redirect()->route('password.edit')
        : redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/change-password', [PasswordChangeController::class, 'edit'])->name('password.edit');
    Route::put('/change-password', [PasswordChangeController::class, 'update'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/admin/{module}', [AdminTableController::class, 'show'])->name('admin.module');
});
