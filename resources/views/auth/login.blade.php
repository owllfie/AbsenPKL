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
                <button type="button" class="primary-button login-tab" data-login-tab="google" style="flex:1; background:#fff; color:var(--text); border:1px solid rgba(170, 117, 51, 0.14); box-shadow:none;">Google</button>
            </div>

            <div class="login-panel" data-login-panel="password">
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

            <div class="login-panel" data-login-panel="google" style="display:none;">
                <div style="display:grid; gap:1rem;">
                    <p style="margin:0; color:var(--muted);">Gunakan email Google yang sudah disimpan pada akun user. Jika belum terhubung, isi email user dulu dari panel admin.</p>

                    @error('google')
                        <small class="error-text">{{ $message }}</small>
                    @enderror

                    <a href="{{ route('login.google.redirect') }}" class="primary-button" style="text-decoration:none; text-align:center;">
                        Login dengan Google
                    </a>
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
            @elseif ($errors->has('google'))
                activate('google');
            @else
                activate('password');
            @endif
        });
    </script>
@endsection
