@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <style>
            .log-toolbar {
                margin: 0 1.5rem 1.5rem;
            }
            .log-filter-card {
                padding: 1.5rem;
                border-radius: 1.5rem;
                background: #ffffff;
                border: 1px solid var(--line);
                box-shadow: var(--shadow);
            }
            .log-filter-form {
                display: grid;
                grid-template-columns: 1.5fr repeat(3, 1fr) auto;
                gap: 1.25rem;
                align-items: end;
            }
            .log-filter-field {
                display: grid;
                gap: 0.5rem;
            }
            .log-filter-field small {
                color: var(--primary-deep);
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-size: 0.72rem;
            }
            .input-with-icon {
                position: relative;
                display: flex;
                align-items: center;
            }
            .input-with-icon .icon {
                position: absolute;
                left: 0.9rem;
                color: var(--muted);
                pointer-events: none;
                display: flex;
                align-items: center;
            }
            .input-with-icon .form-control {
                width: 100%;
                padding-left: 2.6rem !important;
                padding-right: 1rem;
                min-height: 3.2rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.18);
                background: #fffdfa;
                transition: all 0.2s ease;
                color: var(--text);
                outline: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }
            .input-with-icon select.form-control {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23aa7533' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 1rem center;
                background-size: 1.2rem;
                padding-right: 2.8rem;
            }
            .input-with-icon .form-control:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1);
                background-color: #ffffff;
            }
            .log-filter-actions {
                display: flex;
                gap: 0.75rem;
                align-items: center;
            }
            .btn-apply {
                min-height: 3.2rem;
                padding: 0 1.5rem;
                border-radius: 1rem;
                background: linear-gradient(135deg, var(--primary), #f0a540);
                color: white;
                border: none;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                box-shadow: 0 8px 16px rgba(217, 119, 6, 0.15);
            }
            .btn-apply:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 20px rgba(217, 119, 6, 0.2);
            }
            .btn-reset-alt {
                min-height: 3.2rem;
                padding: 0 1.25rem;
                border-radius: 1rem;
                background: #f8f9fa;
                color: var(--text);
                border: 1px solid var(--line);
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                transition: all 0.2s ease;
            }
            .btn-reset-alt:hover {
                background: #f1f3f5;
                border-color: #dee2e6;
            }
            
            /* ... rest of the existing styles ... */
            .log-user {
                display: grid;
                gap: 0.25rem;
            }
            .log-user strong {
                color: var(--text);
            }
            .log-role {
                color: var(--muted);
                font-size: 0.8rem;
            }
            .log-action {
                display: grid;
                gap: 0.35rem;
                min-width: 260px;
            }
            .log-action strong {
                color: var(--primary-deep);
            }
            .log-action-meta {
                color: var(--muted);
                font-size: 0.8rem;
                line-height: 1.5;
            }
            .log-desc {
                display: grid;
                gap: 0.35rem;
                min-width: 280px;
            }
            .log-desc p {
                margin: 0;
                line-height: 1.55;
            }
            .log-location {
                color: var(--muted);
                font-size: 0.8rem;
            }
            .log-module-chip {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.65rem;
                border-radius: 999px;
                background: rgba(217, 119, 6, 0.08);
                color: var(--primary-deep);
                font-size: 0.76rem;
                font-weight: 800;
            }
            @media (max-width: 1200px) {
                .log-filter-form {
                    grid-template-columns: 1fr 1fr;
                }
                .log-filter-actions {
                    grid-column: 1 / -1;
                    justify-content: flex-end;
                }
            }
            @media (max-width: 720px) {
                .log-filter-form {
                    grid-template-columns: 1fr;
                }
                .log-filter-actions {
                    display: grid;
                    grid-template-columns: 1fr auto;
                }
                .btn-apply {
                    justify-content: center;
                }
            }
        </style>

        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>
        </div>

        <div class="log-toolbar">
            <div class="log-filter-card">
                <form method="GET" class="log-filter-form">
                    <div class="log-filter-field">
                        <small>Cari Aktivitas</small>
                        <div class="input-with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </span>
                            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nama, aksi, detail...">
                        </div>
                    </div>
                    <div class="log-filter-field">
                        <small>Modul</small>
                        <div class="input-with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"/></svg>
                            </span>
                            <select name="module" class="form-control">
                                <option value="">Semua Modul</option>
                                @foreach ($modules as $moduleOption)
                                    <option value="{{ $moduleOption }}" @selected($module === $moduleOption)>{{ $moduleOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="log-filter-field">
                        <small>Method</small>
                        <div class="input-with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7"/><path d="m16 16 2 2 4-4"/></svg>
                            </span>
                            <select name="method" class="form-control">
                                <option value="">Semua Method</option>
                                @foreach (['GET', 'POST', 'PUT', 'DELETE'] as $methodOption)
                                    <option value="{{ $methodOption }}" @selected($method === $methodOption)>{{ $methodOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="log-filter-field">
                        <small>Data / Hal</small>
                        <div class="input-with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16h8"/><path d="M7 11h12"/><path d="M7 6h3"/></svg>
                            </span>
                            <select name="per_page" class="form-control">
                                @foreach ([20, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} baris</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="log-filter-actions">
                        <button type="submit" class="btn-apply">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            Filter
                        </button>
                        <a href="{{ route('activity-log') }}" class="btn-reset-alt" title="Reset Filter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Modul</th>
                        <th>Aksi</th>
                        <th>Deskripsi</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <div class="log-user">
                                    <strong>{{ $log->user_name ?? 'System' }}</strong>
                                    <div class="log-role">{{ $log->role_name ?? '-' }}</div>
                                </div>
                            </td>
                            <td><span class="log-module-chip">{{ $log->module_key ?? '-' }}</span></td>
                            <td>
                                <div class="log-action">
                                    <strong>{{ $log->action_label }}</strong>
                                    <div class="log-action-meta">{{ $log->action_detail }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="log-desc">
                                    <p>{{ $log->description }}</p>
                                    @if ($log->location_label)
                                        <div class="log-location">{{ $log->location_label }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">Belum ada aktivitas tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p class="table-summary">
                Menampilkan {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
            </p>

            @if ($logs->lastPage() > 1)
                <nav class="pager" aria-label="Pagination">
                    <a href="{{ $logs->previousPageUrl() ?? '#' }}" class="pager-link {{ $logs->onFirstPage() ? 'disabled' : '' }}">Prev</a>
                    @for ($page = max(1, $logs->currentPage() - 2); $page <= min($logs->lastPage(), $logs->currentPage() + 2); $page++)
                        <a href="{{ $logs->url($page) }}" class="pager-link {{ $page === $logs->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endfor
                    <a href="{{ $logs->nextPageUrl() ?? '#' }}" class="pager-link {{ $logs->hasMorePages() ? '' : 'disabled' }}">Next</a>
                </nav>
            @endif
        </div>
    </section>
@endsection
