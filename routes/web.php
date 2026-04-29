<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminTableController;
use App\Http\Controllers\AgendaApprovalController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceQrController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManageAccessController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PKLChatbotController;
use App\Http\Controllers\SiswaAttendanceController;
use App\Http\Controllers\SiswaAgendaController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\BimbinganController;
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

Route::middleware(['auth', 'password.changed', 'activity.log'])->group(function () {
    Route::get('/change-password', [PasswordChangeController::class, 'edit'])->name('password.edit');
    Route::put('/change-password', [PasswordChangeController::class, 'update'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Siswa Absensi Routes
    Route::get('/siswa/absensi', [SiswaAttendanceController::class, 'index'])->name('siswa.absensi');
    Route::post('/siswa/absensi/submit', [SiswaAttendanceController::class, 'submit'])->name('siswa.absensi.submit');
    Route::post('/siswa/absensi/izin', [SiswaAttendanceController::class, 'izin'])->name('siswa.absensi.izin');

    // Siswa Agenda Routes
    Route::get('/siswa/agenda', [SiswaAgendaController::class, 'index'])->name('siswa.agenda');
    Route::post('/siswa/agenda', [SiswaAgendaController::class, 'store'])->name('siswa.agenda.store');
    Route::get('/agenda/review', [AgendaApprovalController::class, 'index'])->name('agenda.review');
    Route::post('/agenda/{agenda}/assessment', [AgendaApprovalController::class, 'saveAssessment'])->name('agenda.review.assessment');
    Route::post('/agenda/{agenda}/approve', [AgendaApprovalController::class, 'approve'])->name('agenda.review.approve');
    Route::post('/agenda/{agenda}/disapprove', [AgendaApprovalController::class, 'disapprove'])->name('agenda.review.disapprove');

    Route::get('/absensi/rekap', [AttendanceReportController::class, 'weekly'])->name('absensi.rekap');

    Route::get('/bimbingan', [BimbinganController::class, 'index'])->name('bimbingan.index');
    Route::post('/bimbingan', [BimbinganController::class, 'store'])->name('bimbingan.store');
    Route::post('/bimbingan/{id}/approve', [BimbinganController::class, 'approve'])->name('bimbingan.approve');
    Route::delete('/bimbingan/{id}', [BimbinganController::class, 'destroy'])->name('bimbingan.destroy');

    Route::post('/chatbot/ask', [PKLChatbotController::class, 'ask'])->name('chatbot.ask');
    Route::get('/chatbot/stats', [PKLChatbotController::class, 'stats'])->name('chatbot.stats');
    Route::get('/manage-access', [ManageAccessController::class, 'show'])->name('manage-access');
    Route::post('/manage-access', [ManageAccessController::class, 'update'])->name('manage-access.update');
    Route::get('/superadmin/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
    Route::post('/admin/web-setting/save', [AdminTableController::class, 'saveWebSetting'])->name('admin.web-setting.save');
    Route::get('/admin/backup-database/export', [AdminTableController::class, 'exportDatabase'])->name('admin.backup-database.export');
    Route::post('/admin/backup-database/import', [AdminTableController::class, 'importDatabase'])->name('admin.backup-database.import');
    Route::get('/admin/{module}', [AdminTableController::class, 'show'])->name('admin.module');
    Route::post('/admin/{module}/{id}/restore', [AdminTableController::class, 'restore'])->name('admin.module.restore');
    Route::delete('/admin/{module}/{id}/force', [AdminTableController::class, 'forceDelete'])->name('admin.module.force-delete');
    Route::get('/admin/{module}/{id}/history', [AdminTableController::class, 'history'])->name('admin.module.history');
    Route::post('/admin/history/{id}/revert', [AdminTableController::class, 'revert'])->name('admin.module.revert');
    Route::post('/admin/users/{id}/reset-password', [AdminTableController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::post('/admin/{module}/import', [AdminTableController::class, 'importModule'])->name('admin.module.import');
    Route::post('/admin/{module}', [AdminTableController::class, 'store'])->name('admin.module.store');
    Route::put('/admin/{module}/{id}', [AdminTableController::class, 'update'])->name('admin.module.update');
    Route::delete('/admin/{module}/{id}', [AdminTableController::class, 'destroy'])->name('admin.module.destroy');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');
});
