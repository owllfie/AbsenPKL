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
                flex-wrap: wrap;
            }
            .btn-secondary {
                border: 1px solid rgba(14, 116, 144, 0.18);
                border-radius: 0.9rem;
                padding: 0.85rem 1.2rem;
                font-weight: 800;
                cursor: pointer;
                background: #ecfeff;
                color: #155e75;
            }
            .assessment-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 0.75rem;
            }
            .assessment-item {
                padding: 0.9rem 1rem;
                border-radius: 0.95rem;
                background: #fff;
                border: 1px solid rgba(148, 163, 184, 0.18);
            }
            .assessment-name {
                display: block;
                margin-bottom: 0.25rem;
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--muted);
            }
            .assessment-value {
                font-weight: 800;
                color: var(--primary-deep);
            }
            .assessment-empty {
                padding: 1rem 1.1rem;
                border-radius: 1rem;
                background: #fff7ed;
                border: 1px solid rgba(251, 146, 60, 0.18);
                color: #9a3412;
                font-weight: 600;
            }
            .btn-approve,
            .btn-disapprove {
                border: none;
                border-radius: 0.9rem;
                padding: 0.85rem 1.2rem;
                font-weight: 800;
                cursor: pointer;
            }
            .btn-approve:disabled {
                background: #cbd5e1;
                box-shadow: none;
                cursor: not-allowed;
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
            .modal-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.5);
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                z-index: 60;
            }
            .modal-backdrop.active {
                display: flex;
            }
            .assessment-modal {
                width: min(640px, 100%);
                max-height: calc(100vh - 3rem);
                overflow: auto;
                background: white;
                border-radius: 1.5rem;
                padding: 1.5rem;
                box-shadow: 0 28px 55px -30px rgba(15, 23, 42, 0.8);
            }
            .assessment-modal-head {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                align-items: flex-start;
                margin-bottom: 1rem;
            }
            .assessment-modal-title {
                margin: 0;
                color: var(--primary-deep);
            }
            .modal-close {
                border: none;
                background: transparent;
                font-size: 1.5rem;
                line-height: 1;
                cursor: pointer;
                color: var(--muted);
            }
            .assessment-form-grid {
                display: grid;
                gap: 1rem;
            }
            .assessment-field {
                padding: 1rem;
                border-radius: 1rem;
                background: #fffdfa;
                border: 1px solid rgba(217, 119, 6, 0.08);
            }
            .assessment-options {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                margin-top: 0.75rem;
            }
            .assessment-option {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.65rem 0.85rem;
                border-radius: 999px;
                background: white;
                border: 1px solid rgba(148, 163, 184, 0.22);
                font-weight: 700;
            }
            .assessment-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
                margin-top: 1.25rem;
            }
            @media (max-width: 768px) {
                .agenda-review-head {
                    flex-direction: column;
                }
                .agenda-review-actions {
                    justify-content: stretch;
                }
                .agenda-review-actions form,
                .agenda-review-actions button,
                .btn-secondary,
                .assessment-modal-actions button {
                    width: 100%;
                }
                .assessment-modal-head,
                .assessment-modal-actions {
                    flex-direction: column;
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
                            $hasAssessment = filled($agenda->id_penilaian);
                            $assessmentLabel = static fn ($value) => $value === null ? '-' : ((int) $value === 1 ? 'Baik' : 'Kurang');
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

                                @if ($supportsAssessment)
                                    <div class="agenda-section">
                                        <span class="agenda-section-label">Penilaian</span>
                                        @if ($hasAssessment)
                                            <div class="assessment-grid">
                                                <div class="assessment-item">
                                                    <span class="assessment-name">Senyum</span>
                                                    <span class="assessment-value">{{ $assessmentLabel($agenda->senyum) }}</span>
                                                </div>
                                                <div class="assessment-item">
                                                    <span class="assessment-name">Keramahan</span>
                                                    <span class="assessment-value">{{ $assessmentLabel($agenda->keramahan) }}</span>
                                                </div>
                                                <div class="assessment-item">
                                                    <span class="assessment-name">Penampilan</span>
                                                    <span class="assessment-value">{{ $assessmentLabel($agenda->penampilan) }}</span>
                                                </div>
                                                <div class="assessment-item">
                                                    <span class="assessment-name">Komunikasi</span>
                                                    <span class="assessment-value">{{ $assessmentLabel($agenda->komunikasi) }}</span>
                                                </div>
                                                <div class="assessment-item">
                                                    <span class="assessment-name">Realisasi Kerja</span>
                                                    <span class="assessment-value">{{ $assessmentLabel($agenda->realisasi_kerja) }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="assessment-empty">
                                                Penilaian belum ada. Isi dulu dari tombol penilaian di bawah sebelum agenda bisa di-approve.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="agenda-review-actions">
                                @if ($supportsAssessment)
                                    <button
                                        type="button"
                                        class="btn-secondary"
                                        data-open-assessment="assessment-modal-{{ $agenda->id_agenda }}"
                                    >
                                        {{ $hasAssessment ? 'Ubah Penilaian' : 'Isi Penilaian' }}
                                    </button>
                                @endif

                                @if (! $isApproved)
                                    <form method="POST" action="{{ route($approveRoute, $agenda->id_agenda) }}">
                                        @csrf
                                        <button type="submit" class="btn-approve" @disabled($supportsAssessment && ! $hasAssessment)>Approve</button>
                                    </form>

                                    <form method="POST" action="{{ route($disapproveRoute, $agenda->id_agenda) }}">
                                        @csrf
                                        <button type="submit" class="btn-disapprove">Disapprove</button>
                                    </form>
                                @endif
                            </div>
                        </article>

                        @if ($supportsAssessment)
                            <div class="modal-backdrop" id="assessment-modal-{{ $agenda->id_agenda }}" aria-hidden="true">
                                <div class="assessment-modal" role="dialog" aria-modal="true" aria-labelledby="assessment-title-{{ $agenda->id_agenda }}">
                                    <div class="assessment-modal-head">
                                        <div>
                                            <h3 class="assessment-modal-title" id="assessment-title-{{ $agenda->id_agenda }}">Penilaian {{ $agenda->nama_siswa }}</h3>
                                            <div class="agenda-review-meta">
                                                <span>NIS {{ $agenda->nis }}</span>
                                                <span>{{ \Carbon\Carbon::parse($agenda->tanggal)->locale('id')->translatedFormat('d F Y') }}</span>
                                            </div>
                                        </div>

                                        <button type="button" class="modal-close" data-close-assessment="assessment-modal-{{ $agenda->id_agenda }}" aria-label="Tutup modal">
                                            &times;
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('agenda.review.assessment', $agenda->id_agenda) }}">
                                        @csrf

                                        <div class="assessment-form-grid">
                                            @foreach ([
                                                'senyum' => 'Senyum',
                                                'keramahan' => 'Keramahan',
                                                'penampilan' => 'Penampilan',
                                                'komunikasi' => 'Komunikasi',
                                                'realisasi_kerja' => 'Realisasi Kerja',
                                            ] as $field => $label)
                                                <div class="assessment-field">
                                                    <span class="agenda-section-label">{{ $label }}</span>
                                                    <div class="assessment-options">
                                                        <label class="assessment-option">
                                                            <input type="radio" name="{{ $field }}" value="1" {{ (int) ($agenda->{$field} ?? 1) === 1 ? 'checked' : '' }}>
                                                            <span>Baik</span>
                                                        </label>
                                                        <label class="assessment-option">
                                                            <input type="radio" name="{{ $field }}" value="0" {{ (int) ($agenda->{$field} ?? 1) === 0 && $agenda->{$field} !== null ? 'checked' : '' }}>
                                                            <span>Kurang</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="assessment-modal-actions">
                                            <button type="button" class="btn-disapprove" data-close-assessment="assessment-modal-{{ $agenda->id_agenda }}">Batal</button>
                                            <button type="submit" class="btn-approve">Simpan Penilaian</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
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

    <script>
        document.querySelectorAll('[data-open-assessment]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openAssessment);
                if (!modal) {
                    return;
                }

                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        document.querySelectorAll('[data-close-assessment]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.closeAssessment);
                if (!modal) {
                    return;
                }

                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
            });
        });

        document.querySelectorAll('.modal-backdrop').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target !== modal) {
                    return;
                }

                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.modal-backdrop.active').forEach((modal) => {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
            });
        });
    </script>
@endsection
