<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect([
            1 => 'siswa',
            2 => 'kajur',
            3 => 'instruktur',
            4 => 'pembimbing',
            5 => 'kesiswaan',
            6 => 'kepsek',
            7 => 'admin',
            8 => 'superadmin',
        ])->each(fn (string $role, int $id) => Role::query()->create([
            'id_role' => $id,
            'role' => $role,
        ]));
    }

    public function test_first_login_redirects_user_to_change_password_page(): void
    {
        $user = User::factory()->firstLogin()->create([
            'name' => 'budi',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'name' => 'budi',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('password.edit'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_is_blocked_until_password_is_changed(): void
    {
        $user = User::factory()->firstLogin()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('password.edit'));
    }

    public function test_password_change_updates_timestamp_and_hash(): void
    {
        $user = User::factory()->firstLogin()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user)->put('/change-password', [
            'password' => 'new-secret123',
            'password_confirmation' => 'new-secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->password_changed_at);
        $this->assertTrue(Hash::check('new-secret123', $user->fresh()->password));
    }

    public function test_password_change_requires_confirmation(): void
    {
        $user = User::factory()->firstLogin()->create();

        $response = $this->actingAs($user)->from('/change-password')->put('/change-password', [
            'password' => 'new-secret123',
            'password_confirmation' => 'different-secret123',
        ]);

        $response->assertRedirect('/change-password');
        $response->assertSessionHasErrors('password');
    }

    public function test_user_with_changed_password_goes_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'name' => 'sari',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'name' => 'sari',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_user_can_open_users_admin_table(): void
    {
        $user = User::factory()->create([
            'role' => 8,
        ]);

        $response = $this->actingAs($user)->get(route('admin.module', 'users'));

        $response->assertOk();
        $response->assertSee('Users');
    }

    public function test_authenticated_user_can_open_agenda_admin_table(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.module', 'agenda'));

        $response->assertOk();
        $response->assertSee('Gabungan data agenda dan penilaian');
    }

    public function test_admin_cannot_access_web_setting_page(): void
    {
        $user = User::factory()->create([
            'role' => 7,
        ]);

        $response = $this->actingAs($user)->get(route('admin.module', 'web-setting'));

        $response->assertForbidden();
    }

    public function test_superadmin_can_open_manage_access_page(): void
    {
        $user = User::factory()->create([
            'role' => 8,
        ]);

        $response = $this->actingAs($user)->get(route('manage-access'));

        $response->assertOk();
        $response->assertSee('Manage Access');
    }

    public function test_student_dashboard_shows_student_shortcuts_and_only_own_absensi_data(): void
    {
        $user = User::factory()->create([
            'role' => 1,
        ]);

        DB::table('kelas')->insert([
            'id_kelas' => 1,
            'kelas' => 12,
        ]);

        DB::table('users')->insert([
            'id_user' => 99,
            'name' => 'wali',
            'password' => Hash::make('password'),
            'password_changed_at' => now(),
            'role' => 7,
            'remember_token' => 'wali-token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('kajur')->insert([
            'id_kajur' => 1,
            'id_user' => null,
            'nama_kajur' => 'Kajur Test',
        ]);

        DB::table('jurusan')->insert([
            'id_jurusan' => 1,
            'nama_jurusan' => 'RPL',
            'id_kajur' => 1,
        ]);

        DB::table('rombel')->insert([
            'id_rombel' => 1,
            'nama_rombel' => 'XII RPL 1',
            'id_wali' => 99,
        ]);

        DB::table('siswa')->insert([
            'nis' => 10,
            'id_user' => $user->id_user,
            'nama_siswa' => 'Siswa Test',
            'id_kelas' => 1,
            'id_jurusan' => 1,
            'id_rombel' => 1,
            'tahun_ajaran' => '2025/2026',
            'id_tempat' => null,
            'id_pembimbing' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('siswa')->insert([
            'nis' => 11,
            'id_user' => null,
            'nama_siswa' => 'Siswa Lain',
            'id_kelas' => 1,
            'id_jurusan' => 1,
            'id_rombel' => 1,
            'tahun_ajaran' => '2025/2026',
            'id_tempat' => null,
            'id_pembimbing' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('absensi')->insert([
            [
                'id_siswa' => 10,
                'tanggal' => now()->toDateString(),
                'jam_datang' => now(),
                'jam_pulang' => now(),
                'status' => 1,
                'keterangan' => null,
                'foto_bukti' => null,
            ],
        ]);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Dashboard Siswa');
        $dashboardResponse->assertSee('Absensi');
        $dashboardResponse->assertSee('Agenda');

        DB::table('absensi')->insert([
            'id_siswa' => 11,
            'tanggal' => now()->toDateString(),
            'jam_datang' => now(),
            'jam_pulang' => now(),
            'status' => 4,
            'keterangan' => 'Lain',
            'foto_bukti' => null,
        ]);

        $moduleResponse = $this->actingAs($user)->get(route('admin.module', 'absensi'));
        $moduleResponse->assertOk();
        $moduleResponse->assertSee('Siswa Test');
        $moduleResponse->assertDontSee('Siswa Lain');
    }

    public function test_unlinked_pembimbing_is_redirected_to_dashboard_with_error_when_opening_agenda_review(): void
    {
        Schema::create('siswa', function (Blueprint $table): void {
            $table->integer('nis')->primary();
            $table->unsignedBigInteger('id_user')->nullable();
        });

        Schema::create('pembimbing', function (Blueprint $table): void {
            $table->integer('id_pembimbing')->primary();
            $table->unsignedBigInteger('id_user')->nullable();
        });

        $user = User::factory()->create([
            'role' => 4,
        ]);

        $response = $this->actingAs($user)->get(route('agenda.review'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akun Anda belum terhubung ke data pembimbing atau instruktur.');

        $dashboardResponse = $this->actingAs($user)->followingRedirects()->get(route('agenda.review'));
        $dashboardResponse->assertSee('Akun Anda belum terhubung ke data pembimbing atau instruktur.');
    }

    public function test_creating_user_with_pembimbing_role_also_creates_pembimbing_record(): void
    {
        Schema::create('tempat_pkl', function (Blueprint $table): void {
            $table->increments('id_tempat');
            $table->string('nama_perusahaan');
            $table->string('alamat');
        });

        Schema::create('pembimbing', function (Blueprint $table): void {
            $table->increments('id_pembimbing');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_pembimbing');
        });

        Schema::create('instruktur', function (Blueprint $table): void {
            $table->increments('id_instruktur');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_instruktur');
            $table->unsignedInteger('id_tempat');
        });

        $admin = User::factory()->create([
            'role' => 8,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.module.store', 'users'), [
            'name' => 'guru-pembimbing',
            'password' => 'password123',
            'role' => 4,
        ]);

        $response->assertRedirect();

        $createdUser = DB::table('users')->where('name', 'guru-pembimbing')->first();

        $this->assertNotNull($createdUser);
        $this->assertDatabaseHas('pembimbing', [
            'id_user' => $createdUser->id_user,
            'nama_pembimbing' => 'guru-pembimbing',
        ]);
    }

    public function test_creating_user_with_instruktur_role_also_creates_instruktur_record(): void
    {
        Schema::create('tempat_pkl', function (Blueprint $table): void {
            $table->increments('id_tempat');
            $table->string('nama_perusahaan');
            $table->string('alamat');
        });

        Schema::create('pembimbing', function (Blueprint $table): void {
            $table->increments('id_pembimbing');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_pembimbing');
        });

        Schema::create('instruktur', function (Blueprint $table): void {
            $table->increments('id_instruktur');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_instruktur');
            $table->unsignedInteger('id_tempat');
        });

        DB::table('tempat_pkl')->insert([
            'id_tempat' => 1,
            'nama_perusahaan' => 'PT Uji',
            'alamat' => 'Jl. Test',
        ]);

        $admin = User::factory()->create([
            'role' => 8,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.module.store', 'users'), [
            'name' => 'instruktur-baru',
            'password' => 'password123',
            'role' => 3,
            'id_tempat' => 1,
        ]);

        $response->assertRedirect();

        $createdUser = DB::table('users')->where('name', 'instruktur-baru')->first();

        $this->assertNotNull($createdUser);
        $this->assertDatabaseHas('instruktur', [
            'id_user' => $createdUser->id_user,
            'nama_instruktur' => 'instruktur-baru',
            'id_tempat' => 1,
        ]);
    }
}
