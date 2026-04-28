<?php

namespace App\Http\Controllers;

use App\Services\AccessControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminTableController extends Controller
{
    public function __construct(private readonly AccessControlService $accessControl)
    {
    }

    public function show(Request $request, string $module): View|RedirectResponse
    {
        $definition = $this->definitions()[$module] ?? null;

        if (! $definition) {
            throw new NotFoundHttpException();
        }

        abort_unless($this->accessControl->canAccess($request->user(), $module), 403);

        if ($module === 'agenda' && in_array((int) $request->user()->role, [3, 4], true)) {
            return redirect()->route('agenda.review');
        }

        if ($module === 'absensi') {
            return $this->showAbsensiExplorer($request);
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

    public function importModule(Request $request, string $module): RedirectResponse
    {
        abort_unless(in_array($module, ['users', 'siswa'], true), 404);
        abort_unless($this->accessControl->canAccess($request->user(), $module), 403);

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:5120',
        ]);

        $rows = $this->parseImportedSpreadsheet($validated['file'], $module);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => "File tidak berisi data {$module} yang bisa diimpor.",
            ]);
        }

        return match ($module) {
            'users' => $this->importUsersRows($rows),
            'siswa' => $this->importSiswaRows($rows),
        };
    }

    private function importUsersRows(array $rows): RedirectResponse
    {
        $createdCount = 0;

        DB::transaction(function () use ($rows, &$createdCount): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $payload = $this->mapImportedUserRow($row, $rowNumber);

                $validator = Validator::make($payload, [
                    'name' => 'required|string|max:255',
                    'password' => 'required|string|min:1',
                    'role' => 'required|in:1',
                ], [], [
                    'name' => 'NIS',
                    'password' => 'password',
                    'role' => 'role',
                ]);

                if ($validator->fails()) {
                    $message = $validator->errors()->first();

                    throw ValidationException::withMessages([
                        'file' => "Baris {$rowNumber}: {$message}",
                    ]);
                }

                $payload['password'] = bcrypt($payload['password']);
                $payload['created_at'] = now();
                $payload['created_by'] = auth()->id();

                $userId = DB::table('users')->insertGetId($payload);

                $this->syncRoleSpecificProfile((int) $userId, $payload['name'], 1, null);
                $createdCount++;
            }
        }, 3);

        return back()->with('success', "{$createdCount} user berhasil diimpor.");
    }

    private function importSiswaRows(array $rows): RedirectResponse
    {
        $usersByName = DB::table('users')
            ->where('role', '!=', 8)
            ->get(['id_user', 'name'])
            ->mapWithKeys(fn (object $user) => [strtolower(trim($user->name)) => (int) $user->id_user]);

        $createdCount = 0;

        DB::transaction(function () use ($rows, $usersByName, &$createdCount): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $payload = $this->mapImportedSiswaRow($row, $usersByName, $rowNumber);

                $validator = Validator::make($payload, [
                    'nis' => 'required|integer|unique:siswa,nis',
                    'nama_siswa' => 'required|string|max:50',
                    'id_user' => 'nullable|integer',
                    'id_kelas' => 'required|integer',
                    'id_jurusan' => 'required|integer',
                    'id_rombel' => 'required|integer',
                    'tahun_ajaran' => 'required|string|max:50',
                    'id_tempat' => 'nullable',
                    'id_instruktur' => 'nullable',
                    'id_pembimbing' => 'nullable',
                ], [], [
                    'nis' => 'NIS',
                    'nama_siswa' => 'nama siswa',
                    'id_user' => 'akun user',
                    'id_kelas' => 'kelas',
                    'id_jurusan' => 'jurusan',
                    'id_rombel' => 'rombel',
                    'tahun_ajaran' => 'tahun ajaran',
                    'id_tempat' => 'tempat PKL',
                    'id_instruktur' => 'instruktur',
                    'id_pembimbing' => 'pembimbing',
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'file' => "Baris {$rowNumber}: " . $validator->errors()->first(),
                    ]);
                }

                DB::table('siswa')->insert($payload);
                $createdCount++;
            }
        }, 3);

        return back()->with('success', "{$createdCount} siswa berhasil diimpor.");
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

        if ($module === 'users') {
            $relationPayload = [
                'role' => (int) $data['role'],
                'tempat_pkl_id' => $data['id_tempat'] ?? null,
            ];

            unset($data['id_tempat']);

            $userId = DB::transaction(function () use ($definition, $data, $relationPayload): int {
                $userId = DB::table($definition['table'])->insertGetId($data);

                $this->syncRoleSpecificProfile($userId, $data['name'], $relationPayload['role'], $relationPayload['tempat_pkl_id']);

                return (int) $userId;
            }, 3);

            return back()->with('success', "Data berhasil ditambahkan. User ID {$userId} sudah dibuat.");
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

        if ($module === 'users') {
            $relationPayload = [
                'role' => (int) $data['role'],
                'tempat_pkl_id' => $data['id_tempat'] ?? null,
            ];

            unset($data['id_tempat']);

            DB::transaction(function () use ($definition, $id, $data, $relationPayload): void {
                DB::table($definition['table'])
                    ->where($definition['primary_key'], $id)
                    ->update($data);

                $this->syncRoleSpecificProfile((int) $id, $data['name'], $relationPayload['role'], $relationPayload['tempat_pkl_id']);
            }, 3);

            return back()->with('success', 'Data berhasil diperbarui.');
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

    private function showAbsensiExplorer(Request $request): View
    {
        $selectedRombel = trim((string) $request->query('rombel', ''));
        $selectedStudent = trim((string) $request->query('student', ''));
        $studentSearch = trim((string) $request->query('student_search', ''));

        $studentScope = $this->accessibleStudentsQuery($request);
        $activeRombel = null;

        $rombels = (clone $studentScope)
            ->join('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
            ->select([
                'rombel.id_rombel',
                'rombel.nama_rombel',
                DB::raw('COUNT(siswa.nis) as student_count'),
            ])
            ->groupBy('rombel.id_rombel', 'rombel.nama_rombel')
            ->orderBy('rombel.nama_rombel')
            ->get();

        $students = collect();
        $activeStudent = null;
        $attendanceHistory = null;

        if ($selectedRombel !== '') {
            $activeRombel = DB::table('rombel')
                ->where('id_rombel', $selectedRombel)
                ->first();

            $studentsQuery = (clone $studentScope)
                ->join('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
                ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->leftJoin('jurusan', 'siswa.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('siswa.id_rombel', $selectedRombel)
                ->select([
                    'siswa.nis',
                    'siswa.nama_siswa',
                    'siswa.tahun_ajaran',
                    'kelas.kelas',
                    'jurusan.nama_jurusan',
                    'rombel.nama_rombel',
                ])
                ->orderBy('siswa.nama_siswa');

            if ($studentSearch !== '') {
                $studentsQuery->where('siswa.nama_siswa', 'like', "%{$studentSearch}%");
            }

            $students = $studentsQuery->get();
        }

        if ($selectedStudent !== '') {
            $activeStudent = (clone $studentScope)
                ->join('rombel', 'siswa.id_rombel', '=', 'rombel.id_rombel')
                ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
                ->leftJoin('jurusan', 'siswa.id_jurusan', '=', 'jurusan.id_jurusan')
                ->where('siswa.nis', $selectedStudent)
                ->select([
                    'siswa.nis',
                    'siswa.nama_siswa',
                    'siswa.tahun_ajaran',
                    'kelas.kelas',
                    'jurusan.nama_jurusan',
                    'rombel.nama_rombel',
                ])
                ->first();

            if ($activeStudent) {
                $attendanceHistory = DB::table('absensi')
                    ->where('id_siswa', $activeStudent->nis)
                    ->orderByDesc('tanggal')
                    ->orderByDesc('id_absensi')
                    ->paginate(12)
                    ->withQueryString();
            }
        }

        return view('admin.absensi-explorer', [
            'pageTitle' => 'Absensi',
            'pageDescription' => 'Pilih rombel, lalu pilih siswa untuk melihat riwayat absensi.',
            'rombels' => $rombels,
            'students' => $students,
            'activeRombel' => $activeRombel,
            'activeStudent' => $activeStudent,
            'attendanceHistory' => $attendanceHistory,
            'selectedRombel' => $selectedRombel,
            'selectedStudent' => $selectedStudent,
            'studentSearch' => $studentSearch,
        ]);
    }

    private function getValidationRules(string $module, bool $isUpdate, $id = null): array
    {
        $rules = [
            'users' => [
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    $isUpdate ? Rule::unique('users', 'email')->ignore($id, 'id_user') : Rule::unique('users', 'email'),
                ],
                'password' => $isUpdate ? 'nullable|string|min:8' : 'required|string|min:8',
                'role' => 'required|exists:role,id_role',
                'id_tempat' => [
                    Rule::requiredIf(fn () => (int) request('role') === 3),
                    'nullable',
                    'exists:tempat_pkl,id_tempat',
                ],
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
                'description' => 'Data akun pengguna berdasarkan tabel users.',
                'table' => 'users',
                'primary_key' => 'id_user',
                'columns' => [
                    ['key' => 'no', 'label' => 'No', 'sortable' => false],
                    ['key' => 'name', 'label' => 'NIS', 'sortable' => true],
                    ['key' => 'role_name', 'label' => 'Role', 'sortable' => false],
                    ['key' => 'password_changed_at', 'label' => 'Password Changed At', 'sortable' => true],
                    ['key' => 'created_at', 'label' => 'Created At', 'sortable' => true],
                ],
                'form' => [
                    ['key' => 'name', 'label' => 'NIS', 'type' => 'text'],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['key' => 'password', 'label' => 'Password', 'type' => 'password'],
                    ['key' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => 'roleFilterOptions'],
                    ['key' => 'id_tempat', 'label' => 'Tempat PKL (Instruktur)', 'type' => 'select', 'options' => 'tempatOptions'],
                ],
                'query' => 'usersQuery',
                'transformer' => 'usersRow',
                'search_columns' => ['users.name', 'users.email'],
                'sorts' => [
                    'id_user' => 'users.id_user',
                    'name' => 'users.name',
                    'email' => 'users.email',
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
                    ['key' => 'instruktur_status', 'label' => 'Instruktur', 'sortable' => false],
                    ['key' => 'pembimbing_status', 'label' => 'Pembimbing', 'sortable' => false],
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
            ->where('role', '!=', 'superadmin')
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
            ->where('role.role', '!=', 'superadmin')
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

        return $this->scopeOwnedRecords($query, $request, 'absensi.id_siswa', 'siswa');
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

        return $this->scopeOwnedRecords($query, $request, 'agenda.id_siswa', 'siswa');
    }
    private function penilaianQuery(): Builder
    {
        return DB::table('penilaian')
            ->leftJoin('siswa', 'penilaian.id_siswa', '=', 'siswa.nis')
            ->select([
                'penilaian.*',
                'siswa.nama_siswa as student_name',
            ]);
    }
    private function siswaQuery(Request $request): Builder
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

    private function scopeOwnedRecords(Builder $query, Request $request, string $studentColumn, ?string $studentTable = null): Builder
    {
        $role = (int) $request->user()->role;

        $studentId = DB::table('siswa')
            ->where('id_user', $request->user()->id_user)
            ->value('nis');

        if ($studentId) {
            $query->where($studentColumn, $studentId);

            return $query;
        }

        if (! $studentTable) {
            return $query;
        }

        if ($role === 4) {
            $pembimbingId = DB::table('pembimbing')
                ->where('id_user', $request->user()->id_user)
                ->value('id_pembimbing');

            if ($pembimbingId) {
                $query->where("{$studentTable}.id_pembimbing", $pembimbingId);
            }

            return $query;
        }

        if ($role === 3) {
            $instrukturId = DB::table('instruktur')
                ->where('nama_instruktur', $request->user()->name)
                ->value('id_instruktur');

            if ($instrukturId) {
                $query->where("{$studentTable}.id_instruktur", $instrukturId);
            }
        }

        return $query;
    }

    private function usersRow(object $row): array
    {
        $data = (array) $row;
        // Safety check to hide superadmin name/role if somehow it leaked to this row
        if (($data['role_name'] ?? '') === 'superadmin' || ($data['role'] ?? 0) == 8) {
            $data['role_name'] = 'System Admin'; // Or leave as is if query filter works
        }
        return $data;
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
        $data['instruktur_status'] = $row->id_instruktur ? 'Approved' : 'Not Approved';
        $data['pembimbing_status'] = $row->id_pembimbing ? 'Approved' : 'Not Approved';
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
        return DB::table('users')
            ->leftJoin('role', 'users.role', '=', 'role.id_role')
            ->where('role.role', '!=', 'superadmin')
            ->orderBy('users.name')
            ->get(['users.id_user', 'users.name'])
            ->map(fn($u) => ['value' => $u->id_user, 'label' => $u->name])
            ->all();
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

    private function accessibleStudentsQuery(Request $request): Builder
    {
        $query = DB::table('siswa');
        $role = (int) $request->user()->role;
        $studentId = DB::table('siswa')
            ->where('id_user', $request->user()->id_user)
            ->value('nis');

        if ($studentId) {
            return $query->where('siswa.nis', $studentId);
        }

        if ($role === 4) {
            $pembimbingId = DB::table('pembimbing')
                ->where('id_user', $request->user()->id_user)
                ->value('id_pembimbing');

            if ($pembimbingId) {
                $query->where('siswa.id_pembimbing', $pembimbingId);
            }

            return $query;
        }

        if ($role === 3) {
            $instrukturId = DB::table('instruktur')
                ->where('nama_instruktur', $request->user()->name)
                ->value('id_instruktur');

            if ($instrukturId) {
                $query->where('siswa.id_instruktur', $instrukturId);
            }
        }

        return $query;
    }

    private function syncRoleSpecificProfile(int $userId, string $name, int $roleId, ?int $tempatId = null): void
    {
        if ($roleId === 4 && Schema::hasTable('pembimbing')) {
            DB::table('pembimbing')->updateOrInsert(
                ['id_user' => $userId],
                ['nama_pembimbing' => $name]
            );

            return;
        }

        if ($roleId === 3 && Schema::hasTable('instruktur')) {
            $payload = [
                'nama_instruktur' => $name,
            ];

            if (Schema::hasColumn('instruktur', 'id_user')) {
                $payload['id_user'] = $userId;
            }

            if (Schema::hasColumn('instruktur', 'id_tempat')) {
                $payload['id_tempat'] = $tempatId;
            }

            $identifier = Schema::hasColumn('instruktur', 'id_user')
                ? ['id_user' => $userId]
                : ['nama_instruktur' => $name];

            DB::table('instruktur')->updateOrInsert($identifier, $payload);
        }
    }

    private function mapImportedUserRow(array $row, int $rowNumber): array
    {
        $nis = trim((string) ($row['nis'] ?? $row['name'] ?? ''));

        if ($nis === '') {
            throw ValidationException::withMessages([
                'file' => "Baris {$rowNumber}: kolom NIS wajib diisi.",
            ]);
        }

        return [
            'name' => $nis,
            'email' => null,
            'password' => $nis,
            'role' => 1,
        ];
    }

    private function mapImportedSiswaRow(
        array $row,
        \Illuminate\Support\Collection $usersByName,
        int $rowNumber
    ): array {
        $nis = trim((string) ($row['nis'] ?? ''));
        $namaSiswa = trim((string) ($row['nama_siswa'] ?? $row['nama'] ?? ''));

        if ($nis === '' && $namaSiswa === '') {
            throw ValidationException::withMessages([
                'file' => "Baris {$rowNumber}: data kosong tidak bisa diimpor.",
            ]);
        }

        $kelasValue = $this->deriveImportedKelasValue($namaSiswa, $rowNumber);
        $userId = $usersByName->get(strtolower($nis));

        $payload = [
            'nis' => $nis,
            'nama_siswa' => $namaSiswa,
            'id_user' => $userId,
            'id_kelas' => $kelasValue,
            'id_jurusan' => 0,
            'id_rombel' => random_int(1, 10),
            'tahun_ajaran' => '2025/2026',
            'id_tempat' => null,
            'id_instruktur' => null,
            'id_pembimbing' => null,
        ];

        return $payload;
    }

    private function parseImportedSpreadsheet(UploadedFile $file, string $module): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvFile($file, $module),
            'xlsx' => $this->parseXlsxFile($file, $module),
            default => throw ValidationException::withMessages([
                'file' => 'Format file tidak didukung. Gunakan .xlsx atau .csv.',
            ]),
        };
    }

    private function parseCsvFile(UploadedFile $file, string $module): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak bisa dibaca.',
            ]);
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $data);
                continue;
            }

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $rows[] = $this->combineImportRow($headers, $data);
        }

        fclose($handle);

        return $this->ensureImportHeaders($headers, $rows, $module);
    }

    private function parseXlsxFile(UploadedFile $file, string $module): array
    {
        $zip = new \ZipArchive();
        $opened = $zip->open($file->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'file' => 'File XLSX tidak bisa dibuka.',
            ]);
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $worksheetPath = $this->resolveFirstWorksheetPath($zip);
        $worksheetXml = $worksheetPath ? $zip->getFromName($worksheetPath) : false;

        if (! $worksheetXml) {
            $zip->close();

            throw ValidationException::withMessages([
                'file' => 'Worksheet pada file XLSX tidak ditemukan.',
            ]);
        }

        $sheet = simplexml_load_string($worksheetXml);
        $zip->close();

        if (! $sheet || ! isset($sheet->sheetData->row)) {
            throw ValidationException::withMessages([
                'file' => 'Isi worksheet XLSX tidak valid.',
            ]);
        }

        $headers = null;
        $rows = [];

        foreach ($sheet->sheetData->row as $rowNode) {
            $row = $this->extractXlsxRow($rowNode, $sharedStrings);

            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $row);
                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rows[] = $this->combineImportRow($headers, $row);
        }

        return $this->ensureImportHeaders($headers, $rows, $module);
    }

    private function readXlsxSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $sharedStrings = simplexml_load_string($xml);

        if (! $sharedStrings) {
            return [];
        }

        $strings = [];

        foreach ($sharedStrings->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $text = '';

            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function resolveFirstWorksheetPath(\ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if (! $workbookXml || ! $relsXml) {
            return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relsXml);

        if (! $workbook || ! $relationships || ! isset($workbook->sheets->sheet[0])) {
            return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
        }

        $relationshipId = (string) $workbook->sheets->sheet[0]->attributes('r', true)->id;

        foreach ($relationships->Relationship as $relationship) {
            $attributes = $relationship->attributes();

            if ((string) $attributes['Id'] !== $relationshipId) {
                continue;
            }

            return 'xl/' . ltrim((string) $attributes['Target'], '/');
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
    }

    private function extractXlsxRow(\SimpleXMLElement $rowNode, array $sharedStrings): array
    {
        $row = [];

        foreach ($rowNode->c as $cell) {
            $reference = (string) $cell['r'];
            $columnName = preg_replace('/\d+/', '', $reference);
            $columnIndex = $this->spreadsheetColumnToIndex($columnName);
            $row[$columnIndex] = $this->extractXlsxCellValue($cell, $sharedStrings);
        }

        if ($row === []) {
            return [];
        }

        ksort($row);

        $values = [];
        $maxIndex = max(array_keys($row));

        for ($index = 0; $index <= $maxIndex; $index++) {
            $values[] = $row[$index] ?? '';
        }

        return $values;
    }

    private function extractXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return trim((string) ($cell->is->t ?? ''));
        }

        $value = isset($cell->v) ? (string) $cell->v : '';

        return match ($type) {
            's' => trim((string) ($sharedStrings[(int) $value] ?? '')),
            'b' => $value === '1' ? '1' : '0',
            default => trim($value),
        };
    }

    private function spreadsheetColumnToIndex(string $column): int
    {
        $column = strtoupper($column);
        $index = 0;

        for ($i = 0; $i < strlen($column); $i++) {
            $index = ($index * 26) + (ord($column[$i]) - 64);
        }

        return $index - 1;
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim((string) $header, '_');
    }

    private function combineImportRow(array $headers, array $data): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = isset($data[$index]) ? trim((string) $data[$index]) : '';
        }

        return $row;
    }

    private function ensureImportHeaders(?array $headers, array $rows, string $module): array
    {
        if ($headers === null || $headers === []) {
            throw ValidationException::withMessages([
                'file' => 'Header file impor tidak ditemukan.',
            ]);
        }

        if ($module === 'users') {
            if (! in_array('nis', $headers, true) && ! in_array('name', $headers, true)) {
                throw ValidationException::withMessages([
                    'file' => 'Header wajib minimal berisi kolom `nis` atau `name`.',
                ]);
            }
        }

        if ($module === 'siswa') {
            if (! in_array('nis', $headers, true)) {
                throw ValidationException::withMessages([
                    'file' => 'Header wajib berisi kolom `nis`.',
                ]);
            }

            if (! in_array('nama_siswa', $headers, true) && ! in_array('nama', $headers, true)) {
                throw ValidationException::withMessages([
                    'file' => 'Header wajib berisi kolom `nama_siswa` atau `nama`.',
                ]);
            }
        }

        return $rows;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function deriveImportedKelasValue(string $source, int $rowNumber): int
    {
        $normalized = strtolower(trim($source));

        if (str_contains($normalized, 'xii')) {
            return 12;
        }

        if (str_contains($normalized, 'xi')) {
            return 11;
        }

        if (preg_match('/\bx\b/', $normalized) === 1 || str_contains($normalized, ' x')) {
            return 10;
        }

        throw ValidationException::withMessages([
            'file' => "Baris {$rowNumber}: nama siswa harus mengandung penanda kelas X, XI, atau XII.",
        ]);
    }
}
