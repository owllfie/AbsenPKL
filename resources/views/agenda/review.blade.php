@extends('layouts.admin')

@section('title', 'Review Agenda')
@section('admin_title', 'Review Agenda')

@section('admin_content')
    @php
        $isApprovedTab = $tab === 'approved';
    @endphp

    <section class="page-panel">
        <style>
            .agenda-review-shell {
                display: grid;
                gap: 1.5rem;
            }
            .agenda-review-tabs {
                display: inline-flex;
                gap: 0.75rem;
                padding: 0.5rem;
                border-radius: 1rem;
                background: #fff8ee;
                border: 1px solid rgba(217, 119, 6, 0.12);
            }
            .agenda-review-tab {
                padding: 0.85rem 1.2rem;
                border-radius: 0.85rem;
                text-decoration: none;
                font-weight: 800;
                color: #7c5a29;
            }
            .agenda-review-tab.active {
                background: linear-gradient(135deg, #d97706, #f0a540);
                color: white;
                box-shadow: 0 12px 24px -12px rgba(217, 119, 6, 0.9);
            }
            .agenda-card-grid {
                display: grid;
                gap: 1rem;
            }
            .agenda-review-card {
                background: white;
                border: 1px solid rgba(148, 163, 184, 0.18);
                border-radius: 1.5rem;
                padding: 1.5rem;
                box-shadow: 0 20px 35px -28px rgba(15, 23, 42, 0.55);
            }
            .agenda-review-head {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                align-items: flex-start;
                margin-bottom: 1rem;
            }
            .agenda-review-title {
                margin: 0;
                font-size: 1.05rem;
                color: var(--primary-deep);
            }
            .agenda-review-meta {
                margin-top: 0.35rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                color: var(--muted);
                font-size: 0.82rem;
            }
            .agenda-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.38rem 0.7rem;
                border-radius: 999px;
                font-size: 0.76rem;
                font-weight: 800;
            }
            .agenda-badge.approved {
                background: #dcfce7;
                color: #166534;
            }
            .agenda-badge.pending {
                background: #fef3c7;
                color: #92400e;
            }
            .agenda-section-grid {
                display: grid;
                gap: 0.85rem;
                margin-top: 1rem;
            }
            .agenda-section {
                padding: 1rem 1.1rem;
                border-radius: 1rem;
                background: #fffdfa;
                border: 1px solid rgba(217, 119, 6, 0.08);
            }
            .agenda-section-label {
                display: block;
                margin-bottom: 0.35rem;
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--muted);
            }
            .agenda-section p {
                margin: 0;
                line-height: 1.6;
            }
            .agenda-review-actions {
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
                margin-top: 1.25rem;
            }
            .btn-approve,
            .btn-disapprove {
                border: none;
                border-radius: 0.9rem;
                padding: 0.85rem 1.2rem;
                font-weight: 800;
                cursor: pointer;
            }
            .btn-approve {
                background: linear-gradient(135deg, #15803d, #22c55e);
                color: white;
            }
            .btn-disapprove {
                background: #fff1f2;
                color: #be123c;
                border: 1px solid #fecdd3;
            }
            .agenda-empty {
                text-align: center;
                padding: 3rem 1.5rem;
                color: var(--muted);
                background: white;
                border-radius: 1.5rem;
                border: 1px dashed rgba(148, 163, 184, 0.4);
            }
            @media (max-width: 768px) {
                .agenda-review-head {
                    flex-direction: column;
                }
                .agenda-review-actions {
                    justify-content: stretch;
                }
                .agenda-review-actions form,
                .agenda-review-actions button {
                    width: 100%;
                }
            }
        </style>

        <div class="page-panel-header">
            <div class="header-actions">
                <div>
                    <p class="eyebrow">Agenda {{ $roleLabel }}</p>
                    <p class="lede">Cek jurnal siswa dan ubah status approve langsung dari halaman ini.</p>
                </div>

                <div class="agenda-review-tabs" role="tablist" aria-label="Filter status agenda">
                    <a href="{{ route('agenda.review', ['tab' => 'pending']) }}" class="agenda-review-tab {{ $isApprovedTab ? '' : 'active' }}">Not Approved</a>
                    <a href="{{ route('agenda.review', ['tab' => 'approved']) }}" class="agenda-review-tab {{ $isApprovedTab ? 'active' : '' }}">Approved</a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="status-banner">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="status-banner" style="background: #fee2e2; color: #b91c1c; border-color: #fecaca;">
                {{ session('error') }}
            </div>
        @endif

        <div class="agenda-review-shell">
            @if ($agendas->count())
                <div class="agenda-card-grid">
                    @foreach ($agendas as $agenda)
                        @php
                            $isApproved = $approvalColumn === 'id_instruktur'
                                ? filled($agenda->id_instruktur)
                                : filled($agenda->id_pembimbing);
                        @endphp

                        <article class="agenda-review-card">
                            <div class="agenda-review-head">
                                <div>
                                    <h2 class="agenda-review-title">{{ $agenda->nama_siswa }}</h2>
                                    <div class="agenda-review-meta">
                                        <span>NIS {{ $agenda->nis }}</span>
                                        @if ($agenda->kelas)
                                            <span>Kelas {{ $agenda->kelas }}</span>
                                        @endif
                                        @if ($agenda->nama_perusahaan)
                                            <span>{{ $agenda->nama_perusahaan }}</span>
                                        @endif
                                        <span>{{ \Carbon\Carbon::parse($agenda->tanggal)->locale('id')->translatedFormat('d F Y') }}</span>
                                    </div>
                                </div>

                                <span class="agenda-badge {{ $isApproved ? 'approved' : 'pending' }}">
                                    {{ $isApproved ? 'Approved' : 'Not Approved' }}
                                </span>
                            </div>

                            <div class="agenda-section-grid">
                                <div class="agenda-section">
                                    <span class="agenda-section-label">Rencana Pekerjaan</span>
                                    <p>{{ $agenda->rencana_pekerjaan ?: '-' }}</p>
                                </div>

                                <div class="agenda-section">
                                    <span class="agenda-section-label">Realisasi Pekerjaan</span>
                                    <p>{{ $agenda->realisasi_pekerjaan ?: '-' }}</p>
                                </div>

                                <div class="agenda-section">
                                    <span class="agenda-section-label">Penugasan Khusus</span>
                                    <p>{{ $agenda->penugasan_khusus_dari_atasan ?: '-' }}</p>
                                </div>

                                <div class="agenda-section">
                                    <span class="agenda-section-label">Penemuan Masalah</span>
                                    <p>{{ $agenda->penemuan_masalah ?: '-' }}</p>
                                </div>

                                <div class="agenda-section">
                                    <span class="agenda-section-label">Catatan</span>
                                    <p>{{ $agenda->catatan ?: '-' }}</p>
                                </div>
                            </div>

                            <div class="agenda-review-actions">
                                @if ($isApproved)
                                    <form method="POST" action="{{ route($disapproveRoute, $agenda->id_agenda) }}">
                                        @csrf
                                        <button type="submit" class="btn-disapprove">Disapprove</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route($approveRoute, $agenda->id_agenda) }}">
                                        @csrf
                                        <button type="submit" class="btn-approve">Approve</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div>
                    {{ $agendas->links() }}
                </div>
            @else
                <div class="agenda-empty">
                    Tidak ada agenda pada tab {{ $isApprovedTab ? 'Approved' : 'Not Approved' }}.
                </div>
            @endif
        </div>
    </section>
@endsection
