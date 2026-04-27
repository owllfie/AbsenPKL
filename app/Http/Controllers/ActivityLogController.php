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
}
