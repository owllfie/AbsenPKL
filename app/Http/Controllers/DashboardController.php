<?php

namespace App\Http\Controllers;

use App\Services\AccessControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AccessControlService $accessControl)
    {
    }

    public function __invoke(): View
    {
        $user = auth()->user();
        $student = DB::table('siswa')
            ->where('id_user', $user->id_user)
            ->first();

        if ($student) {
            $absensiHistory = DB::table('absensi')
                ->where('id_siswa', $student->nis)
                ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
                ->orderByDesc('tanggal')
                ->get()
                ->map(function (object $row): object {
                    $row->status_label = match ((int) $row->status) {
                        1 => 'Hadir',
                        2 => 'Izin',
                        3 => 'Sakit',
                        default => 'Tanpa Keterangan',
                    };
                    $row->jam_datang_label = $row->jam_datang ? date('H:i', strtotime((string) $row->jam_datang)) : '-';
                    $row->jam_pulang_label = $row->jam_pulang ? date('H:i', strtotime((string) $row->jam_pulang)) : '-';

                    return $row;
                });

            $agendaHistory = DB::table('agenda')
                ->where('id_siswa', $student->nis)
                ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
                ->orderByDesc('tanggal')
                ->get();

            return view('dashboard.student', [
                'student' => $student,
                'absensiHistory' => $absensiHistory,
                'agendaHistory' => $agendaHistory,
            ]);
        }

        return view('dashboard');
    }
}
