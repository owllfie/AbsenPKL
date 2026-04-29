<?php

namespace App\Http\Controllers;

use App\Services\IpLocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiswaAttendanceController extends Controller
{
    public function __construct(private readonly IpLocationService $ipLocation)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $siswa = $this->findStudent($user->id_user);

        if (! $siswa) {
            return redirect()->route('dashboard')->with('error', 'Data siswa tidak ditemukan.');
        }

        $today = Carbon::today()->toDateString();
        $attendance = DB::table('absensi')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        return view('siswa.absensi-button', [
            'siswa' => $siswa,
            'attendance' => $attendance,
            'today' => Carbon::today()->locale('id')->translatedFormat('l, d F Y'),
        ]);
    }

    public function submit(Request $request)
    {
        $user = $request->user();
        $siswa = $this->findStudent($user->id_user);

        if (! $siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $result = $this->recordAttendance($request, $siswa);

        return response()->json($result, $result['success'] ? 200 : ($result['status_code'] ?? 422));
    }

    public function izin(Request $request)
    {
        $request->validate([
            'keterangan' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $siswa = $this->findStudent($user->id_user);

        if (!$siswa) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $today = Carbon::today()->toDateString();
        $attendance = DB::table('absensi')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        if ($attendance) {
            return back()->with('error', 'Anda sudah memiliki catatan absensi hari ini.');
        }

        DB::table('absensi')->insert([
            'id_siswa' => $siswa->nis,
            'tanggal' => $today,
            'status' => 2,
            'keterangan' => $request->input('keterangan'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request->attributes->set('activity_log', [
            'module_key' => 'absensi',
            'action' => 'attendance_permission_create',
            'description' => 'Mengirim izin absensi.',
            'subject_type' => 'absensi',
            'subject_id' => DB::table('absensi')
                ->where('id_siswa', $siswa->nis)
                ->where('tanggal', $today)
                ->value('id_absensi'),
            'properties' => [
                'student_nis' => $siswa->nis,
                'keterangan' => $request->input('keterangan'),
            ],
        ]);

        return back()->with('success', 'Keterangan izin berhasil disimpan.');
    }

    private function findStudent(int $userId): ?object
    {
        return DB::table('siswa')->where('id_user', $userId)->first();
    }

    private function recordAttendance(Request $request, object $siswa): array
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $attendance = DB::table('absensi')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        $ipInfo = $this->ipLocation->lookup($request->ip());
        $locationLabel = $ipInfo['label'] ?? 'Lokasi tidak diketahui';

        if (! $attendance) {
            $payload = [
                'id_siswa' => $siswa->nis,
                'tanggal' => $today,
                'jam_datang' => $now,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $payload = array_merge($payload, $this->optionalColumns([
                'ip_address_datang' => $request->ip(),
                'lokasi_datang' => $locationLabel,
            ]));

            DB::table('absensi')->insert($payload);
            $message = 'Absen masuk berhasil direkam.';
            $action = 'attendance_check_in';
            $subjectId = DB::table('absensi')
                ->where('id_siswa', $siswa->nis)
                ->where('tanggal', $today)
                ->value('id_absensi');
        } else {
            if ($attendance->jam_pulang) {
                return [
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen masuk dan pulang hari ini.',
                    'status_code' => 400,
                ];
            }

            $updatePayload = [
                'status' => 1,
                'updated_at' => $now,
            ];

            $updatePayload = array_merge($updatePayload, $this->optionalColumns([
                'jam_pulang' => $now,
                'ip_address_pulang' => $request->ip(),
                'lokasi_pulang' => $locationLabel,
            ]));

            DB::table('absensi')
                ->where('id_absensi', $attendance->id_absensi)
                ->update($updatePayload);

            $message = 'Absen pulang berhasil direkam. Status: Hadir.';
            $action = 'attendance_check_out';
            $subjectId = $attendance->id_absensi;
        }

        $request->attributes->set('activity_log', [
            'module_key' => 'absensi',
            'action' => $action,
            'description' => $message,
            'location_label' => $locationLabel,
            'subject_type' => 'absensi',
            'subject_id' => $subjectId,
            'properties' => array_filter([
                'student_nis' => $siswa->nis,
                'location_lookup' => $ipInfo,
            ], fn ($value) => $value !== null),
        ]);

        return [
            'success' => true,
            'message' => $message,
        ];
    }

    private function optionalColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('absensi', $column))
            ->all();
    }
}
