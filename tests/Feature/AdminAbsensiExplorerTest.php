<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAbsensiExplorerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect([
            7 => 'admin',
            8 => 'superadmin',
        ])->each(fn (string $role, int $id) => Role::query()->create([
            'id_role' => $id,
            'role' => $role,
        ]));

        if (! Schema::hasTable('kelas')) {
            Schema::create('kelas', function (Blueprint $table): void {
                $table->increments('id_kelas');
                $table->integer('kelas');
            });
        }

        if (! Schema::hasTable('jurusan')) {
            Schema::create('jurusan', function (Blueprint $table): void {
                $table->increments('id_jurusan');
                $table->string('nama_jurusan');
            });
        }

        if (! Schema::hasTable('rombel')) {
            Schema::create('rombel', function (Blueprint $table): void {
                $table->increments('id_rombel');
                $table->string('nama_rombel');
                $table->unsignedBigInteger('id_wali')->nullable();
            });
        }

        if (! Schema::hasTable('siswa')) {
            Schema::create('siswa', function (Blueprint $table): void {
                $table->integer('nis')->primary();
                $table->unsignedBigInteger('id_user')->nullable();
                $table->string('nama_siswa');
                $table->unsignedInteger('id_kelas')->nullable();
                $table->unsignedInteger('id_jurusan')->nullable();
                $table->unsignedInteger('id_rombel')->nullable();
                $table->string('tahun_ajaran')->nullable();
                $table->unsignedInteger('id_tempat')->nullable();
                $table->unsignedInteger('id_instruktur')->nullable();
                $table->unsignedInteger('id_pembimbing')->nullable();
            });
        }

        if (! Schema::hasTable('absensi')) {
            Schema::create('absensi', function (Blueprint $table): void {
                $table->increments('id_absensi');
                $table->integer('id_siswa');
                $table->date('tanggal');
                $table->dateTime('jam_datang')->nullable();
                $table->dateTime('jam_pulang')->nullable();
                $table->integer('status')->default(0);
                $table->string('keterangan')->nullable();
            });
        }
    }

    public function test_absensi_module_shows_rombel_then_students_then_attendance_history(): void
    {
        $admin = User::factory()->create([
            'role' => 7,
        ]);

        DB::table('kelas')->insert(['id_kelas' => 1, 'kelas' => 12]);
        DB::table('jurusan')->insert(['id_jurusan' => 1, 'nama_jurusan' => 'RPL']);
        DB::table('rombel')->insert([
            ['id_rombel' => 1, 'nama_rombel' => 'XII RPL 1'],
            ['id_rombel' => 2, 'nama_rombel' => 'XII RPL 2'],
        ]);

        DB::table('siswa')->insert([
            [
                'nis' => 1001,
                'nama_siswa' => 'Andi',
                'id_kelas' => 1,
                'id_jurusan' => 1,
                'id_rombel' => 1,
                'tahun_ajaran' => '2025/2026',
            ],
            [
                'nis' => 1002,
                'nama_siswa' => 'Budi',
                'id_kelas' => 1,
                'id_jurusan' => 1,
                'id_rombel' => 1,
                'tahun_ajaran' => '2025/2026',
            ],
            [
                'nis' => 1003,
                'nama_siswa' => 'Cici',
                'id_kelas' => 1,
                'id_jurusan' => 1,
                'id_rombel' => 2,
                'tahun_ajaran' => '2025/2026',
            ],
        ]);

        DB::table('absensi')->insert([
            [
                'id_siswa' => 1001,
                'tanggal' => now()->toDateString(),
                'jam_datang' => now(),
                'jam_pulang' => now(),
                'status' => 1,
                'keterangan' => null,
            ],
            [
                'id_siswa' => 1003,
                'tanggal' => now()->toDateString(),
                'jam_datang' => null,
                'jam_pulang' => null,
                'status' => 2,
                'keterangan' => 'Izin sakit',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.module', 'absensi'));
        $response->assertOk();
        $response->assertSee('XII RPL 1');
        $response->assertSee('XII RPL 2');
        $response->assertDontSee('Andi');

        $studentListResponse = $this->actingAs($admin)->get(route('admin.module', [
            'module' => 'absensi',
            'rombel' => 1,
        ]));
        $studentListResponse->assertOk();
        $studentListResponse->assertSee('Kembali ke Daftar Rombel');
        $studentListResponse->assertSee('Andi');
        $studentListResponse->assertSee('Budi');
        $studentListResponse->assertDontSee('Cici');
        $studentListResponse->assertDontSee('Hadir');

        $historyResponse = $this->actingAs($admin)->get(route('admin.module', [
            'module' => 'absensi',
            'rombel' => 1,
            'student' => 1001,
        ]));
        $historyResponse->assertOk();
        $historyResponse->assertSee('Kembali ke Daftar Siswa');
        $historyResponse->assertSee('Andi');
        $historyResponse->assertSee('Hadir');
        $historyResponse->assertDontSee('Izin sakit');
    }
}
