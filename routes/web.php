<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminTableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManageAccessController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PKLChatbotController;
use App\Http\Controllers\SiswaAttendanceController;
use App\Http\Controllers\SiswaAgendaController;
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

    // Siswa Absensi Routes
    Route::get('/siswa/absensi', [SiswaAttendanceController::class, 'index'])->name('siswa.absensi');
    Route::post('/siswa/absensi/scan', [SiswaAttendanceController::class, 'scan'])->name('siswa.absensi.scan');
    Route::post('/siswa/absensi/izin', [SiswaAttendanceController::class, 'izin'])->name('siswa.absensi.izin');

    // Siswa Agenda Routes
    Route::get('/siswa/agenda', [SiswaAgendaController::class, 'index'])->name('siswa.agenda');
    Route::post('/siswa/agenda', [SiswaAgendaController::class, 'store'])->name('siswa.agenda.store');

    Route::get('/chatbot', [PKLChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot/ask', [PKLChatbotController::class, 'ask'])->name('chatbot.ask');
    Route::get('/chatbot/stats', [PKLChatbotController::class, 'stats'])->name('chatbot.stats');
    Route::get('/manage-access', [ManageAccessController::class, 'show'])->name('manage-access');
    Route::post('/manage-access', [ManageAccessController::class, 'update'])->name('manage-access.update');
    Route::get('/admin/{module}', [AdminTableController::class, 'show'])->name('admin.module');
    Route::post('/admin/users/{id}/reset-password', [AdminTableController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::post('/admin/{module}', [AdminTableController::class, 'store'])->name('admin.module.store');
    Route::put('/admin/{module}/{id}', [AdminTableController::class, 'update'])->name('admin.module.update');
    Route::delete('/admin/{module}/{id}', [AdminTableController::class, 'destroy'])->name('admin.module.destroy');
});
