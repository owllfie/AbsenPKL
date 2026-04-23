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
                /* Centering */
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
        </style>

        <div class="page-panel-header">
            <div class="header-actions">
                <div>
                    <p class="eyebrow">{{ $pageTitle }}</p>
                    <p class="lede">{{ $pageDescription }}</p>
                </div>

                <div class="table-header-actions">
                    <form method="GET" class="table-toolbar" data-live-search>
                        <label class="table-search">
                            <span>Cari data</span>
                            <input
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                autocomplete="off"
                            >
                        </label>

                        @foreach ($filters as $filter)
                            <label class="table-filter">
                                <span>{{ $filter['label'] }}</span>
                                <select name="{{ $filter['key'] }}">
                                    <option value="">Semua</option>
                                    @foreach ($filter['options'] as $option)
                                        <option
                                            value="{{ $option['value'] }}"
                                            @selected(($filterValues[$filter['key']] ?? '') === $option['value'])
                                        >
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endforeach

                        <label class="table-page-size">
                            <span>Baris</span>
                            <select name="per_page">
                                @foreach ([10, 20, 50] as $size)
                                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </label>

                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="direction" value="{{ $currentDirection }}">
                    </form>

                    <button type="button" class="btn-primary" onclick="openCreateModal()">
                        Tambah {{ $pageTitle }}
                    </button>
                </div>
            </div>
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
                                    @if ($module === 'users')
                                        <form action="{{ route('admin.users.reset-password', $row[$primaryKey]) }}" method="POST" onsubmit="return confirm('Reset password user ini?')">
                                            @csrf
                                            <button type="submit" class="btn-sm btn-reset">Reset</button>
                                        </form>
                                    @endif
                                    
                                    @if ($module === 'agenda')
                                        <button type="button" class="btn-sm" style="background: #fef9c3; color: #854d0e; border: 1px solid #fde047;" onclick="openDetailModal({{ json_encode($row) }})">Detail</button>
                                    @endif
                                    
                                    <button type="button" class="btn-sm btn-edit" onclick="openEditModal({{ json_encode($row) }})">Edit</button>

                                    <form action="{{ route('admin.module.destroy', ['module' => $module, 'id' => $row[$primaryKey]]) }}" method="POST" onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm btn-delete">Hapus</button>
                                    </form>
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

    <script>
        const modal = document.getElementById('crud-modal');
        const form = document.getElementById('crud-form');
        const modalTitle = document.getElementById('modal-title');
        const methodContainer = document.getElementById('method-container');
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
        }
    </script>
@endpush
