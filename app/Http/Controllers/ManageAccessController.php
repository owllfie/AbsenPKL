<?php

namespace App\Http\Controllers;

use App\Services\AccessControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManageAccessController extends Controller
{
    public function __construct(private readonly AccessControlService $accessControl)
    {
    }

    public function show(Request $request): View
    {
        abort_unless($this->accessControl->canAccess($request->user(), 'manage-access'), 403);

        return view('admin.manage-access', [
            'pageTitle' => 'Manage Access',
            'pageDescription' => 'Atur hak akses tiap role ke setiap modul aplikasi.',
            'roles' => $this->accessControl->roleAccessMatrix()
                ->reject(fn (array $role): bool => strtolower($role['name']) === 'superadmin')
                ->values(),
            'modules' => $this->accessControl->modules(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($this->accessControl->canAccess($request->user(), 'manage-access'), 403);

        $this->accessControl->updateRoleAccess($request->input('access', []));

        return redirect()
            ->route('manage-access')
            ->with('status', 'Hak akses berhasil diperbarui.');
    }
}
