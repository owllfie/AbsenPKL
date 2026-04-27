@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <style>
            .attendance-stage {
                display: grid;
                gap: 1rem;
            }
            .attendance-card {
                max-width: 1080px;
                padding: 1.15rem;
                border-radius: 1.4rem;
                background: rgba(255, 255, 255, 0.74);
                border: 1px solid rgba(170, 117, 51, 0.12);
            }
            .attendance-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1rem;
                flex-wrap: wrap;
            }
            .attendance-toolbar h3 {
                margin: 0.2rem 0 0;
                color: var(--primary-deep);
            }
            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                color: var(--primary-deep);
                font-weight: 700;
                padding: 0.7rem 1rem;
                border-radius: 999px;
                background: rgba(217, 119, 6, 0.08);
                border: 1px solid rgba(217, 119, 6, 0.12);
            }
            .attendance-list {
                display: grid;
                gap: 0.75rem;
            }
            .attendance-list.students {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .attendance-link {
                display: block;
                text-decoration: none;
                color: var(--text);
                padding: 1rem 1.05rem;
                border-radius: 1.05rem;
                background: rgba(255, 253, 250, 0.88);
                border: 1px solid rgba(170, 117, 51, 0.08);
                transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
            }
            .attendance-link:hover {
                transform: translateY(-1px);
                border-color: rgba(217, 119, 6, 0.22);
                box-shadow: 0 18px 28px -24px rgba(180, 83, 9, 0.7);
            }
            .attendance-link-row {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.8rem;
            }
            .attendance-link strong {
                display: block;
            }
            .attendance-link small {
                display: block;
                margin-top: 0.35rem;
                color: var(--muted);
            }
            .attendance-link-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.45rem;
                margin-top: 0.75rem;
            }
            .attendance-chip {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.6rem;
                border-radius: 999px;
                background: rgba(217, 119, 6, 0.08);
                color: var(--primary-deep);
                font-size: 0.76rem;
                font-weight: 700;
            }
            .attendance-link-arrow {
                flex: none;
                width: 2rem;
                height: 2rem;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: rgba(217, 119, 6, 0.08);
                color: var(--primary-deep);
                font-weight: 800;
            }
            .student-search {
                max-width: 420px;
            }
            .student-search input {
                width: 100%;
                padding: 0.85rem 0.95rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.14);
                background: #fffdfa;
                color: var(--text);
                outline: none;
            }
            .student-search input:focus {
                border-color: rgba(217, 119, 6, 0.32);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.08);
            }
            .student-meta {
                display: grid;
                gap: 0.45rem;
                margin-bottom: 1rem;
                padding: 1rem;
                border-radius: 1rem;
                background: linear-gradient(180deg, rgba(255, 248, 238, 0.9), rgba(255, 252, 247, 0.96));
                border: 1px solid rgba(170, 117, 51, 0.1);
            }
            .student-meta h3,
            .student-meta p {
                margin: 0;
            }
            .student-meta h3 {
                color: var(--primary-deep);
            }
            .attendance-history {
                width: 100%;
                border-collapse: collapse;
            }
            .attendance-history th,
            .attendance-history td {
                padding: 0.8rem 0.65rem;
                border-bottom: 1px solid rgba(170, 117, 51, 0.1);
                text-align: left;
                vertical-align: top;
            }
            .attendance-history th {
                color: var(--muted);
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .attendance-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.65rem;
                border-radius: 999px;
                font-weight: 800;
                font-size: 0.75rem;
            }
            .attendance-badge.hadir { background: #dcfce7; color: #166534; }
            .attendance-badge.izin { background: #fef3c7; color: #92400e; }
            .attendance-badge.sakit { background: #dbeafe; color: #1d4ed8; }
            .attendance-badge.alpha { background: #fee2e2; color: #b91c1c; }
            .empty-state {
                padding: 1rem;
                border-radius: 1rem;
                background: rgba(255, 253, 250, 0.84);
                color: var(--muted);
                border: 1px dashed rgba(170, 117, 51, 0.2);
            }
            .history-footer {
                margin-top: 1rem;
            }
            .history-footer .pager {
                justify-content: flex-start;
            }
            @media (max-width: 900px) {
                .attendance-list.students {
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
                        Kembali ke Daftar Siswa
                    </a>
                </div>

                <section class="attendance-card">
                    <div class="student-meta">
                        <h3>{{ $activeStudent->nama_siswa }}</h3>
                        <p>NIS {{ $activeStudent->nis }} | {{ $activeStudent->nama_rombel ?? 'Tanpa rombel' }}</p>
                        <p>{{ $activeStudent->nama_jurusan ?? 'Jurusan belum diatur' }}{{ $activeStudent->tahun_ajaran ? ' | TA ' . $activeStudent->tahun_ajaran : '' }}</p>
                    </div>

                    <div class="table-wrap">
                        <table class="attendance-history">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Datang</th>
                                    <th>Pulang</th>
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
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</td>
                                        <td><span class="attendance-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                                        <td>{{ $item->jam_datang ? \Carbon\Carbon::parse($item->jam_datang)->format('H:i') : '-' }}</td>
                                        <td>{{ $item->jam_pulang ? \Carbon\Carbon::parse($item->jam_pulang)->format('H:i') : '-' }}</td>
                                        <td>{{ $item->keterangan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-cell">Belum ada riwayat absensi untuk siswa ini.</td>
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
                    <a href="{{ route('admin.module', ['module' => 'absensi']) }}" class="back-link">Kembali ke Daftar Rombel</a>
                </div>

                <section class="attendance-card">
                    <form method="GET" class="student-search" style="margin-bottom: 1rem;">
                        <input type="hidden" name="rombel" value="{{ $selectedRombel }}">
                        <input type="search" name="student_search" value="{{ $studentSearch }}" placeholder="Cari nama siswa...">
                    </form>

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
                                    <span class="attendance-link-arrow">></span>
                                </div>
                                <div class="attendance-link-meta">
                                    @if ($student->kelas)
                                        <span class="attendance-chip">Kelas {{ $student->kelas }}</span>
                                    @endif
                                    @if ($student->nama_jurusan)
                                        <span class="attendance-chip">{{ $student->nama_jurusan }}</span>
                                    @endif
                                    @if ($student->tahun_ajaran)
                                        <span class="attendance-chip">TA {{ $student->tahun_ajaran }}</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">Belum ada siswa yang cocok di rombel ini.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @else
            <div class="attendance-stage">
                <div class="attendance-toolbar">
                    <div>
                        <p class="eyebrow">Daftar Rombel</p>
                        <h3>Pilih Rombel</h3>
                    </div>
                </div>

                <section class="attendance-card">
                    <div class="attendance-list">
                        @forelse ($rombels as $rombel)
                            <a
                                href="{{ route('admin.module', ['module' => 'absensi', 'rombel' => $rombel->id_rombel]) }}"
                                class="attendance-link"
                            >
                                <div class="attendance-link-row">
                                    <div>
                                        <strong>{{ $rombel->nama_rombel }}</strong>
                                        <small>{{ $rombel->student_count }} siswa</small>
                                    </div>
                                    <span class="attendance-link-arrow">></span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">Belum ada rombel yang bisa diakses.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </section>
@endsection
