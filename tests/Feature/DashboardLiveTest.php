<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect([
            1 => 'siswa',
            7 => 'admin',
            8 => 'superadmin',
        ])->each(fn (string $role, int $id) => Role::query()->create([
            'id_role' => $id,
            'role' => $role,
        ]));

        if (! Schema::hasTable('siswa')) {
            Schema::create('siswa', function (Blueprint $table): void {
                $table->integer('nis')->primary();
                $table->unsignedBigInteger('id_user')->nullable();
                $table->string('nama_siswa')->nullable();
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

    public function test_dashboard_live_returns_real_time_summary_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 7,
        ]);

        DB::table('siswa')->insert([
            ['nis' => 10, 'nama_siswa' => 'Siswa 1'],
            ['nis' => 11, 'nama_siswa' => 'Siswa 2'],
            ['nis' => 12, 'nama_siswa' => 'Siswa 3'],
        ]);

        DB::table('absensi')->insert([
            [
                'id_siswa' => 10,
                'tanggal' => now()->toDateString(),
                'status' => 1,
            ],
            [
                'id_siswa' => 11,
                'tanggal' => now()->toDateString(),
                'status' => 2,
            ],
        ]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.live'));

        $response->assertOk();
        $response->assertJsonPath('summary.total_students', 3);
        $response->assertJsonPath('summary.hadir', 1);
        $response->assertJsonPath('summary.izin', 1);
        $response->assertJsonPath('summary.alpha', 1);
        $response->assertJsonCount(7, 'trend');
    }

    public function test_dashboard_live_is_forbidden_for_student_dashboard(): void
    {
        $studentUser = User::factory()->create([
            'role' => 1,
        ]);

        DB::table('siswa')->insert([
            'nis' => 10,
            'id_user' => $studentUser->id_user,
            'nama_siswa' => 'Siswa 1',
        ]);

        $response = $this->actingAs($studentUser)->getJson(route('dashboard.live'));

        $response->assertForbidden();
    }
}
