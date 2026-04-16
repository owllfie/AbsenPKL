@extends('layouts.admin')

@section('admin_title', 'Jurnal Agenda Harian')

@section('admin_content')
<section class="page-panel">
    <style>
        .agenda-split-layout {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            padding: 1rem;
        }
        
        /* Left Side: Form */
        .agenda-form-side {
            flex: 0 0 400px;
            position: sticky;
            top: 1rem;
        }
        
        /* Right Side: History */
        .agenda-history-side {
            flex: 1;
            min-width: 0; /* Prevents table from breaking flexbox */
        }

        .form-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .history-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 4px 15px -3px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--primary-deep);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.2s;
            resize: vertical;
        }

        .form-control:focus {
            outline: none;
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #d97706, #f0a540);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(217, 119, 6, 0.3);
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 1024px) {
            .agenda-split-layout {
                flex-direction: column;
                align-items: stretch;
            }
            .agenda-form-side {
                flex: none;
                position: static;
                width: 100%;
            }
        }
    </style>

    <div class="agenda-split-layout">
        <!-- FORM SIDE -->
        <div class="agenda-form-side">
            @if(session('success'))
                <div class="status-banner" style="margin-bottom: 1rem;">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="status-banner" style="background: #fee2e2; color: #991b1b; border-color: #fecaca; margin-bottom: 1rem;">{{ session('error') }}</div>
            @endif

            <div class="form-card">
                <div style="margin-bottom: 1.5rem;">
                    <p class="eyebrow" style="margin-bottom: 0.2rem;">{{ $today }}</p>
                    <h2 style="font-size: 1.25rem; color: var(--primary-deep); margin: 0;">{{ $todayAgenda ? 'Edit Agenda' : 'Input Agenda' }}</h2>
                </div>

                <form action="{{ route('siswa.agenda.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Rencana Pekerjaan <span style="color:red">*</span></label>
                        <textarea name="rencana_pekerjaan" class="form-control" rows="3" required placeholder="Target hari ini...">{{ old('rencana_pekerjaan', $todayAgenda?->rencana_pekerjaan) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Realisasi Pekerjaan</label>
                        <textarea name="realisasi_pekerjaan" class="form-control" rows="3" placeholder="Hasil pengerjaan...">{{ old('realisasi_pekerjaan', $todayAgenda?->realisasi_pekerjaan) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Penugasan Khusus</label>
                        <textarea name="penugasan_khusus_dari_atasan" class="form-control" rows="2" placeholder="Tugas dari atasan...">{{ old('penugasan_khusus_dari_atasan', $todayAgenda?->penugasan_khusus_dari_atasan) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Penemuan Masalah</label>
                        <textarea name="penemuan_masalah" class="form-control" rows="2" placeholder="Masalah/kendala...">{{ old('penemuan_masalah', $todayAgenda?->penemuan_masalah) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan...">{{ old('catatan', $todayAgenda?->catatan) }}</textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        {{ $todayAgenda ? 'Simpan Perubahan' : 'Kirim Agenda' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- HISTORY SIDE -->
        <div class="agenda-history-side">
            <div class="history-card">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--primary-deep); display: flex; align-items: center; gap: 0.5rem;">
                    <span>Riwayat Jurnal</span>
                    <small style="font-weight: normal; color: var(--muted); font-size: 0.8rem;">(Terbaru)</small>
                </h3>
                
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 120px">Tanggal</th>
                                <th>Rencana & Realisasi</th>
                                <th style="width: 90px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $item)
                                <tr>
                                    <td style="vertical-align: top;">
                                        <div style="font-weight: 700; color: var(--primary-deep); font-size: 0.85rem;">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 0.5rem;">
                                            <small style="color: var(--muted); text-transform: uppercase; font-size: 0.65rem; font-weight: 800;">Rencana:</small>
                                            <div style="font-size: 0.85rem; line-height: 1.4;">{{ $item->rencana_pekerjaan }}</div>
                                        </div>
                                        @if($item->realisasi_pekerjaan)
                                        <div>
                                            <small style="color: var(--muted); text-transform: uppercase; font-size: 0.65rem; font-weight: 800;">Realisasi:</small>
                                            <div style="font-size: 0.85rem; line-height: 1.4; color: #475569;">{{ $item->realisasi_pekerjaan }}</div>
                                        </div>
                                        @endif
                                    </td>
                                    <td style="vertical-align: top; text-align: center;">
                                        @if($item->id_instruktur && $item->id_pembimbing)
                                            <span title="Disetujui" style="color: #166534; background: #dcfce7; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.7rem; font-weight: 800;">OK</span>
                                        @else
                                            <span title="Menunggu" style="color: #854d0e; background: #fef9c3; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.7rem; font-weight: 800;">...</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: var(--muted);">Belum ada riwayat agenda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 1.5rem;">
                    {{ $history->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
