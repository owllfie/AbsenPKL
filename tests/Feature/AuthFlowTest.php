<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->create([
            'id_role' => 1,
            'role' => 'siswa',
        ]);
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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.module', 'users'));

        $response->assertOk();
        $response->assertSee('Data akun pengguna berdasarkan tabel users.');
    }

    public function test_authenticated_user_can_open_agenda_admin_table(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.module', 'agenda'));

        $response->assertOk();
        $response->assertSee('Gabungan data agenda dan penilaian');
    }
}
