<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
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

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->password_changed_at === null) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('dashboard'));
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
}
