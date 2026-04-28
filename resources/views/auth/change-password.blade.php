@extends('layouts.app')

@section('title', 'Ganti Password')

@section('content')
    <section class="auth-card">
        <div class="brand-block">
            <p class="eyebrow">Keamanan Akun</p>
            <h3>Ganti password demi keamanan akun!</h3>
            <p class="lede">Pastikan email aktif tetap terisi untuk kebutuhan akun dan notifikasi.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            @method('PUT')

            <label class="field">
                <span>Email aktif</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="nama@email.com" required>
                @error('email')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </label>

            <label class="field">
                <span>Password baru</span>
                <input type="password" name="password" placeholder="New Password" required>
                @error('password')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </label>

            <label class="field">
                <span>Konfirmasi password baru</span>
                <input type="password" name="password_confirmation" placeholder="Confirm New Password" required>
            </label>

            <button type="submit" class="primary-button">Simpan Password Baru</button>
        </form>
    </section>
@endsection
