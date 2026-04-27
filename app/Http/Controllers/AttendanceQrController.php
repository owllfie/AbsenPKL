<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceQrController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperadmin($request->user());

        $token = $this->currentToken($request->user());

        $request->attributes->set('activity_log', [
            'module_key' => 'attendance-qr',
            'action' => 'attendance_qr_view',
            'description' => 'Membuka halaman QR absensi.',
            'subject_type' => 'attendance_qr_tokens',
            'subject_id' => $token->id,
        ]);

        return view('admin.attendance-qr', [
            'pageTitle' => 'QR Absensi',
            'pageDescription' => 'QR code harian untuk absensi masuk dan pulang siswa.',
            'token' => $token,
        ]);
    }

    public function refresh(Request $request): RedirectResponse
    {
        $this->ensureSuperadmin($request->user());

        $token = DB::transaction(function () use ($request) {
            DB::table('attendance_qr_tokens')
                ->whereDate('active_on', now()->toDateString())
                ->update([
                    'expires_at' => now(),
                    'updated_at' => now(),
                ]);

            return $this->issueToken($request->user());
        });

        $request->attributes->set('activity_log', [
            'module_key' => 'attendance-qr',
            'action' => 'attendance_qr_refresh',
            'description' => 'Generate QR absensi baru.',
            'subject_type' => 'attendance_qr_tokens',
            'subject_id' => $token->id,
            'properties' => [
                'payload' => $token->payload,
                'active_on' => $token->active_on,
            ],
        ]);

        return redirect()
            ->route('attendance.qr')
            ->with('success', 'QR absensi berhasil diperbarui.');
    }

    public function currentToken(User $user): object
    {
        $token = DB::table('attendance_qr_tokens')
            ->whereDate('active_on', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        return $token ?: $this->issueToken($user);
    }

    private function issueToken(User $user): object
    {
        $token = Str::upper(Str::random(32));
        $payload = 'ABSENPKL|' . $token;

        $id = DB::table('attendance_qr_tokens')->insertGetId([
            'token' => $token,
            'payload' => $payload,
            'active_on' => now()->toDateString(),
            'expires_at' => now()->endOfDay(),
            'created_by' => $user->id_user,
            'used_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('attendance_qr_tokens')->where('id', $id)->first();
    }

    private function ensureSuperadmin(User $user): void
    {
        abort_unless((int) $user->role === 8, 403);
    }
}
