<?php

namespace App\Http\Controllers;

use App\Services\AccessControlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AccessControlService $accessControl)
    {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();
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

        return view('dashboard', [
            'dashboardData' => $this->buildAdminDashboardData(),
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        abort_if($this->isStudentUser($request->user()), 403);

        return response()->json($this->buildAdminDashboardData());
    }

    private function buildAdminDashboardData(): array
    {
        $today = Carbon::today();
        $fromDate = $today->copy()->subDays(6)->toDateString();
        $toDate = $today->toDateString();
        $totalStudents = DB::table('siswa')->count();

        $attendanceRows = DB::table('absensi')
            ->selectRaw('tanggal, status, COUNT(*) as total')
            ->whereBetween('tanggal', [$fromDate, $toDate])
            ->groupBy('tanggal', 'status')
            ->orderBy('tanggal')
            ->get()
            ->groupBy(fn (object $row) => Carbon::parse($row->tanggal)->toDateString());

        $trend = collect(range(6, 0))
            ->map(function (int $offset) use ($today, $attendanceRows, $totalStudents): array {
                $date = $today->copy()->subDays($offset);
                $key = $date->toDateString();
                $rows = $attendanceRows->get($key, collect());

                $presentRow = $rows->firstWhere('status', 1);
                $izinRow = $rows->firstWhere('status', 2);
                $sakitRow = $rows->firstWhere('status', 3);
                $alphaRow = $rows->firstWhere('status', 4);

                $hadir = (int) ($presentRow->total ?? 0);
                $izin = (int) ($izinRow->total ?? 0);
                $sakit = (int) ($sakitRow->total ?? 0);
                $alpha = max($totalStudents - $hadir - $izin - $sakit, 0);
                $tidakHadir = $izin + $sakit + $alpha;

                return [
                    'date' => $key,
                    'label' => $date->locale('id')->translatedFormat('D'),
                    'full_label' => $date->translatedFormat('d M'),
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alpha' => $alpha,
                    'tidak_hadir' => $tidakHadir,
                ];
            })
            ->values();

        $todayStats = $trend->last() ?: [
            'hadir' => 0,
            'izin' => 0,
            'alpha' => 0,
            'tidak_hadir' => 0,
        ];

        $attendanceRate = $totalStudents > 0
            ? round(((int) $todayStats['hadir'] / $totalStudents) * 100, 1)
            : 0;

        return [
            'summary' => [
                'total_students' => $totalStudents,
                'hadir' => (int) $todayStats['hadir'],
                'izin' => (int) $todayStats['izin'],
                'alpha' => (int) $todayStats['alpha'],
                'tidak_hadir' => (int) $todayStats['tidak_hadir'],
                'attendance_rate' => $attendanceRate,
                'date_label' => $today->locale('id')->translatedFormat('d F Y'),
                'updated_at' => now()->locale('id')->translatedFormat('H:i:s'),
            ],
            'trend' => $trend->all(),
        ];
    }

    private function isStudentUser(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        return DB::table('siswa')
            ->where('id_user', $user->id_user)
            ->exists();
    }
}
