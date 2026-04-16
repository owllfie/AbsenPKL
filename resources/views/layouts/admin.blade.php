@extends('layouts.app')

@section('body_class', 'dashboard-page')

@php
    $navItems = app(\App\Services\AccessControlService::class)->allowedModulesForUser(auth()->user());
    $roleName = auth()->user()->roleRelation?->role ?? 'user';
@endphp

@section('content')
    <section class="dashboard-shell">
        <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle-input">

        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand logo-link">
                <div class="brand-mark">PKL</div>
                <div>
                    <strong>PKL Monitor</strong>
                    <p>{{ ucfirst($roleName) }} Panel</p>
                </div>
            </a>

            <nav class="sidebar-nav">
                @foreach ($navItems as $item)
                    @php
                        $url = route('admin.module', $item['key']);
                        $isActive = request()->routeIs('admin.module') && request()->route('module') === $item['key'];
                        
                        if ($item['key'] === 'manage-access') {
                            $url = route('manage-access');
                            $isActive = request()->routeIs('manage-access');
                        } elseif ($item['key'] === 'chatbot') {
                            $url = route('chatbot.index');
                            $isActive = request()->routeIs('chatbot.index');
                        } elseif ($item['key'] === 'absensi' && auth()->user()->role == 1) {
                            $url = route('siswa.absensi');
                            $isActive = request()->routeIs('siswa.absensi');
                        } elseif ($item['key'] === 'agenda' && auth()->user()->role == 1) {
                            $url = route('siswa.agenda');
                            $isActive = request()->routeIs('siswa.agenda');
                        }
                    @endphp
                    <a href="{{ $url }}" class="sidebar-link {{ $isActive ? 'active' : '' }}">
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
                        <p class="eyebrow">Dashboard {{ ucfirst($roleName) }}</p>
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
