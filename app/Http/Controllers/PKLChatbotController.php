<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PKLChatbotController extends Controller
{
    private const STATUS_LABELS = [
        1 => 'Hadir',
        2 => 'Izin',
        3 => 'Sakit',
        4 => 'Alpha',
    ];

    public function stats(): JsonResponse
    {
        return response()->json($this->getTodayStats());
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'string'],
            'history.*.content' => ['required_with:history', 'string', 'max:1500'],
        ]);

        $apiKey = (string) config('services.groq.key');

        if ($apiKey === '') {
            return response()->json([
                'error' => 'GROQ_API_KEY belum terpasang di konfigurasi aplikasi.',
            ], 500);
        }

        $roleId = (int) $request->user()->role;
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($roleId)],
        ];

        foreach ($validated['history'] ?? [] as $item) {
            if (! in_array($item['role'], ['user', 'assistant'], true)) {
                continue;
            }

            $messages[] = [
                'role' => $item['role'],
                'content' => $item['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->withToken($apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model', 'llama-3.3-70b-versatile'),
                'temperature' => 0.2,
                'max_tokens' => 900,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?: 'Gagal menghubungi layanan AI.';

            return response()->json(['error' => $message], 500);
        }

        $reply = trim((string) $response->json('choices.0.message.content'));

        if ($reply === '') {
            return response()->json([
                'error' => 'AI tidak mengembalikan jawaban yang bisa diproses.',
            ], 500);
        }

        return response()->json(['reply' => $reply]);
    }

    private function getTodayStats(): array
    {
        $today = Carbon::today()->toDateString();
        $rows = $this->attendanceBaseQuery()
            ->whereDate('absensi.tanggal', $today)
            ->get();

        $totalStudents = DB::table('siswa')->count();
        $hadir = $rows->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'present')->count();
        $tidakHadir = $rows->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'absent')->count();
        $persentase = $totalStudents > 0 ? round(($hadir / $totalStudents) * 100, 1) : 0;

        return [
            'total' => $totalStudents,
            'hadir' => $hadir,
            'absen' => $tidakHadir,
            'pct' => $persentase,
            'date_label' => Carbon::today()->locale('id')->translatedFormat('d F Y'),
        ];
    }

    private function buildSystemPrompt(int $roleId): string
    {
        $now = Carbon::now()->locale('id');
        $context = $this->buildDatabaseContext($roleId);
        $scopeInstruction = $this->scopeInstruction($roleId);

        return <<<PROMPT
Kamu adalah asisten chatbot untuk sistem absensi PKL sekolah.
Waktu saat ini: {$now->translatedFormat('l, d F Y H:i')}

Kamu hanya boleh menjawab berdasarkan data ringkasan yang diberikan. Jangan mengarang data.
Jawab dalam Bahasa Indonesia yang singkat, jelas, dan rapi.
Kalau ada daftar nama, gunakan bullet list.
Jika pengguna meminta data yang tidak tersedia, katakan datanya belum tersedia.
{$scopeInstruction}

Penting tentang status absensi:
- Status 1 dibaca sebagai Hadir.
- Status 2 dibaca sebagai Izin.
- Status 3 dibaca sebagai Sakit.
- Status 4 dibaca sebagai Alpha.
- Jika ada angka status lain, sebut sebagai "Status <angka>" dan jangan menebak artinya.

Berikut konteks data terbaru dari database:

{$context}
PROMPT;
    }

    private function buildDatabaseContext(int $roleId): string
    {
        if ($roleId === 8) {
            return implode("\n\n", [
                $this->buildGlobalSchemaContext(false),
                $this->buildAttendanceContext(),
                $this->buildAgendaContext(),
            ]);
        }

        if ($roleId === 7) {
            return implode("\n\n", [
                $this->buildGlobalSchemaContext(true),
                $this->buildAttendanceContext(),
                $this->buildAgendaContext(),
            ]);
        }

        return implode("\n\n", [
            $this->buildAttendanceContext(),
            $this->buildAgendaContext(),
        ]);
    }

    private function buildAttendanceContext(): string
    {
        $today = Carbon::today();
        $lines = [];
        $todayRows = $this->attendanceBaseQuery()
            ->whereDate('absensi.tanggal', $today->toDateString())
            ->orderBy('siswa.nama_siswa')
            ->get();

        $lines[] = '=== RINGKASAN HARI INI ===';
        $lines[] = 'Tanggal: ' . $today->locale('id')->translatedFormat('l, d F Y');
        $lines[] = 'Total siswa: ' . DB::table('siswa')->count();
        $lines[] = 'Total record absensi hari ini: ' . $todayRows->count();
        $lines[] = 'Hadir hari ini: ' . $todayRows->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'present')->count();
        $lines[] = 'Tidak hadir hari ini: ' . $todayRows->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'absent')->count();
        $lines[] = '';
        $lines[] = 'Detail absensi hari ini:';
        $lines = array_merge($lines, $this->formatAttendanceLines($todayRows, 20));

        $lines[] = '';
        $lines[] = '=== REKAP 7 HARI TERAKHIR ===';
        $trendRows = $this->attendanceBaseQuery()
            ->whereBetween('absensi.tanggal', [
                $today->copy()->subDays(6)->toDateString(),
                $today->toDateString(),
            ])
            ->orderBy('absensi.tanggal')
            ->get()
            ->groupBy(fn (object $row) => Carbon::parse($row->tanggal)->toDateString());

        if ($trendRows->isEmpty()) {
            $lines[] = '- Belum ada data absensi dalam 7 hari terakhir.';
        } else {
            foreach ($trendRows as $date => $items) {
                $hadir = $items->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'present')->count();
                $tidakHadir = $items->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'absent')->count();
                $lines[] = '- ' . Carbon::parse($date)->locale('id')->translatedFormat('d M Y') . ": hadir {$hadir}, tidak hadir {$tidakHadir}, total record {$items->count()}";
            }
        }

        $lines[] = '';
        $lines[] = '=== REKAP BULAN INI PER SISWA ===';
        $monthlyRows = $this->attendanceBaseQuery()
            ->whereYear('absensi.tanggal', $today->year)
            ->whereMonth('absensi.tanggal', $today->month)
            ->orderBy('siswa.nama_siswa')
            ->get()
            ->groupBy('nama_siswa');

        if ($monthlyRows->isEmpty()) {
            $lines[] = '- Belum ada data absensi bulan ini.';
        } else {
            foreach ($monthlyRows->take(30) as $studentName => $items) {
                $hadir = $items->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'present')->count();
                $tidakHadir = $items->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'absent')->count();
                $persentase = $items->count() > 0 ? round(($hadir / $items->count()) * 100, 1) : 0;
                $lines[] = "- {$studentName} (NIS {$items->first()->nis}): hadir {$hadir}, tidak hadir {$tidakHadir}, kehadiran {$persentase}%";
            }
        }

        $lines[] = '';
        $lines[] = '=== SISWA PALING SERING TIDAK HADIR BULAN INI ===';
        $topAbsentees = $monthlyRows
            ->map(function (Collection $items, string $studentName): array {
                $tidakHadir = $items->filter(fn (object $row) => $this->statusCategory((int) $row->status) === 'absent')->count();

                return [
                    'name' => $studentName,
                    'nis' => $items->first()->nis,
                    'count' => $tidakHadir,
                ];
            })
            ->filter(fn (array $item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->take(10)
            ->values();

        if ($topAbsentees->isEmpty()) {
            $lines[] = '- Tidak ada data siswa tidak hadir bulan ini.';
        } else {
            foreach ($topAbsentees as $index => $student) {
                $rank = $index + 1;
                $lines[] = "{$rank}. {$student['name']} (NIS {$student['nis']}): {$student['count']} kali";
            }
        }

        $lines[] = '';
        $lines[] = '=== CATATAN ===';
        $lines[] = '- Data diambil dari tabel siswa dan absensi.';
        $lines[] = '- Jika pengguna menanyakan tanggal spesifik, prioritaskan data 7 hari terakhir jika tersedia.';
        $lines[] = '- Jika nama siswa tidak ada di konteks, katakan tidak ditemukan pada data ringkasan saat ini.';

        return implode("\n", $lines);
    }

    private function buildAgendaContext(): string
    {
        $today = Carbon::today();
        $lines = [];

        $agendaRows = DB::table('agenda')
            ->join('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->leftJoin('penilaian', 'agenda.id_agenda', '=', 'penilaian.id_agenda')
            ->select([
                'agenda.id_agenda',
                'agenda.tanggal',
                'agenda.rencana_pekerjaan',
                'agenda.realisasi_pekerjaan',
                'agenda.penugasan_khusus_dari_atasan',
                'agenda.penemuan_masalah',
                'agenda.catatan',
                'agenda.id_instruktur',
                'agenda.id_pembimbing',
                'siswa.nis',
                'siswa.nama_siswa',
                'penilaian.senyum',
                'penilaian.keramahan',
                'penilaian.penampilan',
                'penilaian.komunikasi',
                'penilaian.realisasi_kerja',
            ])
            ->whereBetween('agenda.tanggal', [
                $today->copy()->subDays(7)->toDateString(),
                $today->toDateString(),
            ])
            ->orderByDesc('agenda.tanggal')
            ->orderBy('siswa.nama_siswa')
            ->get();

        $lines[] = '=== RINGKASAN AGENDA DAN PENILAIAN ===';
        $lines[] = 'Total agenda 7 hari terakhir: ' . $agendaRows->count();
        $lines[] = 'Agenda dengan penilaian: ' . $agendaRows->filter(fn (object $row) => $this->hasAssessment($row))->count();
        $lines[] = 'Agenda approved instruktur: ' . $agendaRows->whereNotNull('id_instruktur')->count();
        $lines[] = 'Agenda approved pembimbing: ' . $agendaRows->whereNotNull('id_pembimbing')->count();
        $lines[] = '';
        $lines[] = 'Contoh detail agenda terbaru:';

        if ($agendaRows->isEmpty()) {
            $lines[] = '- Belum ada data agenda dalam 7 hari terakhir.';
        } else {
            foreach ($agendaRows->take(15) as $row) {
                $lines[] = '- ' . $row->nama_siswa . ' (NIS ' . $row->nis . ', ' . Carbon::parse($row->tanggal)->locale('id')->translatedFormat('d M Y') . ')'
                    . ': rencana=' . $this->textOrDash($row->rencana_pekerjaan)
                    . '; realisasi=' . $this->textOrDash($row->realisasi_pekerjaan)
                    . '; catatan=' . $this->textOrDash($row->catatan)
                    . '; approval instruktur=' . ($row->id_instruktur ? 'ya' : 'belum')
                    . '; approval pembimbing=' . ($row->id_pembimbing ? 'ya' : 'belum')
                    . '; penilaian=' . $this->formatAssessmentSummary($row);
            }
        }

        return implode("\n", $lines);
    }

    private function buildGlobalSchemaContext(bool $hideSuperadmin): string
    {
        $lines = [];
        $tableNames = [
            'role',
            'users',
            'kelas',
            'kajur',
            'jurusan',
            'pembimbing',
            'instruktur',
            'rombel',
            'siswa',
            'absensi',
            'agenda',
            'penilaian',
        ];

        $lines[] = '=== RINGKASAN DATABASE ===';

        foreach ($tableNames as $tableName) {
            $lines[] = '- Tabel ' . $tableName . ': ' . DB::table($tableName)->count() . ' baris';
        }

        $lines[] = '';
        $lines[] = '=== STRUKTUR DAN RELASI UTAMA ===';
        $lines[] = '- users menyimpan akun dan terhubung ke role.';
        $lines[] = '- siswa menyimpan data siswa PKL dan terhubung ke kelas, jurusan, rombel, pembimbing, dan instruktur.';
        $lines[] = '- absensi menyimpan kehadiran per siswa per tanggal.';
        $lines[] = '- agenda menyimpan aktivitas harian siswa PKL.';
        $lines[] = '- penilaian menyimpan evaluasi untuk agenda.';
        $lines[] = '- kajur dan pembimbing terhubung ke users.';

        $lines[] = '';
        $lines[] = '=== DATA ROLE YANG BOLEH DIKETAHUI ===';
        $roleRows = DB::table('role')
            ->when($hideSuperadmin, fn ($query) => $query->where('id_role', '<>', 8))
            ->orderBy('id_role')
            ->get(['id_role', 'role']);

        foreach ($roleRows as $role) {
            $lines[] = '- Role ' . $role->id_role . ': ' . $role->role;
        }

        $lines[] = '';
        $lines[] = '=== SAMPEL DATA MASTER ===';

        $lines[] = '';
        $lines[] = 'Daftar user yang tersedia:';
        $userSamples = DB::table('users')
            ->leftJoin('role', 'users.role', '=', 'role.id_role')
            ->when($hideSuperadmin, fn ($query) => $query->where('users.role', '<>', 8))
            ->orderBy('users.id_user')
            ->limit(20)
            ->get([
                'users.id_user',
                'users.name',
                'users.role',
                'role.role as role_name',
                'users.password_changed_at',
                'users.deleted_at',
            ]);

        if ($userSamples->isEmpty()) {
            $lines[] = '- Belum ada data user.';
        } else {
            foreach ($userSamples as $user) {
                $lines[] = '- User ID ' . $user->id_user
                    . ': nama=' . $this->textOrDash($user->name)
                    . ', role=' . ($user->role_name ?: 'ID ' . $user->role)
                    . ', password_changed_at=' . $this->textOrDash($user->password_changed_at)
                    . ', deleted_at=' . $this->textOrDash($user->deleted_at);
            }
        }

        $studentSamples = DB::table('siswa')
            ->leftJoin('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
            ->orderBy('siswa.nama_siswa')
            ->limit(10)
            ->get([
                'siswa.nis',
                'siswa.nama_siswa',
                'siswa.tahun_ajaran',
                'rombel.nama_rombel',
            ]);

        if ($studentSamples->isEmpty()) {
            $lines[] = '- Belum ada data siswa.';
        } else {
            foreach ($studentSamples as $student) {
                $lines[] = '- Siswa: ' . $student->nama_siswa
                    . ' (NIS ' . $student->nis
                    . ', rombel ' . $this->textOrDash($student->nama_rombel)
                    . ', tahun ajaran ' . $this->textOrDash($student->tahun_ajaran) . ')';
            }
        }

        $lines[] = '';
        $lines[] = 'Daftar pembimbing:';
        $mentorSamples = DB::table('pembimbing')
            ->leftJoin('users', 'pembimbing.id_user', '=', 'users.id_user')
            ->orderBy('pembimbing.id_pembimbing')
            ->limit(15)
            ->get(['pembimbing.id_pembimbing', 'pembimbing.nama_pembimbing', 'users.name as user_name']);

        if ($mentorSamples->isEmpty()) {
            $lines[] = '- Belum ada data pembimbing.';
        } else {
            foreach ($mentorSamples as $mentor) {
                $lines[] = '- Pembimbing ID ' . $mentor->id_pembimbing
                    . ': ' . $this->textOrDash($mentor->nama_pembimbing)
                    . ' | user=' . $this->textOrDash($mentor->user_name);
            }
        }

        $lines[] = '';
        $lines[] = 'Daftar instruktur:';
        $instructorSamples = DB::table('instruktur')
            ->orderBy('instruktur.id_instruktur')
            ->limit(15)
            ->get(['instruktur.id_instruktur', 'instruktur.nama_instruktur']);

        if ($instructorSamples->isEmpty()) {
            $lines[] = '- Belum ada data instruktur.';
        } else {
            foreach ($instructorSamples as $instructor) {
                $lines[] = '- Instruktur ID ' . $instructor->id_instruktur
                    . ': ' . $this->textOrDash($instructor->nama_instruktur);
            }
        }

        return implode("\n", $lines);
    }

    private function attendanceBaseQuery()
    {
        return DB::table('absensi')
            ->join('siswa', 'absensi.id_siswa', '=', 'siswa.nis')
            ->select([
                'absensi.id_absensi',
                'absensi.tanggal',
                'absensi.jam_datang',
                'absensi.jam_pulang',
                'absensi.status',
                'absensi.keterangan',
                'siswa.nis',
                'siswa.nama_siswa',
            ]);
    }

    private function formatAttendanceLines(Collection $rows, int $limit): array
    {
        if ($rows->isEmpty()) {
            return ['- Belum ada data absensi untuk periode ini.'];
        }

        $lines = [];

        foreach ($rows->take($limit) as $row) {
            $status = $this->statusLabel((int) $row->status);
            $keterangan = $row->keterangan ? " | ket: {$row->keterangan}" : '';
            $lines[] = "- {$row->nama_siswa} (NIS {$row->nis}): {$status}{$keterangan}";
        }

        if ($rows->count() > $limit) {
            $sisa = $rows->count() - $limit;
            $lines[] = "- dan {$sisa} data lainnya.";
        }

        return $lines;
    }

    private function statusLabel(int $status): string
    {
        return self::STATUS_LABELS[$status] ?? "Status {$status}";
    }

    private function scopeInstruction(int $roleId): string
    {
        if ($roleId === 8) {
            return 'Pengguna ini adalah role 8. Kamu boleh menjawab seluruh pertanyaan tentang data dan struktur database yang ada pada konteks. Jika pengguna meminta daftar data seperti list user, tampilkan item satu per satu berdasarkan data konkret di konteks.';
        }

        if ($roleId === 7) {
            return 'Pengguna ini adalah role 7. Kamu boleh menjawab tentang data operasional database pada konteks, tetapi kamu tidak boleh memberi tahu bahwa ada role superadmin atau role 8. Jika ditanya soal role tertinggi, jawab berdasarkan role yang terlihat pada konteks. Jika pengguna meminta daftar data seperti list user, tampilkan item satu per satu berdasarkan data konkret di konteks.';
        }

        return 'Pengguna ini bukan role 7 atau 8. Kamu hanya boleh menjawab seputar absensi, agenda, dan penilaian. Jangan membahas struktur database lain, akun, users, atau role tersembunyi. Kamu juga tidak boleh memberi tahu bahwa ada superadmin atau role 8.';
    }

    private function hasAssessment(object $row): bool
    {
        return count(array_filter([
            $row->senyum,
            $row->keramahan,
            $row->penampilan,
            $row->komunikasi,
            $row->realisasi_kerja,
        ], fn ($value) => $value !== null)) > 0;
    }

    private function formatAssessmentSummary(object $row): string
    {
        $labels = [
            'senyum' => $row->senyum,
            'keramahan' => $row->keramahan,
            'penampilan' => $row->penampilan,
            'komunikasi' => $row->komunikasi,
            'realisasi_kerja' => $row->realisasi_kerja,
        ];

        $parts = [];

        foreach ($labels as $label => $value) {
            if ($value === null) {
                continue;
            }

            $parts[] = $label . '=' . ($value === 1 ? 'Baik' : 'Kurang');
        }

        return empty($parts) ? 'belum ada' : implode(', ', $parts);
    }

    private function textOrDash(?string $value): string
    {
        $clean = trim((string) $value);

        return $clean === '' ? '-' : $clean;
    }

    private function statusCategory(int $status): string
    {
        return $status === 1 ? 'present' : 'absent';
    }
}
