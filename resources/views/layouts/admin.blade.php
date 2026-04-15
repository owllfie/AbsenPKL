@extends('layouts.app')

@section('body_class', 'dashboard-page')

@php
    $navItems = [
        ['label' => 'Users', 'route' => 'admin.module', 'module' => 'users'],
        ['label' => 'Absensi', 'route' => 'admin.module', 'module' => 'absensi'],
        ['label' => 'Agenda', 'route' => 'admin.module', 'module' => 'agenda'],
        ['label' => 'Siswa', 'route' => 'admin.module', 'module' => 'siswa'],
        ['label' => 'Instruktur', 'route' => 'admin.module', 'module' => 'instruktur'],
        ['label' => 'Pembimbing', 'route' => 'admin.module', 'module' => 'pembimbing'],
        ['label' => 'Kajur', 'route' => 'admin.module', 'module' => 'kajur'],
        ['label' => 'Rombel', 'route' => 'admin.module', 'module' => 'rombel'],
        ['label' => 'Tempat PKL', 'route' => 'admin.module', 'module' => 'tempat-pkl'],
        ['label' => 'Chatbot', 'route' => 'chatbot.index'],
        ['label' => 'Web Setting', 'route' => 'admin.module', 'module' => 'web-setting'],
        ['label' => 'Backup Database', 'route' => 'admin.module', 'module' => 'backup-database'],
    ];
@endphp

@section('content')
    <section class="dashboard-shell">
        <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle-input">

        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand logo-link">
                <div class="brand-mark">PKL</div>
                <div>
                    <strong>PKL Monitor</strong>
                    <p>Super Admin Panel</p>
                </div>
            </a>

            <nav class="sidebar-nav">
                @foreach ($navItems as $item)
                    <a
                        href="{{ isset($item['module']) ? route($item['route'], $item['module']) : route($item['route']) }}"
                        class="sidebar-link {{ isset($item['module']) ? (request()->routeIs('admin.module') && request()->route('module') === $item['module'] ? 'active' : '') : (request()->routeIs($item['route']) ? 'active' : '') }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-title">
                    <label for="sidebar-toggle" class="sidebar-toggle-button" aria-label="Toggle sidebar">
                        <span></span>
                        <span></span>
                        <span></span>
                    </label>

                    <div>
                        <p class="eyebrow">Dashboard Super Admin</p>
                        <h1>@yield('admin_title', 'Ringkasan aktivitas PKL')</h1>
                    </div>
                </div>

                <details class="profile-dropdown">
                    <summary class="profile-trigger">
                        <span class="profile-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="profile-meta">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->roleRelation?->role ?? 'superadmin' }}</small>
                        </span>
                    </summary>

                    <div class="profile-menu">
                        <a href="#" class="profile-link">Change Profile Settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-button">Logout</button>
                        </form>
                    </div>
                </details>
            </header>

            @if (session('status'))
                <div class="status-banner">{{ session('status') }}</div>
            @endif

            @yield('admin_content')
        </div>
    </section>
@endsection
