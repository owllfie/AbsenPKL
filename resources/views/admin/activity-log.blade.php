@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <style>
            .log-toolbar {
                display: grid;
                gap: 1rem;
            }
            .log-filter-card {
                padding: 1rem;
                border-radius: 1.2rem;
                background: linear-gradient(180deg, rgba(255, 248, 238, 0.92), rgba(255, 252, 247, 0.96));
                border: 1px solid rgba(170, 117, 51, 0.12);
            }
            .log-filter-form {
                display: grid;
                grid-template-columns: minmax(180px, 1.3fr) repeat(3, minmax(120px, 0.6fr)) auto;
                gap: 0.85rem;
                align-items: end;
            }
            .log-filter-field {
                display: grid;
                gap: 0.4rem;
            }
            .log-filter-field small {
                color: var(--muted);
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .log-filter-actions {
                display: flex;
                gap: 0.65rem;
                align-items: center;
            }
            .log-filter-reset {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                min-height: 2.9rem;
                padding: 0.85rem 1rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.14);
                background: #fffdfa;
                color: var(--primary-deep);
                text-decoration: none;
                font-weight: 700;
            }
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
            @media (max-width: 1100px) {
                .log-filter-form {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
                .log-filter-actions {
                    grid-column: 1 / -1;
                }
            }
            @media (max-width: 720px) {
                .log-filter-form {
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

        <div class="log-toolbar">
            <div class="log-filter-card">
                <form method="GET" class="log-filter-form">
                    <label class="log-filter-field">
                        <small>Cari Aktivitas</small>
                        <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nama user, aksi, detail, atau path">
                    </label>
                    <label class="log-filter-field">
                        <small>Modul</small>
                        <select name="module" class="form-control">
                            <option value="">Semua Modul</option>
                            @foreach ($modules as $moduleOption)
                                <option value="{{ $moduleOption }}" @selected($module === $moduleOption)>{{ $moduleOption }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="log-filter-field">
                        <small>Method</small>
                        <select name="method" class="form-control">
                            <option value="">Semua Method</option>
                            @foreach (['GET', 'POST', 'PUT', 'DELETE'] as $methodOption)
                                <option value="{{ $methodOption }}" @selected($method === $methodOption)>{{ $methodOption }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="log-filter-field">
                        <small>Jumlah Baris</small>
                        <select name="per_page" class="form-control">
                            @foreach ([20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} baris</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="log-filter-actions">
                        <button type="submit" class="btn-primary">Terapkan Filter</button>
                        <a href="{{ route('activity-log') }}" class="log-filter-reset">Reset</a>
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
