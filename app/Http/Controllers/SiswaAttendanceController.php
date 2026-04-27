<?php

namespace App\Http\Controllers;

use App\Services\IpLocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SiswaAttendanceController extends Controller
{
    public function __construct(private readonly IpLocationService $ipLocation)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();

        if (!$siswa) {
            return redirect()->route('dashboard')->with('error', 'Data siswa tidak ditemukan.');
        }

        $today = Carbon::today()->toDateString();
        $attendance = DB::table('absensi')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        return view('siswa.absensi', [
            'siswa' => $siswa,
            'attendance' => $attendance,
            'today' => Carbon::today()->locale('id')->translatedFormat('l, d F Y'),
        ]);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'image' => 'required|string',
        ]);

        $user = $request->user();
        $siswa = $this->findStudent($user->id_user);

        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $token = DB::table('attendance_qr_tokens')
            ->where('payload', $request->string('qr_code')->toString())
            ->whereDate('active_on', $today)
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->latest('id')
            ->first();

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'QR code tidak valid atau sudah kadaluarsa.'], 422);
        }

        $attendance = DB::table('absensi')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        try {
            $photoPath = $this->storeProofPhoto($request->input('image'), (string) $siswa->nis);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        $ipInfo = $this->ipLocation->lookup($request->ip());
        $locationLabel = $ipInfo['label'] ?? 'Lokasi tidak diketahui';

        if (!$attendance) {
            $payload = [
                'id_siswa' => $siswa->nis,
                'tanggal' => $today,
                'jam_datang' => $now,
                'status' => 0,
                'foto_bukti' => $photoPath,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $payload = array_merge($payload, $this->optionalColumns([
                'ip_address_datang' => $request->ip(),
                'lokasi_datang' => $locationLabel,
                'qr_token' => $token->token,
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
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan absen masuk dan pulang hari ini.'], 400);
            }

            $updatePayload = [
                'status' => 1,
                'updated_at' => $now,
            ];

            $updatePayload = array_merge($updatePayload, $this->optionalColumns([
                'jam_pulang' => $now,
                'ip_address_pulang' => $request->ip(),
                'lokasi_pulang' => $locationLabel,
                'foto_bukti_pulang' => $photoPath,
                'qr_token' => $token->token,
            ]));

            if (! Schema::hasColumn('absensi', 'foto_bukti_pulang')) {
                $updatePayload['foto_bukti'] = $photoPath;
            }

            DB::table('absensi')
                ->where('id_absensi', $attendance->id_absensi)
                ->update($updatePayload);

            $message = 'Absen pulang berhasil direkam. Status: Hadir.';
            $action = 'attendance_check_out';
            $subjectId = $attendance->id_absensi;
        }

        DB::table('attendance_qr_tokens')
            ->where('id', $token->id)
            ->increment('used_count');

        $request->attributes->set('activity_log', [
            'module_key' => 'absensi',
            'action' => $action,
            'description' => $message,
            'location_label' => $locationLabel,
            'subject_type' => 'absensi',
            'subject_id' => $subjectId,
            'properties' => [
                'student_nis' => $siswa->nis,
                'qr_token' => $token->token,
                'photo_path' => $photoPath,
                'location_lookup' => $ipInfo,
            ],
        ]);

        return response()->json(['success' => true, 'message' => $message]);
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

    private function storeProofPhoto(string $imageData, string $studentId): string
    {
        [$mime, $encoded] = array_pad(explode(';base64,', $imageData, 2), 2, null);

        if (! $encoded) {
            throw new \InvalidArgumentException('Format foto bukti tidak valid.');
        }

        $extension = str_contains((string) $mime, 'jpeg') || str_contains((string) $mime, 'jpg')
            ? 'jpg'
            : 'png';

        $binary = base64_decode(str_replace(' ', '+', $encoded), true);

        if ($binary === false) {
            throw new \InvalidArgumentException('Foto bukti tidak dapat diproses.');
        }

        $fileName = 'absensi/' . $studentId . '_' . now()->format('Ymd_His') . '.' . $extension;
        Storage::disk('public')->put($fileName, $binary);

        return $fileName;
    }

    private function optionalColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('absensi', $column))
            ->all();
    }
}
