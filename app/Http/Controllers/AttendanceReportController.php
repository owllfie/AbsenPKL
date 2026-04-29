<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function weekly(Request $request): View
    {
        $user = $request->user();
        $role = (int) $user->role;
        
        // Tentukan rentang waktu (Minggu ini: Senin sampai Minggu)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Ambil input tanggal jika user ingin melihat minggu sebelumnya
        if ($request->filled('start_date')) {
            $startOfWeek = Carbon::parse($request->start_date)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();
        }

        $dates = [];
        for ($date = $startOfWeek->copy(); $date <= $endOfWeek; $date->addDay()) {
            $dates[] = $date->copy();
        }

        if ($role === 1) {
            // Logika untuk Siswa
            $siswa = DB::table('siswa')->where('id_user', $user->id_user)->first();
            $attendance = DB::table('absensi')
                ->where('id_siswa', $siswa->nis)
                ->whereBetween('tanggal', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                ->get()
                ->keyBy('tanggal');

            return view('admin.absensi-rekap-siswa', [
                'dates' => $dates,
                'attendance' => $attendance,
                'startOfWeek' => $startOfWeek,
                'siswa' => $siswa,
            ]);
        }

        // Logika untuk Admin, Pembimbing, Kajur, Kepsek
        $query = DB::table('siswa')
            ->leftJoin('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
            ->whereNull('siswa.deleted_at')
            ->select('siswa.nis', 'siswa.nama_siswa', 'rombel.nama_rombel');

        // Filter berdasarkan Role
        if ($role === 4) { // Pembimbing
            $pembimbingId = DB::table('pembimbing')->where('id_user', $user->id_user)->value('id_pembimbing');
            $query->where('siswa.id_pembimbing', $pembimbingId);
        } elseif ($role === 2) { // Kajur
            $kajurId = DB::table('kajur')->where('id_user', $user->id_user)->value('id_kajur');
            // Kajur biasanya melihat berdasarkan jurusan, tapi id_jurusan sudah dihapus. 
            // Kita asumsikan Kajur melihat semua atau ada logika rombel tertentu.
            // Untuk sementara kita tampilkan semua yang tersedia atau sesuaikan dengan rombel.
        }

        $students = $query->orderBy('siswa.nama_siswa')->get();
        $studentNisList = $students->pluck('nis')->toArray();

        $attendanceData = DB::table('absensi')
            ->whereIn('id_siswa', $studentNisList)
            ->whereBetween('tanggal', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get()
            ->groupBy('id_siswa');

        $report = $students->map(function($student) use ($attendanceData, $dates) {
            $studentAttendance = $attendanceData->get($student->nis) ?? collect();
            $dailyStatus = [];
            
            foreach ($dates as $date) {
                $dayData = $studentAttendance->firstWhere('tanggal', $date->toDateString());
                $dailyStatus[$date->toDateString()] = $dayData ? $dayData->status : null;
            }

            return [
                'nis' => $student->nis,
                'nama' => $student->nama_siswa,
                'rombel' => $student->nama_rombel,
                'status_per_hari' => $dailyStatus,
                'summary' => [
                    'hadir' => $studentAttendance->where('status', 1)->count(),
                    'izin' => $studentAttendance->where('status', 2)->count(),
                    'sakit' => $studentAttendance->where('status', 3)->count(),
                    'alpha' => $studentAttendance->where('status', 4)->count(),
                ]
            ];
        });

        return view('admin.absensi-rekap-mingguan', [
            'dates' => $dates,
            'report' => $report,
            'startOfWeek' => $startOfWeek,
            'role' => $role
        ]);
    }
}
