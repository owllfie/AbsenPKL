@extends('layouts.admin')

@section('title', 'Catatan Bimbingan')
@section('admin_title', 'Catatan Bimbingan')

@section('admin_content')
    <section class="page-panel">
        <style>
            .bimbingan-toolbar {
                margin: 0 1.5rem 1.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .bimbingan-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
                gap: 1.5rem;
                margin: 0 1.5rem 1.5rem;
            }
            .note-card {
                background: #ffffff;
                border: 1px solid var(--line);
                border-radius: 1.5rem;
                padding: 1.5rem;
                box-shadow: var(--shadow);
                display: flex;
                flex-direction: column;
                position: relative;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .note-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 20px 40px -15px rgba(0,0,0,0.08);
                border-color: rgba(217, 119, 6, 0.3);
            }
            .note-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 1.25rem;
                padding-bottom: 1rem;
                border-bottom: 1px dashed var(--line);
            }
            .note-author {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            .note-author strong { color: var(--primary-deep); font-size: 1.15rem; letter-spacing: -0.01em; }
            .note-author small { color: var(--muted); font-weight: 600; font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem; }
            
            .note-body {
                flex-grow: 1;
                display: grid;
                gap: 1.25rem;
            }
            .note-section {
                display: grid;
                gap: 0.4rem;
            }
            .note-section-label {
                font-weight: 800;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--primary-deep);
                display: flex;
                align-items: center;
                gap: 0.4rem;
            }
            .note-text {
                font-size: 0.95rem;
                line-height: 1.6;
                color: var(--text);
                background: #fffdfa;
                padding: 1rem 1.25rem;
                border-radius: 1rem;
                border-left: 3px solid rgba(217, 119, 6, 0.4);
                border-top: 1px solid rgba(217, 119, 6, 0.05);
                border-right: 1px solid rgba(217, 119, 6, 0.05);
                border-bottom: 1px solid rgba(217, 119, 6, 0.05);
            }
            .note-text.tindakan {
                background: #f0f9ff;
                border-left-color: #0ea5e9;
                border-top-color: rgba(14, 165, 233, 0.1);
                border-right-color: rgba(14, 165, 233, 0.1);
                border-bottom-color: rgba(14, 165, 233, 0.1);
                color: #0f172a;
            }
            .note-footer {
                margin-top: 1.5rem;
                padding-top: 1.25rem;
                border-top: 1px solid var(--line);
                display: flex;
                justify-content: space-between;
                align-items: center;
                min-height: 3rem;
            }
            .status-badge {
                padding: 0.4rem 0.8rem;
                border-radius: 999px;
                font-weight: 800;
                font-size: 0.72rem;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                letter-spacing: 0.03em;
            }
            .status-approved { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
            .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

            .btn-approve {
                background: linear-gradient(135deg, var(--primary), #f0a540);
                color: white;
                border: none;
                padding: 0.6rem 1.25rem;
                border-radius: 1rem;
                font-weight: 700;
                font-size: 0.85rem;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
            }
            .btn-approve:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(217, 119, 6, 0.3); }

            .btn-delete-note {
                background: #fef2f2;
                color: #dc2626;
                border: 1px solid #fecaca;
                padding: 0.5rem;
                border-radius: 0.75rem;
                cursor: pointer;
                transition: all 0.2s;
                display: grid;
                place-items: center;
            }
            .btn-delete-note:hover {
                background: #fee2e2;
                color: #b91c1c;
                transform: translateY(-1px);
            }

            .validator-info {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--muted);
                font-size: 0.8rem;
                font-weight: 600;
                background: #f8fafc;
                padding: 0.5rem 1rem;
                border-radius: 999px;
            }

            /* Modal Styles Overrides for local consistency */
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
                font-family: inherit;
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

            @media (max-width: 600px) {
                .bimbingan-grid { grid-template-columns: 1fr; }
            }
        </style>

        <div class="page-panel-header">
            <div>
                <p class="eyebrow">Pusat Bimbingan</p>
                <p class="lede">Kelola catatan perbaikan dan hasil bimbingan siswa selama PKL.</p>
            </div>
            @if($role === 1)
                <button type="button" class="btn-primary" onclick="openBimbinganModal()" style="gap:0.6rem; border-radius: 1rem; padding: 0 1.5rem; min-height: 3.2rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Buat Catatan Baru
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="status-banner" style="margin: 0 1.5rem 1.5rem;">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="status-banner" style="margin: 0 1.5rem 1.5rem; background: #fee2e2; color: #b91c1c; border-color: #fecaca;">
                {{ session('error') }}
            </div>
        @endif

        <div class="bimbingan-grid">
            @forelse($notes as $note)
                <article class="note-card">
                    <div class="note-header">
                        <div class="note-author">
                            <strong>{{ $note->nama_siswa }}</strong>
                            <small>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ \Carbon\Carbon::parse($note->created_at)->translatedFormat('d M Y, H:i') }}
                            </small>
                        </div>
                        @if($note->is_approved)
                            <div class="status-badge status-approved">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Divalidasi
                            </div>
                        @else
                            <div class="status-badge status-pending">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Menunggu
                            </div>
                        @endif
                    </div>

                    <div class="note-body">
                        <div class="note-section">
                            <div class="note-section-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                Poin Perbaikan
                            </div>
                            <div class="note-text">{{ $note->poin_perbaikan }}</div>
                        </div>
                        @if($note->tindakan_lanjut)
                            <div class="note-section">
                                <div class="note-section-label" style="color: #0284c7;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    Tindakan Lanjut
                                </div>
                                <div class="note-text tindakan">{{ $note->tindakan_lanjut }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="note-footer">
                        @if($note->is_approved)
                            <div class="validator-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                {{ $note->nama_pembimbing ?? 'Admin' }}
                            </div>
                        @elseif(in_array($role, [4, 7, 8]))
                            <form action="{{ route('bimbingan.approve', $note->id_catatan) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-approve">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Validasi Catatan
                                </button>
                            </form>
                        @else
                            <div></div> <!-- Spacer -->
                        @endif

                        @if($role === 1 && !$note->is_approved)
                            <form action="{{ route('bimbingan.destroy', $note->id_catatan) }}" method="POST" onsubmit="return confirm('Hapus catatan ini secara permanen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-note" title="Hapus Catatan">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div style="grid-column: 1/-1; text-align: center; padding: 5rem 2rem; color: var(--muted); background: #ffffff; border: 2px dashed var(--line); border-radius: 1.5rem; display: grid; place-items: center; gap: 1rem;">
                    <div style="background: #f8fafc; width: 4rem; height: 4rem; border-radius: 999px; display: grid; place-items: center; color: #94a3b8;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                    </div>
                    <p style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569;">Belum ada catatan bimbingan yang tersedia.</p>
                </div>
            @endforelse
        </div>
    </section>

    <script>
        const bModal = document.getElementById('bimbingan-modal');
        function openBimbinganModal() { if(bModal) bModal.style.display = 'flex'; }
        function closeBimbinganModal() { if(bModal) bModal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == bModal) closeBimbinganModal(); }
    </script>
@endsection

@push('modals')
    @if($role === 1)
        <div id="bimbingan-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Buat Catatan Bimbingan</h2>
                    <button type="button" class="close-modal" onclick="closeBimbinganModal()">&times;</button>
                </div>
                <form action="{{ route('bimbingan.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="poin_perbaikan">Apa yang perlu diperbaiki?</label>
                            <textarea name="poin_perbaikan" id="poin_perbaikan" class="form-control" rows="4" placeholder="Sebutkan hal-hal yang diarahkan pembimbing untuk diperbaiki..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tindakan_lanjut">Rencana Tindakan Lanjut (Opsional)</label>
                            <textarea name="tindakan_lanjut" id="tindakan_lanjut" class="form-control" rows="3" placeholder="Langkah apa yang akan Anda ambil selanjutnya?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeBimbinganModal()">Batal</button>
                        <button type="submit" class="btn-save">Simpan Catatan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endpush
