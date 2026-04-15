<?php

namespace App\Http\Controllers;

use App\Services\AccessControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminTableController extends Controller
{
    public function __construct(private readonly AccessControlService $accessControl)
    {
    }

    public function show(Request $request, string $module): View
    {
        $definition = $this->definitions()[$module] ?? null;

        if (! $definition) {
            throw new NotFoundHttpException();
        }

        abort_unless($this->accessControl->canAccess($request->user(), $module), 403);

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

        $query = $this->{$definition['query']}($request);
        $this->applySearch($query, $definition['search_columns'], $search);
        $this->applyFilters($query, $filters, $filterDefinitions);
        $query->orderBy($definition['sorts'][$sort], $direction);

        if ($sort !== $definition['default_sort']) {
            $query->orderBy($definition['sorts'][$definition['default_sort']], $definition['default_direction']);
        }

        $rows = $query->paginate($perPage)->withQueryString();
        $startNumber = $rows->firstItem() ?? 1;
        
        $formDefinitions = array_map(function($field) {
            if (isset($field['options']) && is_string($field['options'])) {
                $field['options'] = $this->{$field['options']}();
            }
            return $field;
        }, $definition['form'] ?? []);

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
            'module' => $module,
            'primaryKey' => $definition['primary_key'] ?? 'id',
            'formFields' => $formDefinitions,
        ]);
    }

    public function resetPassword(int $id)
    {
        DB::table('users')
            ->where('id_user', $id)
            ->update([
                'password' => bcrypt('12345678'),
                'password_changed_at' => null,
            ]);

        return back()->with('success', 'Password user berhasil direset menjadi 12345678.');
    }

    public function store(Request $request, string $module)
    {
        $definition = $this->definitions()[$module] ?? null;
        if (!$definition) throw new NotFoundHttpException();

        $rules = $this->getValidationRules($module, false);
        $data = $request->validate($rules);

        if ($module === 'users' && isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        if (in_array($module, ['users', 'penilaian'])) {
            $data['created_at'] = now();
            $data['created_by'] = auth()->id();
        }

        DB::table($definition['table'])->insert($data);

        return back()->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, string $module, $id)
    {
        $definition = $this->definitions()[$module] ?? null;
        if (!$definition) throw new NotFoundHttpException();

        $rules = $this->getValidationRules($module, true, $id);
        $data = $request->validate($rules);

        if ($module === 'users' && !empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } elseif ($module === 'users') {
            unset($data['password']);
        }

        if (in_array($module, ['users', 'penilaian'])) {
            $data['updated_at'] = now();
            $data['updated_by'] = auth()->id();
        }

        DB::table($definition['table'])
            ->where($definition['primary_key'], $id)
            ->update($data);

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(string $module, $id)
    {
        $definition = $this->definitions()[$module] ?? null;
        if (!$definition) throw new NotFoundHttpException();

        DB::table($definition['table'])
            ->where($definition['primary_key'], $id)
            ->delete();

        return back()->with('success', 'Data berhasil dihapus.');
    }

    private function getValidationRules(string $module, bool $isUpdate, $id = null): array
    {
        $rules = [
            'users' => [
                'name' => 'required|string|max:255',
                'password' => $isUpdate ? 'nullable|string|min:8' : 'required|string|min:8',
                'role' => 'required|exists:role,id_role',
            ],
            'role' => [
                'role' => 'required|string|max:50',
            ],
            'siswa' => [
                'nis' => $isUpdate ? 'required|integer' : 'required|integer|unique:siswa,nis',
                'nama_siswa' => 'required|string|max:50',
                'id_user' => 'nullable|exists:users,id_user',
                'id_kelas' => 'required|exists:kelas,id_kelas',
                'id_jurusan' => 'required|exists:jurusan,id_jurusan',
                'id_rombel' => 'required|exists:rombel,id_rombel',
                'tahun_ajaran' => 'required|string|max:50',
                'id_tempat' => 'nullable|exists:tempat_pkl,id_tempat',
                'id_instruktur' => 'nullable|exists:instruktur,id_instruktur',
                'id_pembimbing' => 'nullable|exists:pembimbing,id_pembimbing',
            ],
            'kelas' => [
                'kelas' => 'required|integer',
            ],
            'jurusan' => [
                'id_jurusan' => $isUpdate ? 'required|integer' : 'required|integer|unique:jurusan,id_jurusan',
                'nama_jurusan' => 'required|string|max:50',
                'id_kajur' => 'required|exists:kajur,id_kajur',
            ],
            'kajur' => [
                'id_user' => 'nullable|exists:users,id_user',
                'nama_kajur' => 'required|string|max:50',
            ],
            'rombel' => [
                'nama_rombel' => 'required|string|max:50',
                'id_wali' => 'required|exists:users,id_user',
            ],
            'tempat-pkl' => [
                'nama_perusahaan' => 'required|string|max:50',
                'alamat' => 'required|string|max:255',
            ],
            'pembimbing' => [
                'id_user' => 'nullable|exists:users,id_user',
                'nama_pembimbing' => 'required|string|max:50',
            ],
            'instruktur' => [
                'nama_instruktur' => 'required|string|max:50',
                'id_tempat' => 'required|exists:tempat_pkl,id_tempat',
            ],
            'absensi' => [
                'id_siswa' => 'required|exists:siswa,nis',
                'tanggal' => 'required|date',
                'jam_datang' => 'required',
                'jam_pulang' => 'required',
                'status' => 'required|integer',
                'keterangan' => 'nullable|string|max:255',
                'foto_bukti' => 'nullable|string|max:255',
            ],
            'agenda' => [
                'id_siswa' => 'required|exists:siswa,nis',
                'tanggal' => 'required|date',
                'rencana_pekerjaan' => 'nullable|string|max:255',
                'realisasi_pekerjaan' => 'nullable|string|max:255',
                'penugasan_khusus_dari_atasan' => 'nullable|string|max:255',
                'penemuan_masalah' => 'nullable|string|max:255',
                'catatan' => 'nullable|string|max:255',
                'id_instruktur' => 'nullable|exists:instruktur,id_instruktur',
                'id_pembimbing' => 'nullable|exists:pembimbing,id_pembimbing',
            ],
            'penilaian' => [
                'id_siswa' => 'required|exists:siswa,nis',
                'id_agenda' => 'required|exists:agenda,id_agenda',
                'senyum' => 'required|in:0,1',
                'keramahan' => 'required|in:0,1',
                'penampilan' => 'required|in:0,1',
                'komunikasi' => 'required|in:0,1',
                'realisasi_kerja' => 'required|in:0,1',
            ],
        ];

        return $rules[$module] ?? [];
    }

    private function definitions(): array
    {
        return [
            'users' => [
                'title' => 'Users',
<<<<<<< HEAD
                'description' => 'Data akun pengguna berdasarkan tabel users.',
                'table' => 'users',
                'primary_key' => 'id_user',
=======
                'description' => 'Data akun pengguna.',
>>>>>>> fcd64bbc4eba1949c1d5a67236d32288a5100b0a
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'name', 'label' => 'Name', 'sortable' => true],
                    ['key' => 'role_name', 'label' => 'Role', 'sortable' => false],
                    ['key' => 'password_changed_at', 'label' => 'Password Changed At', 'sortable' => true],
                    ['key' => 'created_at', 'label' => 'Created At', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'name', 'label' => 'Nama', 'type' => 'text'],
                    ['key' => 'password', 'label' => 'Password', 'type' => 'password'],
                    ['key' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => 'roleFilterOptions'],
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
                ],
                'filters' => [
                    ['key' => 'role', 'label' => 'Role', 'column' => 'role.id_role', 'options' => 'roleFilterOptions'],
                ],
                'default_sort' => 'id_user',
                'default_direction' => 'asc',
            ],
            'role' => [
                'title' => 'Role',
                'description' => 'Daftar role pengguna.',
                'table' => 'role',
                'primary_key' => 'id_role',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'role', 'label' => 'Nama Role', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'role', 'label' => 'Nama Role', 'type' => 'text'],
                ],
                'query' => 'roleQuery',
                'transformer' => 'roleRow',
                'search_columns' => ['role.role'],
                'sorts' => ['id_role' => 'role.id_role', 'role' => 'role.role'],
                'default_sort' => 'id_role',
                'default_direction' => 'asc',
            ],
            'siswa' => [
                'title' => 'Siswa',
                'description' => 'Data siswa peserta PKL.',
                'table' => 'siswa',
                'primary_key' => 'nis',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nis', 'label' => 'NIS', 'sortable' => true],
                    ['key' => 'nama_siswa', 'label' => 'Nama Siswa', 'sortable' => true],
                    ['key' => 'kelas', 'label' => 'Kelas', 'sortable' => true],
                    ['key' => 'nama_jurusan', 'label' => 'Jurusan', 'sortable' => true],
                    ['key' => 'nama_rombel', 'label' => 'Rombel', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'nis', 'label' => 'NIS', 'type' => 'number'],
                    ['key' => 'nama_siswa', 'label' => 'Nama Siswa', 'type' => 'text'],
                    ['key' => 'id_user', 'label' => 'Akun User', 'type' => 'select', 'options' => 'userOptions'],
                    ['key' => 'id_kelas', 'label' => 'Kelas', 'type' => 'select', 'options' => 'kelasOptions'],
                    ['key' => 'id_jurusan', 'label' => 'Jurusan', 'type' => 'select', 'options' => 'jurusanOptions'],
                    ['key' => 'id_rombel', 'label' => 'Rombel', 'type' => 'select', 'options' => 'rombelOptions'],
                    ['key' => 'tahun_ajaran', 'label' => 'Tahun Ajaran', 'type' => 'text'],
                    ['key' => 'id_tempat', 'label' => 'Tempat PKL', 'type' => 'select', 'options' => 'tempatOptions'],
                    ['key' => 'id_instruktur', 'label' => 'Instruktur', 'type' => 'select', 'options' => 'instrukturOptions'],
                    ['key' => 'id_pembimbing', 'label' => 'Pembimbing', 'type' => 'select', 'options' => 'pembimbingOptions'],
                ],
                'query' => 'siswaQuery',
                'transformer' => 'siswaRow',
                'search_columns' => ['siswa.nama_siswa', 'jurusan.nama_jurusan', 'rombel.nama_rombel'],
                'sorts' => [
                    'nis' => 'siswa.nis',
                    'nama_siswa' => 'siswa.nama_siswa',
                    'kelas' => 'kelas.kelas',
                    'nama_jurusan' => 'jurusan.nama_jurusan',
                    'nama_rombel' => 'rombel.nama_rombel',
                ],
                'default_sort' => 'nis',
                'default_direction' => 'asc',
            ],
            'kelas' => [
                'title' => 'Kelas',
                'description' => 'Daftar tingkat kelas.',
                'table' => 'kelas',
                'primary_key' => 'id_kelas',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'kelas', 'label' => 'Kelas', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'kelas', 'label' => 'Kelas (Angka)', 'type' => 'number'],
                ],
                'query' => 'kelasQuery',
                'transformer' => 'kelasRow',
                'search_columns' => ['kelas.kelas'],
                'sorts' => ['id_kelas' => 'kelas.id_kelas', 'kelas' => 'kelas.kelas'],
                'default_sort' => 'id_kelas',
                'default_direction' => 'asc',
            ],
            'jurusan' => [
                'title' => 'Jurusan',
                'description' => 'Daftar jurusan sekolah.',
                'table' => 'jurusan',
                'primary_key' => 'id_jurusan',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'id_jurusan', 'label' => 'ID Jurusan', 'sortable' => true],
                    ['key' => 'nama_jurusan', 'label' => 'Nama Jurusan', 'sortable' => true],
                    ['key' => 'nama_kajur', 'label' => 'Kajur', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'id_jurusan', 'label' => 'ID Jurusan', 'type' => 'number'],
                    ['key' => 'nama_jurusan', 'label' => 'Nama Jurusan', 'type' => 'text'],
                    ['key' => 'id_kajur', 'label' => 'Kajur', 'type' => 'select', 'options' => 'kajurOptions'],
                ],
                'query' => 'jurusanFullQuery',
                'transformer' => 'jurusanFullRow',
                'search_columns' => ['jurusan.nama_jurusan', 'kajur.nama_kajur'],
                'sorts' => [
                    'id_jurusan' => 'jurusan.id_jurusan',
                    'nama_jurusan' => 'jurusan.nama_jurusan',
                    'nama_kajur' => 'kajur.nama_kajur',
                ],
                'default_sort' => 'id_jurusan',
                'default_direction' => 'asc',
            ],
            'kajur' => [
                'title' => 'Kajur',
                'description' => 'Data kepala jurusan.',
                'table' => 'kajur',
                'primary_key' => 'id_kajur',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_kajur', 'label' => 'Nama Kajur', 'sortable' => true],
                    ['key' => 'user_name', 'label' => 'Nama Akun', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'nama_kajur', 'label' => 'Nama Kajur', 'type' => 'text'],
                    ['key' => 'id_user', 'label' => 'Akun User', 'type' => 'select', 'options' => 'userOptions'],
                ],
                'query' => 'kajurQuery',
                'transformer' => 'kajurRow',
                'search_columns' => ['kajur.nama_kajur', 'users.name'],
                'sorts' => [
                    'id_kajur' => 'kajur.id_kajur',
                    'nama_kajur' => 'kajur.nama_kajur',
                    'user_name' => 'users.name',
                ],
                'default_sort' => 'id_kajur',
                'default_direction' => 'asc',
            ],
            'rombel' => [
                'title' => 'Rombel',
                'description' => 'Data rombongan belajar.',
                'table' => 'rombel',
                'primary_key' => 'id_rombel',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_rombel', 'label' => 'Nama Rombel', 'sortable' => true],
                    ['key' => 'wali_name', 'label' => 'Wali Kelas', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'nama_rombel', 'label' => 'Nama Rombel', 'type' => 'text'],
                    ['key' => 'id_wali', 'label' => 'Wali Kelas (User)', 'type' => 'select', 'options' => 'userOptions'],
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
                'description' => 'Daftar perusahaan/tempat PKL.',
                'table' => 'tempat_pkl',
                'primary_key' => 'id_tempat',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_perusahaan', 'label' => 'Nama Perusahaan', 'sortable' => true],
                    ['key' => 'alamat', 'label' => 'Alamat', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'nama_perusahaan', 'label' => 'Nama Perusahaan', 'type' => 'text'],
                    ['key' => 'alamat', 'label' => 'Alamat', 'type' => 'text'],
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
            'pembimbing' => [
                'title' => 'Pembimbing',
                'description' => 'Data guru pembimbing PKL.',
                'table' => 'pembimbing',
                'primary_key' => 'id_pembimbing',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_pembimbing', 'label' => 'Nama Pembimbing', 'sortable' => true],
                    ['key' => 'user_name', 'label' => 'Nama Akun', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'nama_pembimbing', 'label' => 'Nama Pembimbing', 'type' => 'text'],
                    ['key' => 'id_user', 'label' => 'Akun User', 'type' => 'select', 'options' => 'userOptions'],
                ],
                'query' => 'pembimbingQuery',
                'transformer' => 'pembimbingRow',
                'search_columns' => ['pembimbing.nama_pembimbing', 'users.name'],
                'sorts' => [
                    'id_pembimbing' => 'pembimbing.id_pembimbing',
                    'nama_pembimbing' => 'pembimbing.nama_pembimbing',
                    'user_name' => 'users.name',
                ],
                'default_sort' => 'id_pembimbing',
                'default_direction' => 'asc',
            ],
            'instruktur' => [
                'title' => 'Instruktur',
                'description' => 'Data instruktur lapangan dari perusahaan.',
                'table' => 'instruktur',
                'primary_key' => 'id_instruktur',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'nama_instruktur', 'label' => 'Nama Instruktur', 'sortable' => true],
                    ['key' => 'nama_perusahaan', 'label' => 'Perusahaan', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'nama_instruktur', 'label' => 'Nama Instruktur', 'type' => 'text'],
                    ['key' => 'id_tempat', 'label' => 'Tempat PKL', 'type' => 'select', 'options' => 'tempatOptions'],
                ],
                'query' => 'instrukturQuery',
                'transformer' => 'instrukturRow',
                'search_columns' => ['instruktur.nama_instruktur', 'tempat_pkl.nama_perusahaan'],
                'sorts' => [
                    'id_instruktur' => 'instruktur.id_instruktur',
                    'nama_instruktur' => 'instruktur.nama_instruktur',
                    'nama_perusahaan' => 'tempat_pkl.nama_perusahaan',
                ],
                'default_sort' => 'id_instruktur',
                'default_direction' => 'asc',
            ],
            'absensi' => [
                'title' => 'Absensi',
                'description' => 'Data kehadiran siswa.',
                'table' => 'absensi',
                'primary_key' => 'id_absensi',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'student_name', 'label' => 'Siswa', 'sortable' => true],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'sortable' => true],
                    ['key' => 'status_label', 'label' => 'Status', 'sortable' => false],
                ],
                'form' => [
                    ['key' => 'id_siswa', 'label' => 'Siswa', 'type' => 'select', 'options' => 'siswaOptions'],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                    ['key' => 'jam_datang', 'label' => 'Jam Datang', 'type' => 'datetime-local'],
                    ['key' => 'jam_pulang', 'label' => 'Jam Pulang', 'type' => 'datetime-local'],
                    ['key' => 'status', 'label' => 'Status (1=Hadir, 0=Absen)', 'type' => 'number'],
                    ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'text'],
                    ['key' => 'foto_bukti', 'label' => 'URL Foto Bukti', 'type' => 'text'],
                ],
                'query' => 'absensiQuery',
                'transformer' => 'absensiRow',
                'search_columns' => ['siswa.nama_siswa'],
                'sorts' => [
                    'id_absensi' => 'absensi.id_absensi',
                    'student_name' => 'siswa.nama_siswa',
                    'tanggal' => 'absensi.tanggal',
                ],
                'default_sort' => 'tanggal',
                'default_direction' => 'desc',
            ],
            'agenda' => [
                'title' => 'Agenda',
                'description' => 'Jurnal harian siswa.',
                'table' => 'agenda',
                'primary_key' => 'id_agenda',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'student_name', 'label' => 'Siswa', 'sortable' => true],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'sortable' => true],
                    ['key' => 'rencana_pekerjaan', 'label' => 'Rencana', 'sortable' => false],
                ],
                'form' => [
                    ['key' => 'id_siswa', 'label' => 'Siswa', 'type' => 'select', 'options' => 'siswaOptions'],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                    ['key' => 'rencana_pekerjaan', 'label' => 'Rencana Pekerjaan', 'type' => 'textarea'],
                    ['key' => 'realisasi_pekerjaan', 'label' => 'Realisasi Pekerjaan', 'type' => 'textarea'],
                    ['key' => 'penugasan_khusus_dari_atasan', 'label' => 'Penugasan Khusus', 'type' => 'textarea'],
                    ['key' => 'penemuan_masalah', 'label' => 'Penemuan Masalah', 'type' => 'textarea'],
                    ['key' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea'],
                    ['key' => 'id_instruktur', 'label' => 'Approval Instruktur', 'type' => 'select', 'options' => 'instrukturOptions'],
                    ['key' => 'id_pembimbing', 'label' => 'Approval Pembimbing', 'type' => 'select', 'options' => 'pembimbingOptions'],
                ],
                'query' => 'agendaQuery',
                'transformer' => 'agendaRow',
                'search_columns' => ['siswa.nama_siswa'],
                'sorts' => [
                    'id_agenda' => 'agenda.id_agenda',
                    'student_name' => 'siswa.nama_siswa',
                    'tanggal' => 'agenda.tanggal',
                ],
                'default_sort' => 'tanggal',
                'default_direction' => 'desc',
            ],
            'penilaian' => [
                'title' => 'Penilaian',
                'description' => 'Data penilaian harian siswa.',
                'table' => 'penilaian',
                'primary_key' => 'id_penilaian',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'student_name', 'label' => 'Siswa', 'sortable' => true],
                    ['key' => 'senyum_label', 'label' => 'Senyum', 'sortable' => false],
                    ['key' => 'keramahan_label', 'label' => 'Keramahan', 'sortable' => false],
                    ['key' => 'penampilan_label', 'label' => 'Penampilan', 'sortable' => false],
                ],
                'form' => [
                    ['key' => 'id_siswa', 'label' => 'Siswa', 'type' => 'select', 'options' => 'siswaOptions'],
                    ['key' => 'id_agenda', 'label' => 'Agenda', 'type' => 'select', 'options' => 'agendaOptions'],
                    ['key' => 'senyum', 'label' => 'Senyum (1=Baik, 0=Kurang)', 'type' => 'number'],
                    ['key' => 'keramahan', 'label' => 'Keramahan (1=Baik, 0=Kurang)', 'type' => 'number'],
                    ['key' => 'penampilan', 'label' => 'Penampilan (1=Baik, 0=Kurang)', 'type' => 'number'],
                    ['key' => 'komunikasi', 'label' => 'Komunikasi (1=Baik, 0=Kurang)', 'type' => 'number'],
                    ['key' => 'realisasi_kerja', 'label' => 'Realisasi Kerja (1=Baik, 0=Kurang)', 'type' => 'number'],
                ],
                'query' => 'penilaianQuery',
                'transformer' => 'penilaianRow',
                'search_columns' => ['siswa.nama_siswa'],
                'sorts' => [
                    'id_penilaian' => 'penilaian.id_penilaian',
                    'student_name' => 'siswa.nama_siswa',
                ],
                'default_sort' => 'id_penilaian',
                'default_direction' => 'desc',
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

    private function usersQuery(Request $request): Builder
    {
        return DB::table('users')
            ->leftJoin('role', 'users.role', '=', 'role.id_role')
            ->select([
                'users.*',
                'role.role as role_name',
            ]);
    }

    private function absensiQuery(Request $request): Builder
    {
        $query = DB::table('absensi')
            ->leftJoin('siswa', 'absensi.id_siswa', '=', 'siswa.nis')
            ->select([
                'absensi.*',
                'siswa.nama_siswa as student_name',
            ]);

        return $this->scopeStudentOwnedRecords($query, $request, 'absensi.id_siswa');
    }

    private function agendaQuery(Request $request): Builder
    {
        $query = DB::table('agenda')
            ->leftJoin('siswa', 'agenda.id_siswa', '=', 'siswa.nis')
            ->leftJoin('penilaian', 'agenda.id_agenda', '=', 'penilaian.id_agenda')
            ->select([
                'agenda.*',
                'siswa.nama_siswa as student_name',
                'penilaian.senyum',
                'penilaian.keramahan',
                'penilaian.penampilan',
                'penilaian.komunikasi',
                'penilaian.realisasi_kerja',
            ]);

        return $this->scopeStudentOwnedRecords($query, $request, 'agenda.id_siswa');
    }

<<<<<<< HEAD
    private function penilaianQuery(): Builder
    {
        return DB::table('penilaian')
            ->leftJoin('siswa', 'penilaian.id_siswa', '=', 'siswa.nis')
            ->select([
                'penilaian.*',
                'siswa.nama_siswa as student_name',
            ]);
    }

    private function siswaQuery(): Builder
=======
    private function siswaQuery(Request $request): Builder
>>>>>>> fcd64bbc4eba1949c1d5a67236d32288a5100b0a
    {
        return DB::table('siswa')
            ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->leftJoin('jurusan', 'siswa.id_jurusan', '=', 'jurusan.id_jurusan')
            ->leftJoin('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
            ->leftJoin('tempat_pkl', 'siswa.id_tempat', '=', 'tempat_pkl.id_tempat')
            ->leftJoin('pembimbing', 'siswa.id_pembimbing', '=', 'pembimbing.id_pembimbing')
            ->select([
                'siswa.*',
                'kelas.kelas',
                'jurusan.nama_jurusan',
                'rombel.nama_rombel',
                'tempat_pkl.nama_perusahaan',
                'pembimbing.nama_pembimbing',
            ]);
    }

    private function instrukturQuery(Request $request): Builder
    {
        return DB::table('instruktur')
            ->leftJoin('tempat_pkl', 'instruktur.id_tempat', '=', 'tempat_pkl.id_tempat')
            ->select([
                'instruktur.*',
                'tempat_pkl.nama_perusahaan',
            ]);
    }

    private function pembimbingQuery(Request $request): Builder
    {
        return DB::table('pembimbing')
            ->leftJoin('users', 'pembimbing.id_user', '=', 'users.id_user')
            ->select([
                'pembimbing.*',
                'users.name as user_name',
            ]);
    }

    private function kajurQuery(Request $request): Builder
    {
        return DB::table('kajur')
            ->leftJoin('users', 'kajur.id_user', '=', 'users.id_user')
            ->select([
                'kajur.*',
                'users.name as user_name',
            ]);
    }

    private function rombelQuery(Request $request): Builder
    {
        return DB::table('rombel')
            ->leftJoin('users', 'rombel.id_wali', '=', 'users.id_user')
            ->select([
                'rombel.*',
                'users.name as wali_name',
            ]);
    }

    private function tempatPklQuery(Request $request): Builder
    {
        return DB::table('tempat_pkl');
    }

    private function roleQuery(): Builder
    {
        return DB::table('role');
    }

    private function roleRow(object $row): array
    {
        return (array) $row;
    }

    private function kelasQuery(): Builder
    {
        return DB::table('kelas');
    }

    private function kelasRow(object $row): array
    {
        return (array) $row;
    }

    private function jurusanFullQuery(): Builder
    {
        return DB::table('jurusan')
            ->leftJoin('kajur', 'jurusan.id_kajur', '=', 'kajur.id_kajur')
            ->select(['jurusan.*', 'kajur.nama_kajur']);
    }

    private function jurusanFullRow(object $row): array
    {
        return (array) $row;
    }

    private function scopeStudentOwnedRecords(Builder $query, Request $request, string $studentColumn): Builder
    {
        $studentId = DB::table('siswa')
            ->where('id_user', $request->user()->id_user)
            ->value('nis');

        if ($studentId) {
            $query->where($studentColumn, $studentId);
        }

        return $query;
    }

    private function usersRow(object $row): array
    {
        return (array) $row;
    }

    private function absensiRow(object $row): array
    {
        $data = (array) $row;
        $data['status_label'] = $row->status == 1 ? 'Hadir' : 'Absen';
        return $data;
    }

    private function agendaRow(object $row): array
    {
        $data = (array) $row;
        $data['senyum_label'] = $this->ratingLabel($row->senyum);
        $data['keramahan_label'] = $this->ratingLabel($row->keramahan);
        $data['penampilan_label'] = $this->ratingLabel($row->penampilan);
        $data['komunikasi_label'] = $this->ratingLabel($row->komunikasi);
        $data['realisasi_kerja_label'] = $this->ratingLabel($row->realisasi_kerja);
        return $data;
    }

    private function penilaianRow(object $row): array
    {
        $data = (array) $row;
        $data['senyum_label'] = $this->ratingLabel($row->senyum);
        $data['keramahan_label'] = $this->ratingLabel($row->keramahan);
        $data['penampilan_label'] = $this->ratingLabel($row->penampilan);
        $data['komunikasi_label'] = $this->ratingLabel($row->komunikasi);
        $data['realisasi_kerja_label'] = $this->ratingLabel($row->realisasi_kerja);
        return $data;
    }

    private function siswaRow(object $row): array
    {
        return (array) $row;
    }

    private function instrukturRow(object $row): array
    {
        return (array) $row;
    }

    private function pembimbingRow(object $row): array
    {
        return (array) $row;
    }

    private function kajurRow(object $row): array
    {
        return (array) $row;
    }

    private function rombelRow(object $row): array
    {
        return (array) $row;
    }

    private function tempatPklRow(object $row): array
    {
        return (array) $row;
    }

    private function ratingLabel(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value == 1 ? 'Baik' : 'Kurang';
    }

    private function userOptions(): array
    {
        return DB::table('users')->orderBy('name')->get()->map(fn($u) => ['value' => $u->id_user, 'label' => $u->name])->all();
    }

    private function kelasOptions(): array
    {
        return DB::table('kelas')->orderBy('kelas')->get()->map(fn($k) => ['value' => $k->id_kelas, 'label' => "Kelas {$k->kelas}"])->all();
    }

    private function jurusanOptions(): array
    {
        return DB::table('jurusan')->orderBy('nama_jurusan')->get()->map(fn($j) => ['value' => $j->id_jurusan, 'label' => $j->nama_jurusan])->all();
    }

    private function rombelOptions(): array
    {
        return DB::table('rombel')->orderBy('nama_rombel')->get()->map(fn($r) => ['value' => $r->id_rombel, 'label' => $r->nama_rombel])->all();
    }

    private function tempatOptions(): array
    {
        return DB::table('tempat_pkl')->orderBy('nama_perusahaan')->get()->map(fn($t) => ['value' => $t->id_tempat, 'label' => $t->nama_perusahaan])->all();
    }

    private function instrukturOptions(): array
    {
        return DB::table('instruktur')->orderBy('nama_instruktur')->get()->map(fn($i) => ['value' => $i->id_instruktur, 'label' => $i->nama_instruktur])->all();
    }

    private function pembimbingOptions(): array
    {
        return DB::table('pembimbing')->orderBy('nama_pembimbing')->get()->map(fn($p) => ['value' => $p->id_pembimbing, 'label' => $p->nama_pembimbing])->all();
    }

    private function kajurOptions(): array
    {
        return DB::table('kajur')->orderBy('nama_kajur')->get()->map(fn($k) => ['value' => $k->id_kajur, 'label' => $k->nama_kajur])->all();
    }

    private function siswaOptions(): array
    {
        return DB::table('siswa')->orderBy('nama_siswa')->get()->map(fn($s) => ['value' => $s->nis, 'label' => $s->nama_siswa])->all();
    }

    private function agendaOptions(): array
    {
        return DB::table('agenda')->orderBy('tanggal', 'desc')->get()->map(fn($a) => ['value' => $a->id_agenda, 'label' => "Agenda {$a->tanggal}"])->all();
    }
}
