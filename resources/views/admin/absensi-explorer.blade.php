@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <style>
            .attendance-stage {
                display: grid;
                gap: 1.5rem;
                padding-inline: 1.5rem;
                margin-bottom: 2rem;
            }
            .attendance-card {
                padding: 1.5rem;
                border-radius: 1.5rem;
                background: #ffffff;
                border: 1px solid var(--line);
                box-shadow: var(--shadow);
            }
            .attendance-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 1rem;
                flex-wrap: wrap;
            }
            .attendance-toolbar h3 {
                margin: 0.25rem 0 0;
                color: var(--primary-deep);
                font-size: 1.75rem;
                letter-spacing: -0.02em;
            }
            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 0.6rem;
                text-decoration: none;
                color: var(--text);
                font-weight: 700;
                padding: 0.75rem 1.25rem;
                border-radius: 1rem;
                background: #ffffff;
                border: 1px solid var(--line);
                transition: all 0.2s;
            }
            .back-link:hover {
                background: #f8f9fa;
                transform: translateX(-3px);
            }
            .attendance-list {
                display: grid;
                gap: 1rem;
            }
            .attendance-list.students {
                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            }
            .attendance-link {
                display: block;
                text-decoration: none;
                color: var(--text);
                padding: 1.25rem;
                border-radius: 1.25rem;
                background: #fffdfa;
                border: 1px solid rgba(170, 117, 51, 0.12);
                transition: all 0.2s;
            }
            .attendance-link:hover {
                transform: translateY(-3px);
                border-color: var(--primary);
                box-shadow: 0 12px 24px rgba(217, 119, 6, 0.12);
                background: #ffffff;
            }
            .attendance-link-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }
            .attendance-link strong {
                display: block;
                font-size: 1.1rem;
                color: var(--primary-deep);
            }
            .attendance-link small {
                display: block;
                margin-top: 0.25rem;
                color: var(--muted);
                font-weight: 500;
            }
            .attendance-link-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-top: 1rem;
            }
            .attendance-chip {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.75rem;
                border-radius: 999px;
                background: rgba(217, 119, 6, 0.06);
                color: var(--primary-deep);
                font-size: 0.75rem;
                font-weight: 700;
                border: 1px solid rgba(217, 119, 6, 0.1);
            }
            .attendance-link-arrow {
                flex: none;
                width: 2.25rem;
                height: 2.25rem;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: rgba(217, 119, 6, 0.08);
                color: var(--primary-deep);
                transition: all 0.2s;
            }
            .attendance-link:hover .attendance-link-arrow {
                background: var(--primary);
                color: white;
            }
            
            /* Search/Filter Consistency */
            .explorer-search-card {
                padding: 1.25rem;
                border-radius: 1.25rem;
                background: #ffffff;
                border: 1px solid var(--line);
                margin-bottom: 1.5rem;
                box-shadow: var(--shadow);
            }
            .input-with-icon {
                position: relative;
                display: flex;
                align-items: center;
                max-width: 420px;
            }
            .input-with-icon .icon {
                position: absolute;
                left: 1rem;
                color: var(--muted);
                pointer-events: none;
                display: flex;
                align-items: center;
            }
            .input-with-icon .form-control-custom {
                width: 100%;
                padding: 0.85rem 1rem 0.85rem 2.75rem;
                min-height: 3.2rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.18);
                background: #fffdfa;
                transition: all 0.2s ease;
                color: var(--text);
                outline: none;
                font-size: 0.95rem;
            }
            .input-with-icon .form-control-custom:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1);
                background-color: #ffffff;
            }

            .student-meta {
                display: grid;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
                padding: 1.25rem;
                border-radius: 1.25rem;
                background: linear-gradient(135deg, #fffdfa, #f8f9fa);
                border: 1px solid rgba(170, 117, 51, 0.1);
            }
            .student-meta h3 { color: var(--primary-deep); margin: 0; font-size: 1.4rem; }
            .student-meta p { margin: 0; color: var(--muted); font-size: 0.95rem; }
            
            /* Table refinements */
            .attendance-history { width: 100%; border-collapse: collapse; }
            .attendance-history th, .attendance-history td {
                padding: 1rem;
                border-bottom: 1px solid rgba(170, 117, 51, 0.08);
                text-align: left;
            }
            .attendance-history th {
                color: var(--primary-deep);
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                font-weight: 800;
            }
            
            .attendance-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.4rem 0.8rem;
                border-radius: 999px;
                font-weight: 800;
                font-size: 0.75rem;
                letter-spacing: 0.02em;
            }
            .attendance-badge.hadir { background: #dcfce7; color: #166534; }
            .attendance-badge.izin { background: #fef3c7; color: #92400e; }
            .attendance-badge.sakit { background: #dbeafe; color: #1d4ed8; }
            .attendance-badge.alpha { background: #fee2e2; color: #b91c1c; }

            .empty-state {
                padding: 3rem 2rem;
                text-align: center;
                border-radius: 1.5rem;
                background: #f8fafc;
                color: var(--muted);
                border: 2px dashed #e2e8f0;
                grid-column: 1 / -1;
            }
            
            @media (max-width: 768px) {
                .attendance-stage { padding-inline: 1rem; }
                .attendance-toolbar { flex-direction: column; align-items: flex-start; }
                .back-link { width: 100%; justify-content: center; }
                .attendance-list.students { grid-template-columns: 1fr; }
            }
        </style>

        <div class="page-panel-header">
            <div style="padding-inline: 0.9rem;">
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>
        </div>

        @if ($activeStudent && $attendanceHistory)
            <div class="attendance-stage">
                <div class="attendance-toolbar">
                    <div>
                        <p class="eyebrow">Riwayat Siswa</p>
                        <h3>{{ $activeStudent->nama_siswa }}</h3>
                    </div>
                    <a
                        href="{{ route('admin.module', ['module' => 'absensi', 'rombel' => $selectedRombel, 'student_search' => $studentSearch ?: null]) }}"
                        class="back-link"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Kembali ke Daftar Siswa
                    </a>
                </div>

                <section class="attendance-card">
                    <div class="student-meta">
                        <h3>{{ $activeStudent->nama_siswa }}</h3>
                        <p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            NIS {{ $activeStudent->nis }} | {{ $activeStudent->nama_rombel ?? 'Tanpa rombel' }}
                        </p>
                        <p>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c3.33 3 8.67 3 12 0v-5"/></svg>
                            {{ $activeStudent->tahun_ajaran ? 'Tahun Ajaran ' . $activeStudent->tahun_ajaran : '' }}
                        </p>
                    </div>

                    <div class="table-wrap" style="padding: 0;">
                        <table class="attendance-history">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Datang</th>
                                    <th>IP / Lokasi Datang</th>
                                    <th>Pulang</th>
                                    <th>IP / Lokasi Pulang</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendanceHistory as $item)
                                    @php
                                        $badge = match ((int) $item->status) {
                                            1 => ['label' => 'Hadir', 'class' => 'hadir'],
                                            2 => ['label' => 'Izin', 'class' => 'izin'],
                                            3 => ['label' => 'Sakit', 'class' => 'sakit'],
                                            default => ['label' => 'Alpha', 'class' => 'alpha'],
                                        };
                                    @endphp
                                    <tr>
                                        <td><strong>{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</strong></td>
                                        <td><span class="attendance-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                                        <td>{{ $item->jam_datang ? \Carbon\Carbon::parse($item->jam_datang)->format('H:i') : '-' }}</td>
                                        <td>
                                            <div class="attendance-detail">
                                                <strong>{{ $item->ip_address_datang ?: '-' }}</strong>
                                                <span>{{ $item->lokasi_datang ?: 'Lokasi belum tercatat' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $item->jam_pulang ? \Carbon\Carbon::parse($item->jam_pulang)->format('H:i') : '-' }}</td>
                                        <td>
                                            <div class="attendance-detail">
                                                <strong>{{ $item->ip_address_pulang ?: '-' }}</strong>
                                                <span>{{ $item->lokasi_pulang ?: 'Lokasi belum tercatat' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $item->keterangan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="empty-cell">Belum ada riwayat absensi untuk siswa ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($attendanceHistory->lastPage() > 1)
                        <div class="history-footer">
                            <nav class="pager" aria-label="Pagination">
                                <a href="{{ $attendanceHistory->previousPageUrl() ?? '#' }}" class="pager-link {{ $attendanceHistory->onFirstPage() ? 'disabled' : '' }}">Prev</a>
                                @for ($page = 1; $page <= $attendanceHistory->lastPage(); $page++)
                                    <a href="{{ $attendanceHistory->url($page) }}" class="pager-link {{ $page === $attendanceHistory->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                                @endfor
                                <a href="{{ $attendanceHistory->nextPageUrl() ?? '#' }}" class="pager-link {{ $attendanceHistory->hasMorePages() ? '' : 'disabled' }}">Next</a>
                            </nav>
                        </div>
                    @endif
                </section>
            </div>
        @elseif ($selectedRombel !== '')
            <div class="attendance-stage">
                <div class="attendance-toolbar">
                    <div>
                        <p class="eyebrow">Daftar Siswa</p>
                        <h3>{{ $activeRombel->nama_rombel ?? 'Rombel' }}</h3>
                    </div>
                    <a href="{{ route('admin.module', ['module' => 'absensi']) }}" class="back-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Kembali ke Daftar Rombel
                    </a>
                </div>

                <div class="explorer-search-card">
                    <form method="GET" class="student-search">
                        <input type="hidden" name="rombel" value="{{ $selectedRombel }}">
                        <div class="input-with-icon">
                            <span class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </span>
                            <input type="search" name="student_search" value="{{ $studentSearch }}" class="form-control-custom" placeholder="Cari nama siswa...">
                        </div>
                    </form>
                </div>

                <div class="attendance-list students">
                    @forelse ($students as $student)
                        <a
                            href="{{ route('admin.module', ['module' => 'absensi', 'rombel' => $selectedRombel, 'student' => $student->nis, 'student_search' => $studentSearch ?: null]) }}"
                            class="attendance-link"
                        >
                            <div class="attendance-link-row">
                                <div>
                                    <strong>{{ $student->nama_siswa }}</strong>
                                    <small>NIS {{ $student->nis }}</small>
                                </div>
                                <span class="attendance-link-arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                            </div>
                            <div class="attendance-link-meta">
                                @if ($student->tahun_ajaran)
                                    <span class="attendance-chip">TA {{ $student->tahun_ajaran }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">
                            <p>Belum ada siswa yang cocok di rombel ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="attendance-stage">
                <div class="attendance-toolbar">
                    <div>
                        <p class="eyebrow">Daftar Rombel</p>
                        <h3>Pilih Rombel</h3>
                    </div>
                </div>

                <div class="attendance-list">
                    @forelse ($rombels as $rombel)
                        <a
                            href="{{ route('admin.module', ['module' => 'absensi', 'rombel' => $rombel->id_rombel]) }}"
                            class="attendance-link"
                        >
                            <div class="attendance-link-row">
                                <div>
                                    <strong>{{ $rombel->nama_rombel }}</strong>
                                    <small>{{ $rombel->student_count }} siswa terdaftar</small>
                                </div>
                                <span class="attendance-link-arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">Belum ada rombel yang bisa diakses.</div>
                    @endforelse
                </div>
            </div>
        @endif
    </section>
@endsection
