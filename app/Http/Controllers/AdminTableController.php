<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminTableController extends Controller
{
    public function show(Request $request, string $module): View
    {
        $definition = $this->definitions()[$module] ?? null;

        if (! $definition) {
            throw new NotFoundHttpException();
        }

        if (($definition['type'] ?? 'table') === 'placeholder') {
            return view('admin.placeholder', [
                'pageTitle' => $definition['title'],
                'pageDescription' => $definition['description'],
            ]);
        }

        $search = trim((string) $request->query('search', ''));
        $perPage = max(5, min(50, (int) $request->query('per_page', 10) ?: 10));
        $sort = (string) $request->query('sort', $definition['default_sort']);
        $direction = strtolower((string) $request->query('direction', $definition['default_direction']));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : $definition['default_direction'];
        $filterDefinitions = $this->buildFilters($definition['filters'] ?? []);
        $filters = $this->resolveFilters($request, $filterDefinitions);

        if (! array_key_exists($sort, $definition['sorts'])) {
            $sort = $definition['default_sort'];
            $direction = $definition['default_direction'];
        }

        $query = $this->{$definition['query']}();
        $this->applySearch($query, $definition['search_columns'], $search);
        $this->applyFilters($query, $filters, $filterDefinitions);
        $query->orderBy($definition['sorts'][$sort], $direction);

        if ($sort !== $definition['default_sort']) {
            $query->orderBy($definition['sorts'][$definition['default_sort']], $definition['default_direction']);
        }

        $rows = $query->paginate($perPage)->withQueryString();
        $startNumber = $rows->firstItem() ?? 1;
        $rows->setCollection($rows->getCollection()->values()->map(
            fn (object $row, int $index) => ['no' => $startNumber + $index] + $this->{$definition['transformer']}($row)
        ));

        return view('admin.table', [
            'pageTitle' => $definition['title'],
            'pageDescription' => $definition['description'],
            'columns' => $definition['columns'],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filterDefinitions,
            'filterValues' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    private function definitions(): array
    {
        return [
            'users' => [
                'title' => 'Users',
                'description' => 'Data akun pengguna berdasarkan tabel users.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                    ['key' => 'role_name', 'label' => 'Role', 'sortable' => false],
                    ['key' => 'password_changed_at', 'label' => 'Password Changed At', 'sortable' => true],
                    ['key' => 'created_at', 'label' => 'Created At', 'sortable' => true],
                    ['key' => 'updated_at', 'label' => 'Updated At', 'sortable' => true],
                    ['key' => 'deleted_at', 'label' => 'Deleted At', 'sortable' => true],
                ],
                'query' => 'usersQuery',
                'transformer' => 'usersRow',
                'search_columns' => ['users.name'],
                'sorts' => [
                    'id_user' => 'users.id_user',
                    'name' => 'users.name',
                    'role_name' => 'role.role',
                    'password_changed_at' => 'users.password_changed_at',
                    'created_at' => 'users.created_at',
                    'updated_at' => 'users.updated_at',
                    'deleted_at' => 'users.deleted_at',
                ],
                'filters' => [
                    [
                        'key' => 'role',
                        'label' => 'Role',
                        'column' => 'role.id_role',
                        'options' => 'roleFilterOptions',
                    ],
                ],
                'default_sort' => 'id_user',
                'default_direction' => 'asc',
            ],
            'absensi' => [
                'title' => 'Absensi',
                'description' => 'Data absensi siswa dari tabel absensi.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'student_name', 'label' => 'Nama Siswa', 'sortable' => true],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'sortable' => true],
                    ['key' => 'jam_datang', 'label' => 'Jam Datang', 'sortable' => true],
                    ['key' => 'jam_pulang', 'label' => 'Jam Pulang', 'sortable' => true],
                    ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                    ['key' => 'keterangan', 'label' => 'Keterangan', 'sortable' => false],
                    ['key' => 'foto_bukti', 'label' => 'Foto Bukti', 'sortable' => false],
                ],
                'query' => 'absensiQuery',
                'transformer' => 'absensiRow',
                'search_columns' => ['siswa.nama_siswa', 'absensi.keterangan'],
                'sorts' => [
                    'id_absensi' => 'absensi.id_absensi',
                    'id_siswa' => 'absensi.id_siswa',
                    'student_name' => 'siswa.nama_siswa',
                    'tanggal' => 'absensi.tanggal',
                    'jam_datang' => 'absensi.jam_datang',
                    'jam_pulang' => 'absensi.jam_pulang',
                    'status' => 'absensi.status',
                ],
                'default_sort' => 'tanggal',
                'default_direction' => 'desc',
            ],
            'agenda' => [
                'title' => 'Agenda',
                'description' => 'Gabungan data agenda dan penilaian, termasuk status approval pembimbing dan instruktur.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'student_name', 'label' => 'Nama Siswa', 'sortable' => true],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'sortable' => true],
                    ['key' => 'rencana_pekerjaan', 'label' => 'Rencana', 'sortable' => false],
                    ['key' => 'realisasi_pekerjaan', 'label' => 'Realisasi', 'sortable' => false],
                    ['key' => 'penugasan_khusus', 'label' => 'Penugasan Khusus', 'sortable' => false],
                    ['key' => 'penemuan_masalah', 'label' => 'Penemuan Masalah', 'sortable' => false],
                    ['key' => 'catatan', 'label' => 'Catatan', 'sortable' => false],
                    ['key' => 'approval_instruktur', 'label' => 'Approval Instruktur', 'sortable' => true],
                    ['key' => 'approval_pembimbing', 'label' => 'Approval Pembimbing', 'sortable' => true],
                    ['key' => 'senyum', 'label' => 'Senyum', 'sortable' => true],
                    ['key' => 'keramahan', 'label' => 'Keramahan', 'sortable' => true],
                    ['key' => 'penampilan', 'label' => 'Penampilan', 'sortable' => true],
                    ['key' => 'komunikasi', 'label' => 'Komunikasi', 'sortable' => true],
                    ['key' => 'realisasi_kerja', 'label' => 'Realisasi Kerja', 'sortable' => true],
                ],
                'query' => 'agendaQuery',
                'transformer' => 'agendaRow',
                'search_columns' => ['siswa.nama_siswa'],
                'sorts' => [
                    'id_agenda' => 'agenda.id_agenda',
                    'student_name' => 'siswa.nama_siswa',
                    'tanggal' => 'agenda.tanggal',
                    'approval_instruktur' => 'agenda.id_instruktur',
                    'approval_pembimbing' => 'agenda.id_pembimbing',
                    'senyum' => 'penilaian.senyum',
                    'keramahan' => 'penilaian.keramahan',
                    'penampilan' => 'penilaian.penampilan',
                    'komunikasi' => 'penilaian.komunikasi',
                    'realisasi_kerja' => 'penilaian.realisasi_kerja',
                ],
                'default_sort' => 'tanggal',
                'default_direction' => 'desc',
            ],
            'siswa' => [
                'title' => 'Siswa',
                'description' => 'Data siswa.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_siswa', 'label' => 'Nama Siswa', 'sortable' => true],
                    ['key' => 'kelas', 'label' => 'Kelas', 'sortable' => true],
                    ['key' => 'nama_jurusan', 'label' => 'Jurusan', 'sortable' => true],
                    ['key' => 'nama_rombel', 'label' => 'Rombel', 'sortable' => true],
                    ['key' => 'tahun_ajaran', 'label' => 'Tahun Ajaran', 'sortable' => true],
                    ['key' => 'nama_perusahaan', 'label' => 'Tempat PKL', 'sortable' => true],
                    ['key' => 'nama_instruktur', 'label' => 'Instrukur', 'sortable' => true],
                    ['key' => 'nama_pembimbing', 'label' => 'Pembimbing', 'sortable' => true],
                ],
                'query' => 'siswaQuery',
                'transformer' => 'siswaRow',
                'search_columns' => ['siswa.nama_siswa', 'jurusan.nama_jurusan', 'rombel.nama_rombel', 'tempat_pkl.nama_perusahaan'],
                'sorts' => [
                    'nis' => 'siswa.nis',
                    'nama_siswa' => 'siswa.nama_siswa',
                    'kelas' => 'kelas.kelas',
                    'nama_jurusan' => 'jurusan.nama_jurusan',
                    'nama_rombel' => 'rombel.nama_rombel',
                    'tahun_ajaran' => 'siswa.tahun_ajaran',
                    'nama_perusahaan' => 'tempat_pkl.nama_perusahaan',
                    'nama_instruktur' => 'instruktur.nama_instruktur',
                    'nama_pembimbing' => 'pembimbing.nama_pembimbing',
                ],
                'default_sort' => 'nis',
                'default_direction' => 'asc',
            ],
            'instruktur' => [
                'title' => 'Instruktur',
                'description' => 'Data instruktur dari tabel instruktur.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_instruktur', 'label' => 'Nama Instruktur', 'sortable' => true],
                    ['key' => 'nama_perusahaan', 'label' => 'Nama Perusahaan', 'sortable' => true],
                ],
                'query' => 'instrukturQuery',
                'transformer' => 'instrukturRow',
                'search_columns' => ['instruktur.nama_instruktur', 'tempat_pkl.nama_perusahaan'],
                'sorts' => [
                    'id_instruktur' => 'instruktur.id_instruktur',
                    'nama_instruktur' => 'instruktur.nama_instruktur',
                    'id_tempat' => 'instruktur.id_tempat',
                    'nama_perusahaan' => 'tempat_pkl.nama_perusahaan',
                ],
                'default_sort' => 'id_instruktur',
                'default_direction' => 'asc',
            ],
            'pembimbing' => [
                'title' => 'Pembimbing',
                'description' => 'Data pembimbing dari tabel pembimbing.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'user_name', 'label' => 'Nama User', 'sortable' => true],
                    ['key' => 'nama_pembimbing', 'label' => 'Nama Pembimbing', 'sortable' => true],
                ],
                'query' => 'pembimbingQuery',
                'transformer' => 'pembimbingRow',
                'search_columns' => ['users.name', 'pembimbing.nama_pembimbing'],
                'sorts' => [
                    'id_pembimbing' => 'pembimbing.id_pembimbing',
                    'user_name' => 'users.name',
                    'nama_pembimbing' => 'pembimbing.nama_pembimbing',
                ],
                'default_sort' => 'id_pembimbing',
                'default_direction' => 'asc',
            ],
            'kajur' => [
                'title' => 'Kajur',
                'description' => 'Data kepala jurusan dari tabel kajur.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'user_name', 'label' => 'Nama User', 'sortable' => true],
                    ['key' => 'nama_kajur', 'label' => 'Nama Kajur', 'sortable' => true],
                ],
                'query' => 'kajurQuery',
                'transformer' => 'kajurRow',
                'search_columns' => ['users.name', 'kajur.nama_kajur'],
                'sorts' => [
                    'id_kajur' => 'kajur.id_kajur',
                    'user_name' => 'users.name',
                    'nama_kajur' => 'kajur.nama_kajur',
                ],
                'default_sort' => 'id_kajur',
                'default_direction' => 'asc',
            ],
            'rombel' => [
                'title' => 'Rombel',
                'description' => 'Data rombel dari tabel rombel.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_rombel', 'label' => 'Nama Rombel', 'sortable' => true],
                    ['key' => 'wali_name', 'label' => 'Nama Wali', 'sortable' => true],
                ],
                'query' => 'rombelQuery',
                'transformer' => 'rombelRow',
                'search_columns' => ['rombel.nama_rombel', 'users.name'],
                'sorts' => [
                    'id_rombel' => 'rombel.id_rombel',
                    'nama_rombel' => 'rombel.nama_rombel',
                    'wali_name' => 'users.name',
                ],
                'default_sort' => 'id_rombel',
                'default_direction' => 'asc',
            ],
            'tempat-pkl' => [
                'title' => 'Tempat PKL',
                'description' => 'Data tempat PKL dari tabel tempat_pkl.',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_perusahaan', 'label' => 'Nama Perusahaan', 'sortable' => true],
                    ['key' => 'alamat', 'label' => 'Alamat', 'sortable' => true],
                ],
                'query' => 'tempatPklQuery',
                'transformer' => 'tempatPklRow',
                'search_columns' => ['tempat_pkl.nama_perusahaan', 'tempat_pkl.alamat'],
                'sorts' => [
                    'id_tempat' => 'tempat_pkl.id_tempat',
                    'nama_perusahaan' => 'tempat_pkl.nama_perusahaan',
                    'alamat' => 'tempat_pkl.alamat',
                ],
                'default_sort' => 'id_tempat',
                'default_direction' => 'asc',
            ],
            'web-setting' => [
                'title' => 'Web Setting',
                'description' => 'Halaman pengaturan web masih placeholder.',
                'type' => 'placeholder',
            ],
            'backup-database' => [
                'title' => 'Backup Database',
                'description' => 'Halaman backup database masih placeholder.',
                'type' => 'placeholder',
            ],
        ];
    }

    private function applySearch(Builder $query, array $searchColumns, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($searchColumns, $search): void {
            foreach ($searchColumns as $index => $column) {
                if ($index === 0) {
                    $searchQuery->where($column, 'like', "%{$search}%");
                    continue;
                }

                $searchQuery->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    private function buildFilters(array $filters): array
    {
        return array_map(function (array $filter): array {
            if (isset($filter['options']) && is_string($filter['options'])) {
                $filter['options'] = $this->{$filter['options']}();
            }

            return $filter;
        }, $filters);
    }

    private function resolveFilters(Request $request, array $filters): array
    {
        $resolved = [];

        foreach ($filters as $filter) {
            $key = $filter['key'];
            $resolved[$key] = trim((string) $request->query($key, ''));
        }

        return $resolved;
    }

    private function applyFilters(Builder $query, array $values, array $filters): void
    {
        foreach ($filters as $filter) {
            $key = $filter['key'];
            $value = $values[$key] ?? '';

            if ($value === '') {
                continue;
            }

            $query->where($filter['column'], $value);
        }
    }

    private function roleFilterOptions(): array
    {
        return DB::table('role')
            ->orderBy('role')
            ->get(['id_role', 'role'])
            ->map(fn (object $role) => [
                'value' => (string) $role->id_role,
                'label' => $role->role,
            ])
            ->all();
    }

    private function usersQuery(): Builder
    {
        return DB::table('users')
            ->leftJoin('role', 'users.role', '=', 'role.id_role')
            ->select([
                'users.id_user',
                'users.name',
                'role.role as role_name',
                'users.password_changed_at',
                'users.created_at',
                'users.updated_at',
                'users.deleted_at',
            ]);
    }

    private function absensiQuery(): Builder
    {
        return DB::table('absensi')
            ->leftJoin('siswa', 'absensi.id_siswa', '=', 'siswa.nis')
            ->select([
                'absensi.id_absensi',
                'siswa.nama_siswa as student_name',
                'absensi.tanggal',
                'absensi.jam_datang',
                'absensi.jam_pulang',
                'absensi.status',
                'absensi.keterangan',
                'absensi.foto_bukti',
            ]);
    }

    private function agendaQuery(): Builder
    {
        return DB::table('agenda')
            ->leftJoin('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->leftJoin('penilaian', 'agenda.id_agenda', '=', 'penilaian.id_agenda')
            ->select([
                'agenda.id_agenda',
                'siswa.nama_siswa as student_name',
                'agenda.tanggal',
                'agenda.rencana_pekerjaan',
                'agenda.realisasi_pekerjaan',
                'agenda.penugasan_khusus_dari_atasan as penugasan_khusus',
                'agenda.penemuan_masalah',
                'agenda.catatan',
                'agenda.id_instruktur',
                'agenda.id_pembimbing',
                'penilaian.senyum',
                'penilaian.keramahan',
                'penilaian.penampilan',
                'penilaian.komunikasi',
                'penilaian.realisasi_kerja',
            ]);
    }

    private function siswaQuery(): Builder
    {
        return DB::table('siswa')
            ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->leftJoin('jurusan', 'siswa.id_jurusan', '=', 'jurusan.id_jurusan')
            ->leftJoin('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
            ->leftJoin('tempat_pkl', 'siswa.id_tempat', '=', 'tempat_pkl.id_tempat')
            ->leftJoin('pembimbing', 'siswa.id_pembimbing', '=', 'pembimbing.id_pembimbing')
            ->select([
                'siswa.nis',
                'siswa.nama_siswa',
                'kelas.kelas',
                'jurusan.nama_jurusan',
                'rombel.nama_rombel',
                'siswa.tahun_ajaran',
                'tempat_pkl.nama_perusahaan',
                'pembimbing.nama_pembimbing',
            ]);
    }

    private function instrukturQuery(): Builder
    {
        return DB::table('instruktur')
            ->leftJoin('tempat_pkl', 'instruktur.id_tempat', '=', 'tempat_pkl.id_tempat')
            ->select([
                'instruktur.id_instruktur',
                'instruktur.nama_instruktur',
                'tempat_pkl.nama_perusahaan',
            ]);
    }

    private function pembimbingQuery(): Builder
    {
        return DB::table('pembimbing')
            ->leftJoin('users', 'pembimbing.id_user', '=', 'users.id_user')
            ->select([
                'pembimbing.id_pembimbing',
                'users.name as user_name',
                'pembimbing.nama_pembimbing',
            ]);
    }

    private function kajurQuery(): Builder
    {
        return DB::table('kajur')
            ->leftJoin('users', 'kajur.id_user', '=', 'users.id_user')
            ->select([
                'kajur.id_kajur',
                'users.name as user_name',
                'kajur.nama_kajur',
            ]);
    }

    private function rombelQuery(): Builder
    {
        return DB::table('rombel')
            ->leftJoin('users', 'rombel.id_wali', '=', 'users.id_user')
            ->select([
                'rombel.id_rombel',
                'rombel.nama_rombel',
                'users.name as wali_name',
            ]);
    }

    private function tempatPklQuery(): Builder
    {
        return DB::table('tempat_pkl')
            ->select([
                'tempat_pkl.id_tempat',
                'tempat_pkl.nama_perusahaan',
                'tempat_pkl.alamat',
            ]);
    }

    private function usersRow(object $row): array
    {
        return [
            'id_user' => $row->id_user,
            'name' => $row->name,
            'role_name' => $row->role_name,
            'password_changed_at' => $row->password_changed_at,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'deleted_at' => $row->deleted_at,
        ];
    }

    private function absensiRow(object $row): array
    {
        return [
            'id_absensi' => $row->id_absensi,
            'student_name' => $row->student_name,
            'tanggal' => $row->tanggal,
            'jam_datang' => $row->jam_datang,
            'jam_pulang' => $row->jam_pulang,
            'status' => $row->status,
            'keterangan' => $row->keterangan,
            'foto_bukti' => $row->foto_bukti,
        ];
    }

    private function agendaRow(object $row): array
    {
        return [
            'id_agenda' => $row->id_agenda,
            'student_name' => $row->student_name,
            'tanggal' => $row->tanggal,
            'rencana_pekerjaan' => $row->rencana_pekerjaan,
            'realisasi_pekerjaan' => $row->realisasi_pekerjaan,
            'penugasan_khusus' => $row->penugasan_khusus,
            'penemuan_masalah' => $row->penemuan_masalah,
            'catatan' => $row->catatan,
            'approval_instruktur' => $row->id_instruktur ? 'Approved' : 'Waiting for approval',
            'approval_pembimbing' => $row->id_pembimbing ? 'Approved' : 'Waiting for approval',
            'senyum' => $this->ratingLabel($row->senyum),
            'keramahan' => $this->ratingLabel($row->keramahan),
            'penampilan' => $this->ratingLabel($row->penampilan),
            'komunikasi' => $this->ratingLabel($row->komunikasi),
            'realisasi_kerja' => $this->ratingLabel($row->realisasi_kerja),
        ];
    }

    private function siswaRow(object $row): array
    {
        return [
            'nis' => $row->nis,
            'nama_siswa' => $row->nama_siswa,
            'kelas' => $row->kelas,
            'nama_jurusan' => $row->nama_jurusan,
            'nama_rombel' => $row->nama_rombel,
            'tahun_ajaran' => $row->tahun_ajaran,
            'nama_perusahaan' => $row->nama_perusahaan,
            'nama_pembimbing' => $row->nama_pembimbing,
        ];
    }

    private function instrukturRow(object $row): array
    {
        return [
            'id_instruktur' => $row->id_instruktur,
            'nama_instruktur' => $row->nama_instruktur,
            'nama_perusahaan' => $row->nama_perusahaan,
        ];
    }

    private function pembimbingRow(object $row): array
    {
        return [
            'id_pembimbing' => $row->id_pembimbing,
            'user_name' => $row->user_name,
            'nama_pembimbing' => $row->nama_pembimbing,
        ];
    }

    private function kajurRow(object $row): array
    {
        return [
            'id_kajur' => $row->id_kajur,
            'user_name' => $row->user_name,
            'nama_kajur' => $row->nama_kajur,
        ];
    }

    private function rombelRow(object $row): array
    {
        return [
            'id_rombel' => $row->id_rombel,
            'nama_rombel' => $row->nama_rombel,
            'wali_name' => $row->wali_name,
        ];
    }

    private function tempatPklRow(object $row): array
    {
        return [
            'id_tempat' => $row->id_tempat,
            'nama_perusahaan' => $row->nama_perusahaan,
            'alamat' => $row->alamat,
        ];
    }

    private function ratingLabel(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value === 1 ? 'Baik' : 'Kurang';
    }
}
