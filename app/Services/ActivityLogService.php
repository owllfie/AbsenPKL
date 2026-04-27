<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityLogService
{
    public function log(array $payload): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        $user = $payload['user'] ?? null;

        DB::table('activity_logs')->insert([
            'user_id' => $user instanceof User ? $user->id_user : ($payload['user_id'] ?? null),
            'user_name' => $user instanceof User ? $user->name : ($payload['user_name'] ?? null),
            'role_name' => $user instanceof User ? ($user->roleRelation?->role ?? null) : ($payload['role_name'] ?? null),
            'module_key' => $payload['module_key'] ?? null,
            'action' => $payload['action'] ?? 'activity.recorded',
            'description' => $payload['description'] ?? 'Aktivitas tercatat.',
            'route_name' => $payload['route_name'] ?? null,
            'http_method' => $payload['http_method'] ?? null,
            'path' => $payload['path'] ?? null,
            'status_code' => $payload['status_code'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'location_label' => $payload['location_label'] ?? null,
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => isset($payload['subject_id']) ? (string) $payload['subject_id'] : null,
            'properties' => isset($payload['properties']) ? json_encode($payload['properties'], JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }
}
