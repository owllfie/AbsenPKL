@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <style>
            .settings-shell {
                display: grid;
                gap: 1.25rem;
                padding: 0.75rem 1rem 0;
            }
            .settings-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1.25rem;
            }
            .settings-card {
                padding: 1.35rem;
                border-radius: 1.4rem;
                background: rgba(255, 255, 255, 0.76);
                border: 1px solid rgba(170, 117, 51, 0.12);
            }
            .settings-card h3 {
                margin: 0 0 0.35rem;
                color: var(--primary-deep);
            }
            .settings-card p {
                margin: 0;
                color: var(--muted);
                line-height: 1.6;
            }
            .settings-form {
                display: grid;
                gap: 1rem;
                margin-top: 1.25rem;
            }
            .settings-field {
                display: grid;
                gap: 0.45rem;
            }
            .settings-field label {
                font-weight: 700;
                color: var(--text);
            }
            .settings-field input,
            .settings-field select {
                width: 100%;
                padding: 0.85rem 0.95rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.14);
                background: #fffdfa;
                color: var(--text);
                outline: none;
            }
            .settings-field input:focus,
            .settings-field select:focus {
                border-color: rgba(217, 119, 6, 0.32);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.08);
            }
            .settings-helper {
                font-size: 0.86rem;
                color: var(--muted);
            }
            .settings-check {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                color: var(--muted);
            }
            .settings-check input {
                width: auto;
                margin: 0;
            }
            .settings-submit {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                min-height: 3.2rem;
                padding: 0.95rem 1.2rem;
                border: none;
                border-radius: 1rem;
                background: linear-gradient(135deg, var(--primary), #efac52);
                color: #fff;
                font-weight: 800;
                cursor: pointer;
                box-shadow: 0 18px 34px -24px rgba(180, 83, 9, 0.8);
            }
            .theme-options {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.85rem;
                margin-top: 1rem;
            }
            .theme-option {
                padding: 0.9rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.12);
                background: rgba(255, 253, 250, 0.94);
            }
            .theme-option strong {
                display: block;
                margin-bottom: 0.7rem;
            }
            .theme-swatches {
                display: flex;
                gap: 0.45rem;
            }
            .theme-swatches span {
                width: 1.25rem;
                height: 1.25rem;
                border-radius: 999px;
                border: 1px solid rgba(15, 23, 42, 0.08);
            }
            .logo-current {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
                border-radius: 1.15rem;
                background: linear-gradient(180deg, rgba(255, 248, 238, 0.92), rgba(255, 252, 247, 0.96));
                border: 1px solid rgba(170, 117, 51, 0.12);
            }
            .logo-current img {
                width: 4rem;
                height: 4rem;
                object-fit: cover;
                border-radius: 1rem;
                flex: none;
            }
            @media (max-width: 900px) {
                .settings-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>
        </div>

        <div class="settings-shell">
            <div class="settings-grid">
                <section class="settings-card">
                    <h3>Pengaturan Branding</h3>
                    <p>Perubahan di sini akan mengubah nama web, logo brand, dan warna utama dashboard.</p>

                    <form action="{{ route('admin.web-setting.save') }}" method="POST" enctype="multipart/form-data" class="settings-form">
                        @csrf

                        <div class="settings-field">
                            <label for="web_name">Nama Web</label>
                            <input id="web_name" name="web_name" type="text" value="{{ old('web_name', $settings['web_name']) }}" required>
                        </div>

                        <div class="settings-field">
                            <label for="logo">Logo Web</label>
                            <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp">
                            <span class="settings-helper">Gunakan gambar persegi agar logo tampil rapi di sidebar.</span>
                        </div>

                        @if ($logoUrl)
                            <div class="logo-current">
                                <img src="{{ $logoUrl }}" alt="{{ $settings['web_name'] }} logo">
                                <div>
                                    <strong>Logo aktif</strong>
                                    <div class="settings-helper">Logo ini sedang dipakai di sidebar aplikasi.</div>
                                </div>
                            </div>
                        @endif

                        @if ($logoUrl)
                            <label class="settings-check" for="remove_logo">
                                <input id="remove_logo" name="remove_logo" type="checkbox" value="1" @checked(old('remove_logo'))>
                                <span>Hapus logo saat ini dan kembali ke monogram teks.</span>
                            </label>
                        @endif

                        <div class="settings-field">
                            <label for="theme">Theme</label>
                            <select id="theme" name="theme" required>
                                @foreach ($themeOptions as $key => $theme)
                                    <option value="{{ $key }}" @selected(old('theme', $settings['theme']) === $key)>{{ $theme['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="settings-helper">Theme mengubah warna utama seluruh halaman dashboard.</span>
                        </div>

                        @if ($errors->any())
                            <div class="status-banner" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button type="submit" class="settings-submit">Simpan Pengaturan</button>
                    </form>
                    <div>
                        <h3 style="margin-top:1.4rem;">Pilihan Theme</h3>
                        <div class="theme-options">
                            @foreach ($themeOptions as $key => $theme)
                                <div class="theme-option">
                                    <strong>{{ $theme['label'] }}</strong>
                                    <div class="theme-swatches">
                                        <span style="background: {{ $theme['vars']['--primary'] }}"></span>
                                        <span style="background: {{ $theme['vars']['--bg'] }}"></span>
                                        <span style="background: {{ $theme['vars']['--bg-deep'] }}"></span>
                                        <span style="background: {{ $theme['vars']['--text'] }}"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
