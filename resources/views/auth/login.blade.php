@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <section class="auth-card" style="max-width: 720px;">
        <div class="brand-block">
            <p class="eyebrow">Absensi PKL</p>
            <p class="lede">Masuk dengan NIS dan password akun yang sudah terdaftar.</p>
        </div>
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

            <button type="submit" class="primary-button">Login</button>
        </form>
    </section>
@endsection
