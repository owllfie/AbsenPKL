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
                    <a
                        href="{{ $item['key'] === 'manage-access' ? route('manage-access') : route('admin.module', $item['key']) }}"
                        class="sidebar-link {{ ($item['key'] === 'manage-access' && request()->routeIs('manage-access')) || (request()->routeIs('admin.module') && request()->route('module') === $item['key']) ? 'active' : '' }}"
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
