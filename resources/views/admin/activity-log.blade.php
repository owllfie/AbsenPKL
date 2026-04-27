@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>

            <form method="GET" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:end;">
                <label>
                    <small style="display:block; margin-bottom:0.35rem; color:var(--muted);">Cari</small>
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="User, aksi, path">
                </label>
                <label>
                    <small style="display:block; margin-bottom:0.35rem; color:var(--muted);">Modul</small>
                    <select name="module" class="form-control">
                        <option value="">Semua</option>
                        @foreach ($modules as $moduleOption)
                            <option value="{{ $moduleOption }}" @selected($module === $moduleOption)>{{ $moduleOption }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <small style="display:block; margin-bottom:0.35rem; color:var(--muted);">Method</small>
                    <select name="method" class="form-control">
                        <option value="">Semua</option>
                        @foreach (['GET', 'POST', 'PUT', 'DELETE'] as $methodOption)
                            <option value="{{ $methodOption }}" @selected($method === $methodOption)>{{ $methodOption }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <small style="display:block; margin-bottom:0.35rem; color:var(--muted);">Baris</small>
                    <select name="per_page" class="form-control">
                        @foreach ([20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
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
                                <strong>{{ $log->user_name ?? 'System' }}</strong>
                                <div style="color:var(--muted); font-size:0.8rem;">{{ $log->role_name ?? '-' }}</div>
                            </td>
                            <td>{{ $log->module_key ?? '-' }}</td>
                            <td>
                                <div>{{ $log->action }}</div>
                                <div style="color:var(--muted); font-size:0.8rem;">{{ $log->http_method }} {{ $log->path }}</div>
                            </td>
                            <td>
                                <div>{{ $log->description }}</div>
                                @if ($log->location_label)
                                    <div style="color:var(--muted); font-size:0.8rem;">{{ $log->location_label }}</div>
                                @endif
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
