@extends('layouts.admin')

@section('title', 'Dashboard Siswa')
@section('admin_title', 'Dashboard Siswa')

@section('admin_content')
    <section class="student-quick-actions">
        <a href="{{ route('admin.module', 'absensi') }}" class="student-action-card">
            <span class="panel-label">Aksi Cepat</span>
            <strong>Absensi</strong>
            <p>Buka halaman absensi tanpa perlu cari dari sidebar.</p>
        </a>

        <a href="{{ route('admin.module', 'agenda') }}" class="student-action-card">
            <span class="panel-label">Aksi Cepat</span>
            <strong>Agenda</strong>
            <p>Buka halaman agenda harian dengan satu klik.</p>
        </a>
    </section>

    <section class="student-history-grid">
        <article class="page-panel">
            <div class="page-panel-header">
                <div>
                    <p class="eyebrow">7 Hari Terakhir</p>
                    <p class="lede">Riwayat absensi terbaru milik {{ $student->nama_siswa }}.</p>
                </div>
            </div>

            <div class="student-timeline">
                @forelse ($absensiHistory as $item)
                    <div class="timeline-item">
                        <strong>{{ $item->tanggal }}</strong>
                        <span>Status: {{ $item->status_label }}</span>
                        <span>Datang: {{ $item->jam_datang_label }}</span>
                        <span>Pulang: {{ $item->jam_pulang_label }}</span>
                    </div>
                @empty
                    <p class="timeline-empty">Belum ada absensi dalam 7 hari terakhir.</p>
                @endforelse
            </div>
        </article>

        <article class="page-panel">
            <div class="page-panel-header">
                <div>
                    <p class="eyebrow">7 Hari Terakhir</p>
                    <p class="lede">Riwayat agenda terbaru milik {{ $student->nama_siswa }}.</p>
                </div>
            </div>

            <div class="student-timeline">
                @forelse ($agendaHistory as $item)
                    <div class="timeline-item">
                        <strong>{{ $item->tanggal }}</strong>
                        <span>Rencana: {{ $item->rencana_pekerjaan ?: '-' }}</span>
                        <span>Realisasi: {{ $item->realisasi_pekerjaan ?: '-' }}</span>
                        <span>Catatan: {{ $item->catatan ?: '-' }}</span>
                    </div>
                @empty
                    <p class="timeline-empty">Belum ada agenda dalam 7 hari terakhir.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
