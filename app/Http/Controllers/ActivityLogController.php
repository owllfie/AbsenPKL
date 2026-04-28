<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperadmin($request->user());

        $search = trim((string) $request->query('search', ''));
        $module = trim((string) $request->query('module', ''));
        $method = trim((string) $request->query('method', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 20) ?: 20));

        $logs = DB::table('activity_logs')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('path', 'like', "%{$search}%");
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module_key', $module))
            ->when($method !== '', fn ($query) => $query->where('http_method', $method))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $logs->setCollection(
            $logs->getCollection()->map(function (object $log): object {
                $log->properties_array = $this->decodeProperties($log->properties ?? null);
                $log->action_label = $this->humanizeAction($log);
                $log->action_detail = $this->buildActionDetail($log);

                return $log;
            })
        );

        $request->attributes->set('activity_log', [
            'module_key' => 'activity-log',
            'action' => 'activity_log_view',
            'description' => 'Membuka halaman activity log.',
        ]);

        return view('admin.activity-log', [
            'pageTitle' => 'Activity Log',
            'pageDescription' => 'Seluruh aktivitas pengguna yang tercatat di aplikasi.',
            'logs' => $logs,
            'search' => $search,
            'module' => $module,
            'method' => $method,
            'perPage' => $perPage,
            'modules' => DB::table('activity_logs')
                ->select('module_key')
                ->whereNotNull('module_key')
                ->distinct()
                ->orderBy('module_key')
                ->pluck('module_key'),
        ]);
    }

    private function ensureSuperadmin(User $user): void
    {
        abort_unless((int) $user->role === 8, 403);
    }

    private function decodeProperties(?string $properties): array
    {
        if (! $properties) {
            return [];
        }

        $decoded = json_decode($properties, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function humanizeAction(object $log): string
    {
        return match ((string) $log->action) {
            'attendance_check_in' => 'Melakukan absen masuk',
            'attendance_check_out' => 'Melakukan absen pulang',
            'attendance_permission_create' => 'Mengirim izin absensi',
            'activity_log_view' => 'Membuka halaman activity log',
            'attendance_qr_refresh' => 'Memperbarui QR absensi',
            'manage_access_update' => 'Mengubah hak akses',
            default => $this->headlineFromText((string) $log->description ?: (string) $log->action),
        };
    }

    private function buildActionDetail(object $log): string
    {
        $details = [];
        $properties = $log->properties_array ?? [];

        if ($log->module_key) {
            $details[] = 'Modul ' . $log->module_key;
        }

        if ($log->subject_type && $log->subject_id) {
            $details[] = 'Target ' . $log->subject_type . ' #' . $log->subject_id;
        }

        if (! empty($properties['student_nis'])) {
            $details[] = 'Siswa NIS ' . $properties['student_nis'];
        }

        if (! empty($properties['keterangan'])) {
            $details[] = 'Keterangan: ' . $properties['keterangan'];
        }

        $inputSummary = $this->summarizeInput($properties['input'] ?? []);
        if ($inputSummary !== null) {
            $details[] = $inputSummary;
        }

        if ($log->http_method && $log->path) {
            $details[] = $log->http_method . ' /' . ltrim((string) $log->path, '/');
        }

        return $details !== [] ? implode(' | ', $details) : 'Tidak ada detail tambahan.';
    }

    private function summarizeInput(array $input): ?string
    {
        if ($input === []) {
            return null;
        }

        $pairs = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $pairs[] = $key . '=' . $value;

            if (count($pairs) >= 3) {
                break;
            }
        }

        return $pairs !== [] ? 'Input: ' . implode(', ', $pairs) : null;
    }

    private function headlineFromText(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return 'Aktivitas pengguna';
        }

        $headline = preg_replace('/\s+/', ' ', $text) ?? $text;

        return ucfirst(rtrim($headline, '.')) . '.';
    }
}
