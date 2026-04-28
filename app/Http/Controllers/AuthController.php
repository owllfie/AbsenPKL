<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('name', $credentials['name'])
            ->whereNull('deleted_at')
            ->first();

        if (! $user || ! $this->passwordMatches($credentials['password'], $user)) {
            return back()
                ->withErrors([
                    'name' => 'Nama atau password tidak sesuai.',
                ])
                ->onlyInput('name');
        }

        return $this->completeLogin($request, $user, 'Login menggunakan NIS dan password.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function passwordMatches(string $plainPassword, User $user): bool
    {
        $storedPassword = (string) $user->password;
        $passwordInfo = password_get_info($storedPassword);

        if ($passwordInfo['algoName'] !== 'unknown') {
            return Hash::check($plainPassword, $storedPassword);
        }

        if (! hash_equals($storedPassword, $plainPassword)) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($plainPassword),
        ])->save();

        return true;
    }

    private function completeLogin(Request $request, User $user, string $description): RedirectResponse
    {
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $this->activityLog->log([
            'user' => $user,
            'module_key' => 'auth',
            'action' => 'login_success',
            'description' => $description,
            'route_name' => $request->route()?->getName(),
            'http_method' => $request->method(),
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
        ]);

        if ($user->password_changed_at === null) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
    }
}
