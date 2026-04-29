@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@php
    $currentDirection = $direction === 'asc' ? 'asc' : 'desc';
    $pageWindowStart = max(1, $rows->currentPage() - 2);
    $pageWindowEnd = min($rows->lastPage(), $rows->currentPage() + 2);
@endphp

@section('admin_content')
    <section class="page-panel">
        <style>
            .page-panel { overflow: visible !important; }
            .header-top-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 1.5rem;
                margin-bottom: 1.5rem;
            }
            .table-toolbar-card {
                margin: 0 1.5rem 1.5rem;
                padding: 1.25rem;
                border-radius: 1.5rem;
                background: #ffffff;
                border: 1px solid var(--line);
                box-shadow: var(--shadow);
            }
            .table-toolbar-form {
                display: grid;
                grid-template-columns: 1.5fr repeat(auto-fit, minmax(140px, 1fr)) auto;
                gap: 1.25rem;
                align-items: end;
            }
            .filter-field {
                display: grid;
                gap: 0.5rem;
            }
            .filter-field small {
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
            .input-with-icon .form-control-custom {
                width: 100%;
                padding: 0.8rem 1rem 0.8rem 2.6rem;
                min-height: 3.2rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.18);
                background: #fffdfa;
                transition: all 0.2s ease;
                color: var(--text);
                outline: none;
                font-size: 0.95rem;
                appearance: none;
            }
            .input-with-icon select.form-control-custom {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23aa7533' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 1rem center;
                background-size: 1.1rem;
                padding-right: 2.8rem;
            }
            .input-with-icon .form-control-custom:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1);
                background-color: #ffffff;
            }
            .action-buttons-group {
                display: flex;
                gap: 0.75rem;
            }
            .btn-action {
                display: inline-flex;
                align-items: center;
                gap: 0.6rem;
                padding: 0 1.5rem;
                min-height: 3.2rem;
                border-radius: 1rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s ease;
                border: 1px solid transparent;
                text-decoration: none;
                white-space: nowrap;
            }
            .btn-action-primary {
                background: linear-gradient(135deg, var(--primary), #f0a540);
                color: white;
                box-shadow: 0 8px 16px rgba(217, 119, 6, 0.15);
            }
            .btn-action-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 20px rgba(217, 119, 6, 0.22);
            }
            .btn-action-secondary {
                background: #ffffff;
                color: var(--text);
                border-color: var(--line);
            }
            .btn-action-secondary:hover {
                background: #f8f9fa;
                border-color: #dee2e6;
                transform: translateY(-1px);
            }
            
            @media (max-width: 1024px) {
                .header-top-row {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .action-buttons-group {
                    width: 100%;
                }
                .btn-action {
                    flex: 1;
                    justify-content: center;
                }
            }
            
            /* Modal Styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.4);
                backdrop-filter: blur(8px);
                overflow-y: auto;
                padding: 2rem 1.5rem;
                align-items: center;
                justify-content: center;
            }
            .modal-content {
                background-color: #fffdfa;
                margin: auto;
                padding: 2.25rem;
                border: 1px solid rgba(170, 117, 51, 0.14);
                border-radius: 1.75rem;
                width: 100%;
                max-width: 580px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                position: relative;
                transform: translateY(0);
                transition: transform 0.3s ease;
            }
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.75rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid rgba(170, 117, 51, 0.08);
            }
            .modal-header h2 { margin: 0; font-size: 1.5rem; color: var(--primary-deep); letter-spacing: -0.02em; }
            .close-modal {
                cursor: pointer;
                font-size: 1.75rem;
                line-height: 1;
                color: var(--muted);
                background: rgba(0, 0, 0, 0.04);
                border: none;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 999px;
                display: grid;
                place-items: center;
                transition: background 0.2s, color 0.2s;
            }
            .close-modal:hover { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
            .form-group { margin-bottom: 1.5rem; }
            .form-group label { display: block; margin-bottom: 0.6rem; font-weight: 700; font-size: 0.85rem; color: var(--primary-deep); text-transform: uppercase; letter-spacing: 0.03em; }
            .form-control {
                width: 100%;
                padding: 0.85rem 1.1rem;
                border: 1.5px solid rgba(170, 117, 51, 0.15);
                border-radius: 1rem;
                background: #fffdfa;
                color: var(--text);
                outline: none;
                font-size: 0.95rem;
                transition: all 0.2s ease;
            }
            .form-control:focus {
                border-color: #d97706;
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1);
                background: white;
            }
            .modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 1rem;
                margin-top: 2.5rem;
                padding-top: 1.5rem;
                border-top: 1px solid rgba(170, 117, 51, 0.1);
            }
            .btn-cancel {
                padding: 0.85rem 1.75rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                font-weight: 700;
                cursor: pointer;
                color: #475569;
                transition: all 0.2s;
            }
            .btn-cancel:hover { background: #e2e8f0; }
            .btn-save {
                padding: 0.85rem 2rem;
                background: linear-gradient(135deg, #d97706, #f0a540);
                color: white;
                border: none;
                border-radius: 1rem;
                font-weight: 800;
                cursor: pointer;
                box-shadow: 0 10px 20px -5px rgba(217, 119, 6, 0.4);
                transition: all 0.2s ease;
            }
            .btn-save:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(217, 119, 6, 0.5); }
            .btn-save:active { transform: translateY(0); }
            .btn-secondary {
                padding: 0.9rem 1.4rem;
                border-radius: 999px;
                border: 1px solid rgba(148, 163, 184, 0.45);
                background: #f8fafc;
                color: #334155;
                font-weight: 800;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .btn-secondary:hover {
                background: #e2e8f0;
                transform: translateY(-1px);
            }
            
            /* Recycle Bin Tabs */
            .table-tabs {
                display: flex;
                gap: 0.5rem;
                margin: 0 1.5rem 1rem;
            }
            .table-tab {
                padding: 0.6rem 1.25rem;
                border-radius: 999px;
                font-weight: 700;
                font-size: 0.85rem;
                text-decoration: none;
                color: var(--muted);
                transition: all 0.2s;
                border: 1px solid transparent;
            }
            .table-tab.active {
                background: var(--primary-deep);
                color: white;
            }
            .table-tab:not(.active):hover {
                background: rgba(0, 0, 0, 0.05);
                color: var(--text);
            }
            .table-tab.tab-trash.active { background: #be123c; }
            .table-tab.tab-trash:not(.active):hover { color: #be123c; }

            /* History Styles */
            .history-item {
                padding: 1rem;
                border-radius: 1rem;
                background: #fffdfa;
                border: 1px solid rgba(170, 117, 51, 0.1);
                margin-bottom: 1rem;
            }
            .history-meta {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.5rem;
                font-size: 0.75rem;
                font-weight: 700;
                color: var(--muted);
            }
            .history-diff {
                font-size: 0.85rem;
                display: grid;
                gap: 0.25rem;
            }
            .diff-row { display: flex; gap: 0.5rem; align-items: center; }
            .diff-old { color: #be123c; text-decoration: line-through; opacity: 0.7; }
            .diff-new { color: #15803d; font-weight: 700; }
            .btn-revert {
                padding: 0.35rem 0.75rem;
                border-radius: 0.5rem;
                background: #f1f5f9;
                color: #334155;
                font-weight: 700;
                font-size: 0.75rem;
                cursor: pointer;
                border: 1px solid #e2e8f0;
            }
            .btn-revert:hover { background: #e2e8f0; }
        </style>

        <div class="page-panel-header">
            <div class="header-top-row">
                <div>
                    <p class="eyebrow">{{ $pageTitle }}</p>
                    <p class="lede">{{ $pageDescription }}</p>
                </div>

                <div class="action-buttons-group">
                    @if (!$showTrash)
                        @if (in_array($module, ['users', 'siswa'], true))
                            <button type="button" class="btn-action btn-action-secondary" onclick="openImportModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Import Excel
                            </button>
                        @endif

                        <button type="button" class="btn-action btn-action-primary" onclick="openCreateModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Tambah {{ $pageTitle }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="table-tabs">
            <a href="{{ request()->fullUrlWithQuery(['trash' => null, 'page' => 1]) }}" class="table-tab {{ !request()->boolean('trash') ? 'active' : '' }}">
                Data Aktif
            </a>
            <a href="{{ request()->fullUrlWithQuery(['trash' => 1, 'page' => 1]) }}" class="table-tab tab-trash {{ request()->boolean('trash') ? 'active' : '' }}">
                Recycle Bin
            </a>
        </div>

        <div class="table-toolbar-card">
            <form method="GET" class="table-toolbar-form" data-live-search>
                <div class="filter-field">
                    <small>Cari Data</small>
                    <div class="input-with-icon">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input type="search" name="search" value="{{ $search }}" autocomplete="off" class="form-control-custom" placeholder="Cari apa saja...">
                    </div>
                </div>

                @foreach ($filters as $filter)
                    <div class="filter-field">
                        <small>{{ $filter['label'] }}</small>
                        <div class="input-with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            </span>
                            <select name="{{ $filter['key'] }}" class="form-control-custom">
                                <option value="">Semua</option>
                                @foreach ($filter['options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(($filterValues[$filter['key']] ?? '') === $option['value'])>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach

                <div class="filter-field">
                    <small>Baris</small>
                    <div class="input-with-icon">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16h8"/><path d="M7 11h12"/><path d="M7 6h3"/></svg>
                        </span>
                        <select name="per_page" class="form-control-custom">
                            @foreach ([10, 20, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} baris</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="filter-actions" style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="{{ url()->current() }}" class="btn-action btn-action-secondary" style="min-height: 3.2rem; padding: 0 1rem;" title="Reset Filter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </a>
                </div>

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $currentDirection }}">
            </form>
        </div>

        @if (session('success'))
            <div class="status-banner">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="status-banner" style="background: #fee2e2; color: #dc2626; border-color: #fecaca;">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            @php
                                $isActiveSort = $sort === $column['key'];
                                $nextDirection = $isActiveSort && $currentDirection === 'asc' ? 'desc' : 'asc';
                                $sortUrl = request()->fullUrlWithQuery([
                                    'sort' => $column['key'],
                                    'direction' => $nextDirection,
                                    'page' => 1,
                                ]);
                            @endphp
                            <th>
                                @if ($column['sortable'])
                                    <a
                                        href="{{ $sortUrl }}"
                                        class="sort-link {{ $isActiveSort ? 'active' : '' }}"
                                        aria-label="Urutkan berdasarkan {{ $column['label'] }} {{ $nextDirection === 'asc' ? 'menaik' : 'menurun' }}"
                                    >
                                        <span>{{ $column['label'] }}</span>
                                        <span class="sort-indicator" aria-hidden="true">{!! $isActiveSort ? ($currentDirection === 'asc' ? '&uarr;' : '&darr;') : '&varr;' !!}</span>
                                    </a>
                                @else
                                    <span>{{ $column['label'] }}</span>
                                @endif
                            </th>
                        @endforeach
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($columns as $column)
                                <td>
                                    @if (in_array($column['key'], ['instruktur_status', 'pembimbing_status'], true))
                                        @php
                                            $approved = ($row[$column['key']] ?? '') === 'Approved';
                                        @endphp
                                        <span style="display:inline-flex; align-items:center; padding:0.35rem 0.65rem; border-radius:999px; font-size:0.75rem; font-weight:800; {{ $approved ? 'background:#dcfce7; color:#166534;' : 'background:#fef3c7; color:#92400e;' }}">
                                            {{ $row[$column['key']] ?? 'Not Approved' }}
                                        </span>
                                    @else
                                        {{ $row[$column['key']] ?? 'null' }}
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <div class="table-row-actions">
                                    @if ($showTrash)
                                        <form action="{{ route('admin.module.restore', ['module' => $module, 'id' => $row[$primaryKey]]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn-sm" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Restore</button>
                                        </form>

                                        <form action="{{ route('admin.module.force-delete', ['module' => $module, 'id' => $row[$primaryKey]]) }}" method="POST" onsubmit="return confirm('Hapus permanen data ini? Tindakan ini tidak bisa dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-sm btn-delete">Force Delete</button>
                                        </form>
                                    @else
                                        @if ($module === 'users')
                                            <form action="{{ route('admin.users.reset-password', $row[$primaryKey]) }}" method="POST" onsubmit="return confirm('Reset password user ini?')">
                                                @csrf
                                                <button type="submit" class="btn-sm btn-reset">PW</button>
                                            </form>
                                        @endif
                                        
                                        @if ($module === 'agenda')
                                            <button type="button" class="btn-sm" style="background: #fef9c3; color: #854d0e; border: 1px solid #fde047;" onclick="openDetailModal({{ json_encode($row) }})">Det</button>
                                        @endif
                                        
                                        <button type="button" class="btn-sm" style="background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc;" onclick="openHistoryModal('{{ $row[$primaryKey] }}')">Hist</button>
                                        <button type="button" class="btn-sm btn-edit" onclick="openEditModal({{ json_encode($row) }})">Edit</button>

                                        <form action="{{ route('admin.module.destroy', ['module' => $module, 'id' => $row[$primaryKey]]) }}" method="POST" onsubmit="return confirm('Pindahkan ke Recycle Bin?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-sm btn-delete">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="empty-cell">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p class="table-summary">
                Menampilkan {{ $rows->firstItem() ?? 0 }}-{{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} data
            </p>

            @if ($rows->lastPage() > 1)
                <nav class="pager" aria-label="Pagination">
                    <a
                        href="{{ $rows->previousPageUrl() ?? '#' }}"
                        class="pager-link {{ $rows->onFirstPage() ? 'disabled' : '' }}"
                        @if ($rows->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                    >
                        Prev
                    </a>

                    @for ($page = $pageWindowStart; $page <= $pageWindowEnd; $page++)
                        <a
                            href="{{ $rows->url($page) }}"
                            class="pager-link {{ $page === $rows->currentPage() ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $rows->nextPageUrl() ?? '#' }}"
                        class="pager-link {{ $rows->hasMorePages() ? '' : 'disabled' }}"
                        @if (! $rows->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                    >
                        Next
                    </a>
                </nav>
            @endif
        </div>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-live-search]');
            if (!form) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const perPageSelect = form.querySelector('select[name="per_page"]');
            const filterSelects = form.querySelectorAll('.table-filter select');
            let debounceId;

            searchInput?.addEventListener('input', () => {
                window.clearTimeout(debounceId);
                debounceId = window.setTimeout(() => {
                    form.submit();
                }, 250);
            });

            perPageSelect?.addEventListener('change', () => {
                form.submit();
            });

            filterSelects.forEach((select) => {
                select.addEventListener('change', () => {
                    form.submit();
                });
            });
        })();
    </script>
@endsection

@push('modals')
    <div id="crud-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Tambah {{ $pageTitle }}</h2>
                <button type="button" class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="crud-form" method="POST">
                @csrf
                <div id="method-container"></div>
                <div class="modal-body">
                    @foreach ($formFields as $field)
                        <div class="form-group">
                            <label for="field-{{ $field['key'] }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'select')
                                <select name="{{ $field['key'] }}" id="field-{{ $field['key'] }}" class="form-control" @disabled($field['disabled'] ?? false) required>
                                    <option value="" disabled selected hidden>Pilih {{ $field['label'] }}</option>
                                    @foreach ($field['options'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'textarea')
                                <textarea name="{{ $field['key'] }}" id="field-{{ $field['key'] }}" class="form-control" rows="3" @disabled($field['disabled'] ?? false) required></textarea>
                            @else
                                <input type="{{ $field['type'] }}" name="{{ $field['key'] }}" id="field-{{ $field['key'] }}" class="form-control" @disabled($field['disabled'] ?? false) required>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="detail-modal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 id="detail-title">Detail Agenda</h2>
                <button type="button" class="close-modal" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detail-content" style="display: grid; gap: 1rem; max-height: 70vh; overflow-y: auto; padding-right: 0.5rem;">
                <!-- Content will be injected by JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

    @if (in_array($module, ['users', 'siswa'], true))
        <div id="import-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Import {{ $pageTitle }}</h2>
                    <button type="button" class="close-modal" onclick="closeImportModal()">&times;</button>
                </div>
                <form action="{{ route('admin.module.import', ['module' => $module]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="display: grid; gap: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="import-file">File Import</label>
                            <input type="file" name="file" id="import-file" class="form-control" accept=".xlsx,.csv,text/csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeImportModal()">Batal</button>
                        <button type="submit" class="btn-save">Import</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div id="history-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Riwayat Pembaruan</h2>
                <button type="button" class="close-modal" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body" id="history-content" style="max-height: 70vh; overflow-y: auto;">
                <p style="text-align: center; color: var(--muted); padding: 2rem;">Memuat riwayat...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeHistoryModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('crud-modal');
        const form = document.getElementById('crud-form');
        const modalTitle = document.getElementById('modal-title');
        const methodContainer = document.getElementById('method-container');
        const importModal = document.getElementById('import-modal');
        const historyModal = document.getElementById('history-modal');
        const historyContent = document.getElementById('history-content');
        const module = @json($module);
        const primaryKey = @json($primaryKey);

        function openCreateModal() {
            modalTitle.textContent = `Tambah ${@json($pageTitle)}`;
            form.action = `/admin/${module}`;
            methodContainer.innerHTML = '';
            form.reset();
            modal.style.display = 'flex';
        }

        function openEditModal(row) {
            modalTitle.textContent = `Edit ${@json($pageTitle)}`;
            form.action = `/admin/${module}/${row[primaryKey]}`;
            methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            form.reset();
            
            // Map row data to form fields
            @foreach ($formFields as $field)
                if (row.hasOwnProperty(@json($field['key']))) {
                    const input = document.getElementById('field-' + @json($field['key']));
                    if (input) {
                        input.value = row[@json($field['key'])];
                    }
                }
            @endforeach
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function openImportModal() {
            if (importModal) {
                importModal.style.display = 'flex';
            }
        }

        function closeImportModal() {
            if (importModal) {
                importModal.style.display = 'none';
            }
        }

        async function openHistoryModal(id) {
            historyModal.style.display = 'flex';
            historyContent.innerHTML = '<p style="text-align: center; color: var(--muted); padding: 2rem;">Memuat riwayat...</p>';
            
            try {
                const response = await fetch(`/admin/${module}/${id}/history`);
                const data = await response.json();
                
                if (data.length === 0) {
                    historyContent.innerHTML = '<p style="text-align: center; color: var(--muted); padding: 2rem;">Belum ada riwayat pembaruan untuk data ini.</p>';
                    return;
                }
                
                let html = '';
                data.forEach(h => {
                    const oldVals = JSON.parse(h.old_values);
                    const newVals = JSON.parse(h.new_values);
                    const date = new Date(h.created_at).toLocaleString('id-ID');
                    
                    html += `
                        <div class="history-item">
                            <div class="history-meta">
                                <span>Oleh: ${h.user_name}</span>
                                <span>${date}</span>
                            </div>
                            <div class="history-diff">
                                ${Object.keys(newVals).map(key => `
                                    <div style="margin-bottom:0.5rem;">
                                        <small style="display:block; font-weight:800; color:var(--muted); text-transform:uppercase; font-size:0.6rem;">${key}</small>
                                        <div class="diff-row">
                                            <span class="diff-old">${oldVals[key] || '(kosong)'}</span>
                                            <span style="color:var(--muted); font-size:0.7rem;">&rarr;</span>
                                            <span class="diff-new">${newVals[key]}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            <div style="margin-top: 1rem; text-align: right;">
                                <form action="/admin/history/${h.id}/revert" method="POST" onsubmit="return confirm('Kembalikan data ke versi ini?')">
                                    @csrf
                                    <button type="submit" class="btn-revert">Revert ke Versi Ini</button>
                                </form>
                            </div>
                        </div>
                    `;
                });
                historyContent.innerHTML = html;
                
            } catch (error) {
                historyContent.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 2rem;">Gagal memuat riwayat.</p>';
            }
        }

        function closeHistoryModal() {
            historyModal.style.display = 'none';
        }

        const detailModal = document.getElementById('detail-modal');
        const detailContent = document.getElementById('detail-content');

        function openDetailModal(row) {
            const labels = {
                student_name: 'Nama Siswa',
                tanggal: 'Tanggal',
                rencana_pekerjaan: 'Rencana Pekerjaan',
                realisasi_pekerjaan: 'Realisasi Pekerjaan',
                penugasan_khusus_dari_atasan: 'Penugasan Khusus',
                penemuan_masalah: 'Penemuan Masalah',
                catatan: 'Catatan',
                senyum_label: 'Rating: Senyum',
                keramahan_label: 'Rating: Keramahan',
                penampilan_label: 'Rating: Penampilan',
                komunikasi_label: 'Rating: Komunikasi',
                realisasi_kerja_label: 'Rating: Realisasi Kerja'
            };

            let html = '';
            
            // Show core fields first
            const order = ['student_name', 'tanggal', 'rencana_pekerjaan', 'realisasi_pekerjaan', 'penugasan_khusus_dari_atasan', 'penemuan_masalah', 'catatan'];
            order.forEach(key => {
                if (row[key]) {
                    html += `
                        <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                            <label style="display:block; font-weight:800; font-size:0.7rem; color:var(--muted); text-transform:uppercase; margin-bottom:0.25rem;">${labels[key] || key}</label>
                            <div style="font-size:0.95rem; white-space:pre-wrap; line-height:1.5;">${row[key]}</div>
                        </div>
                    `;
                }
            });

            // Show ratings
            const ratings = ['senyum_label', 'keramahan_label', 'penampilan_label', 'komunikasi_label', 'realisasi_kerja_label'];
            let ratingsHtml = '';
            ratings.forEach(key => {
                if (row[key]) {
                    ratingsHtml += `
                        <div style="background:#f8fafc; padding:0.5rem 1rem; border-radius:0.5rem; border:1px solid #f1f5f9;">
                            <label style="display:block; font-weight:800; font-size:0.6rem; color:var(--muted); text-transform:uppercase; margin-bottom:0.1rem;">${labels[key]}</label>
                            <div style="font-weight:700; color:var(--primary-deep); font-size:0.85rem;">${row[key]}</div>
                        </div>
                    `;
                }
            });

            if (ratingsHtml) {
                html += `
                    <div style="margin-top: 1rem;">
                        <label style="display:block; font-weight:800; font-size:0.7rem; color:var(--muted); text-transform:uppercase; margin-bottom:0.75rem;">Penilaian Instruktur</label>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem;">${ratingsHtml}</div>
                    </div>
                `;
            }

            detailContent.innerHTML = html;
            detailModal.style.display = 'flex';
        }

        function closeDetailModal() {
            detailModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == detailModal) {
                closeDetailModal();
            }
            if (event.target == importModal) {
                closeImportModal();
            }
        }
    </script>
@endpush
