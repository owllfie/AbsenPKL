@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <section class="auth-card" style="max-width: 720px;">
        <div class="brand-block">
            <p class="eyebrow">Absensi PKL</p>
            <p class="lede">Masuk dengan NIS dan password, OTP email, atau akun Google yang sudah terhubung.</p>
        </div>

        @if (session('success'))
            <div class="status-banner">{{ session('success') }}</div>
        @endif

        <div class="login-switcher" style="display:grid; gap:1.25rem;">
            <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <button type="button" class="primary-button login-tab active" data-login-tab="password" style="flex:1;">NIS + Password</button>
                <button type="button" class="primary-button login-tab" data-login-tab="otp" style="flex:1; background:#fff; color:var(--text); border:1px solid rgba(170, 117, 51, 0.14); box-shadow:none;">OTP</button>
            </div>

            <div class="login-panel" data-login-panel="password">
                <div style="display:grid; gap:1rem;">
                    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                        @csrf

                        <label class="field">
                            <span>NIS</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan NIS" required autofocus>
                            @error('name')
                                <small class="error-text">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="field">
                            <span>Password</span>
                            <input type="password" name="password" placeholder="Masukkan password" required>
                            @error('password')
                                <small class="error-text">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="checkbox-row">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ingat saya</span>
                        </label>

                        <button type="submit" class="primary-button">Login dengan Password</button>
                    </form>

                    <div style="display:grid; gap:1rem;">
                        <div style="display:flex; align-items:center; gap:0.75rem; color:var(--muted);">
                            <span style="height:1px; flex:1; background:rgba(170, 117, 51, 0.16);"></span>
                            <span style="font-size:0.85rem;">atau</span>
                            <span style="height:1px; flex:1; background:rgba(170, 117, 51, 0.16);"></span>
                        </div>

                        @error('google')
                            <small class="error-text">{{ $message }}</small>
                        @enderror

                        <a
                            href="{{ route('login.google.redirect') }}"
                            class="primary-button"
                            style="text-decoration:none; text-align:center; display:flex; align-items:center; justify-content:center; gap:0.75rem; background:#fff; color:#1f1f1f; border:1px solid rgba(31, 31, 31, 0.14); box-shadow:none;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" aria-hidden="true" style="width:20px; height:20px; flex:none;">
                                <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.6 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12S17.4 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34 6.1 29.3 4 24 4C13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.3-.4-3.5Z"/>
                                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15 18.9 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34 6.1 29.3 4 24 4c-7.7 0-14.3 4.3-17.7 10.7Z"/>
                                <path fill="#4CAF50" d="M24 44c5.2 0 9.8-2 13.2-5.2l-6.1-5.2c-2 1.5-4.4 2.4-7.1 2.4c-5.2 0-9.6-3.3-11.2-8l-6.5 5C9.7 39.5 16.3 44 24 44Z"/>
                                <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1.1 3.1-3.2 5.6-6.1 7.4l6.1 5.2C38.9 37.3 44 31.3 44 24c0-1.3-.1-2.3-.4-3.5Z"/>
                            </svg>
                            <span>Login dengan Google</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="login-panel" data-login-panel="otp" style="display:none;">
                <div style="display:grid; gap:1.5rem;">
                    <form method="POST" action="{{ route('login.otp.request') }}" class="auth-form">
                        @csrf

                        <label class="field">
                            <span>NIS</span>
                            <input type="text" name="otp_name" value="{{ old('otp_name') }}" placeholder="Masukkan NIS untuk kirim OTP" required>
                            @error('otp_name')
                                <small class="error-text">{{ $message }}</small>
                            @enderror
                        </label>

                        <button type="submit" class="primary-button">Kirim OTP ke Email</button>
                    </form>

                    <form method="POST" action="{{ route('login.otp.verify') }}" class="auth-form">
                        @csrf

                        <label class="field">
                            <span>NIS</span>
                            <input type="text" name="otp_name" value="{{ old('otp_name') }}" placeholder="Masukkan NIS" required>
                        </label>

                        <label class="field">
                            <span>Kode OTP</span>
                            <input type="text" name="otp_code" value="{{ old('otp_code') }}" inputmode="numeric" maxlength="6" placeholder="6 digit OTP" required>
                            @error('otp_code')
                                <small class="error-text">{{ $message }}</small>
                            @enderror
                        </label>

                        <button type="submit" class="primary-button">Verifikasi OTP</button>
                    </form>
                </div>
            </div>

        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('[data-login-tab]');
            const panels = document.querySelectorAll('[data-login-panel]');

            const activate = (name) => {
                tabs.forEach((tab) => {
                    const active = tab.dataset.loginTab === name;
                    tab.classList.toggle('active', active);
                    tab.style.background = active ? '' : '#fff';
                    tab.style.color = active ? '' : 'var(--text)';
                    tab.style.border = active ? '' : '1px solid rgba(170, 117, 51, 0.14)';
                    tab.style.boxShadow = active ? '' : 'none';
                });

                panels.forEach((panel) => {
                    panel.style.display = panel.dataset.loginPanel === name ? '' : 'none';
                });
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activate(tab.dataset.loginTab));
            });

            @if ($errors->has('otp_name') || $errors->has('otp_code'))
                activate('otp');
            @else
                activate('password');
            @endif
        });
    </script>
@endsection
