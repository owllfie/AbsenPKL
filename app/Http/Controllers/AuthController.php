<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function requestOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp_name' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('name', $data['otp_name'])
            ->whereNull('deleted_at')
            ->first();

        if (! $user || empty($user->email)) {
            return back()
                ->withErrors([
                    'otp_name' => 'Akun tidak ditemukan atau email belum terdaftar untuk OTP.',
                ])
                ->withInput();
        }

        if ($user->otp_requested_at && $user->otp_requested_at->gt(now()->subMinute())) {
            return back()
                ->withErrors([
                    'otp_name' => 'OTP baru bisa diminta lagi setelah 1 menit.',
                ])
                ->withInput();
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_requested_at' => now(),
        ])->save();

        Mail::raw(
            "Kode OTP login PKL Monitor Anda: {$otp}. Kode ini berlaku 10 menit.",
            function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Kode OTP Login PKL Monitor');
            }
        );

        return back()->with('success', 'Kode OTP sudah dikirim ke email terdaftar.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp_name' => ['required', 'string'],
            'otp_code' => ['required', 'digits:6'],
        ]);

        $user = User::query()
            ->where('name', $data['otp_name'])
            ->whereNull('deleted_at')
            ->first();

        if (! $user || ! $user->otp_code || ! $user->otp_expires_at) {
            return back()
                ->withErrors([
                    'otp_code' => 'OTP tidak tersedia. Silakan minta OTP baru.',
                ])
                ->withInput();
        }

        if ($user->otp_expires_at->isPast()) {
            $this->clearOtp($user);

            return back()
                ->withErrors([
                    'otp_code' => 'OTP sudah kadaluarsa. Silakan minta ulang.',
                ])
                ->withInput();
        }

        if (! Hash::check($data['otp_code'], $user->otp_code)) {
            return back()
                ->withErrors([
                    'otp_code' => 'Kode OTP tidak valid.',
                ])
                ->withInput();
        }

        $this->clearOtp($user);

        return $this->completeLogin($request, $user, 'Login menggunakan OTP.');
    }

    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $clientId = (string) config('services.google.client_id');
        $redirectUri = (string) config('services.google.redirect');

        abort_unless($clientId !== '' && $redirectUri !== '', 500, 'Google login belum dikonfigurasi.');

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Login Google dibatalkan atau ditolak.']);
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        abort_unless(
            hash_equals((string) $request->session()->pull('google_oauth_state'), (string) $request->input('state')),
            403,
            'State OAuth tidak valid.'
        );

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $request->string('code')->toString(),
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Gagal mengambil token Google.']);
        }

        $accessToken = $tokenResponse->json('access_token');

        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $profileResponse->successful()) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Gagal mengambil profil akun Google.']);
        }

        $profile = $profileResponse->json();
        $googleId = (string) ($profile['sub'] ?? '');
        $email = strtolower((string) ($profile['email'] ?? ''));

        if ($googleId === '' || $email === '') {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Data akun Google tidak lengkap.']);
        }

        $user = User::query()
            ->where(function ($query) use ($googleId, $email): void {
                $query->where('google_id', $googleId)
                    ->orWhere('email', $email);
            })
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Email Google ini belum terhubung dengan akun PKL Monitor.']);
        }

        $user->forceFill([
            'email' => $user->email ?: $email,
            'google_id' => $googleId,
        ])->save();

        return $this->completeLogin($request, $user, 'Login menggunakan Google.');
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

    private function clearOtp(User $user): void
    {
        $user->forceFill([
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();
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
