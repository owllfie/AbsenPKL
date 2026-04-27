<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccessControlService
{
    public function modules(): array
    {
        return [
            'users' => ['label' => 'Users'],
            'absensi' => ['label' => 'Absensi'],
            'attendance-qr' => ['label' => 'QR Absensi'],
            'agenda' => ['label' => 'Agenda'],
            'siswa' => ['label' => 'Siswa'],
            'instruktur' => ['label' => 'Instruktur'],
            'pembimbing' => ['label' => 'Pembimbing'],
            'kajur' => ['label' => 'Kajur'],
            'rombel' => ['label' => 'Rombel'],
            'tempat-pkl' => ['label' => 'Tempat PKL'],
            'chatbot' => ['label' => 'Chatbot'],
            'web-setting' => ['label' => 'Web Setting'],
            'backup-database' => ['label' => 'Backup Database'],
            'manage-access' => ['label' => 'Manage Access'],
            'activity-log' => ['label' => 'Activity Log'],
        ];
    }

    public function superadminOnlyModules(): array
    {
        return [
            'attendance-qr',
            'activity-log',
        ];
    }

    public function defaultAccessMap(): array
    {
        return [
            'superadmin' => array_keys($this->modules()),
            'admin' => [
                'users',
                'absensi',
                'agenda',
                'siswa',
                'instruktur',
                'pembimbing',
                'kajur',
                'rombel',
                'tempat-pkl',
                'chatbot',
            ],
            'kepsek' => ['absensi', 'agenda', 'rombel', 'tempat-pkl'],
            'kesiswaan' => ['absensi', 'rombel', 'tempat-pkl'],
            'pembimbing' => ['agenda', 'absensi'],
            'instruktur' => ['agenda', 'absensi'],
            'kajur' => ['siswa', 'rombel', 'tempat-pkl'],
            'siswa' => ['absensi', 'agenda'],
        ];
    }

    public function syncDefaults(): void
    {
        $modules = array_keys($this->modules());
        $defaultMap = $this->defaultAccessMap();

        Role::query()->get(['id_role', 'role'])->each(function (Role $role) use ($modules, $defaultMap): void {
            $allowedModules = $defaultMap[strtolower($role->role)] ?? [];
            $rows = [];

            foreach ($modules as $moduleKey) {
                $rows[] = [
                    'role_id' => $role->id_role,
                    'module_key' => $moduleKey,
                    'is_allowed' => in_array($moduleKey, $allowedModules, true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('role_accesses')->insertOrIgnore($rows);
        });
    }

    public function allowedModuleKeysForUser(User $user): array
    {
        $this->syncDefaults();

        return DB::table('role_accesses')
            ->where('role_id', $user->role)
            ->where('is_allowed', true)
            ->orderBy('id')
            ->pluck('module_key')
            ->all();
    }

    public function allowedModulesForUser(User $user): array
    {
        $availableModules = $this->modules();
        $allowedKeys = $this->allowedModuleKeysForUser($user);

        return collect($allowedKeys)
            ->filter(fn (string $key): bool => array_key_exists($key, $availableModules))
            ->map(fn (string $key): array => [
                'key' => $key,
                'label' => $availableModules[$key]['label'],
            ])
            ->values()
            ->all();
    }

    public function canAccess(User $user, string $moduleKey): bool
    {
        if (in_array($moduleKey, $this->superadminOnlyModules(), true)) {
            return (int) $user->role === 8;
        }

        return in_array($moduleKey, $this->allowedModuleKeysForUser($user), true);
    }

    public function roleAccessMatrix(): Collection
    {
        $this->syncDefaults();

        $groupedAccess = DB::table('role_accesses')
            ->orderBy('role_id')
            ->orderBy('module_key')
            ->get()
            ->groupBy('role_id');

        $manageableModules = array_diff(array_keys($this->modules()), $this->superadminOnlyModules());

        return Role::query()
            ->where('role', '!=', 'superadmin')
            ->where('id_role', '!=', 8)
            ->orderBy('id_role')
            ->get()
            ->map(function (Role $role) use ($groupedAccess, $manageableModules): array {
                $accessLookup = $groupedAccess
                    ->get($role->id_role, collect())
                    ->pluck('is_allowed', 'module_key')
                    ->map(fn ($value): bool => (bool) $value)
                    ->only($manageableModules)
                    ->all();

                return [
                    'id' => $role->id_role,
                    'name' => $role->role,
                    'access' => $accessLookup,
                ];
            });
    }

    public function updateRoleAccess(array $accessPayload): void
    {
        $this->syncDefaults();

        $modules = array_diff(array_keys($this->modules()), $this->superadminOnlyModules());
        $roles = Role::query()
            ->where('role', '!=', 'superadmin')
            ->where('id_role', '!=', 8)
            ->get(['id_role']);

        DB::transaction(function () use ($accessPayload, $modules, $roles): void {
            foreach ($roles as $role) {
                foreach ($modules as $moduleKey) {
                    DB::table('role_accesses')
                        ->where('role_id', $role->id_role)
                        ->where('module_key', $moduleKey)
                        ->update([
                            'is_allowed' => isset($accessPayload[$role->id_role][$moduleKey]),
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }
}
