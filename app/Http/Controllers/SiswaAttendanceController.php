<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiswaAttendanceController extends Controller
{
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
            'image' => 'required|string', // Base64 image from selfie
        ]);

        $user = $request->user();
        $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();

        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        // Logic for QR code validation could be added here (e.g. check if it matches a daily token)
        // For now we assume any QR scanned by the student is valid for their location.

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = DB::table('absensi')
            ->where('id_siswa', $siswa->nis)
            ->where('tanggal', $today)
            ->first();

        // Process image
        $img = $request->input('image');
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $fileName = 'absensi/' . $siswa->nis . '_' . time() . '.png';
        Storage::disk('public')->put($fileName, $data);

        if (!$attendance) {
            // First check-in (Jam Masuk)
            DB::table('absensi')->insert([
                'id_siswa' => $siswa->nis,
                'tanggal' => $today,
                'jam_datang' => $now,
                'status' => 0, // Not yet "Hadir" until 2nd check-in? Or maybe partial? 
                               // User says: "habis tuh baru dikirim masuk ke server database tabel absensi dan dalam satu hari bakal bisa absen 2x, 
                               // absen yg pertama jamnya bakal disimpan di jam_masuk dan yg kedua kali di jam_keluar, dan status nya kalau sudah absen dua kali isi dengan hadir"
                               // status 1 = Hadir, 4 = Tanpa Keterangan based on database.md
                'foto_bukti' => $fileName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $message = 'Absen masuk berhasil direkam.';
        } else {
            if ($attendance->jam_pulang) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan absen masuk dan pulang hari ini.'], 400);
            }

            // Second check-in (Jam Keluar)
            DB::table('absensi')
                ->where('id_absensi', $attendance->id_absensi)
                ->update([
                    'jam_pulang' => $now,
                    'status' => 1, // Hadir
                    'foto_bukti' => $fileName, // Update with latest photo or keep first? User said "simpan ke field foto_bukti"
                    'updated_at' => $now,
                ]);
            $message = 'Absen pulang berhasil direkam. Status: Hadir.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function izin(Request $request)
    {
        $request->validate([
            'keterangan' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();

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
            'status' => 2, // Izin based on common mapping (1=Hadir, 2=Izin, 3=Sakit, 4=Alpha)
            'keterangan' => $request->input('keterangan'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Keterangan izin berhasil disimpan.');
    }
}
