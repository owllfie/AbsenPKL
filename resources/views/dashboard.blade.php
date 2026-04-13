@extends('layouts.admin')

@section('title', 'Dashboard')
@section('admin_title', 'Ringkasan aktivitas PKL')

@section('admin_content')
    <section class="stats-grid">
        <article class="stat-card">
            <span class="panel-label">Total Siswa Hadir Hari Ini</span>
            <strong>128</strong>
            <p>Placeholder data kehadiran harian</p>
        </article>

        <article class="stat-card">
            <span class="panel-label">Total Izin Hari Ini</span>
            <strong>14</strong>
            <p>Placeholder data izin harian</p>
        </article>

        <article class="stat-card">
            <span class="panel-label">Tanpa Keterangan Hari Ini</span>
            <strong>7</strong>
            <p>Placeholder data siswa tanpa keterangan</p>
        </article>
    </section>

    <section class="chart-panel">
        <div class="chart-header">
            <div>
                <p class="eyebrow">Grafik Harian</p>
                <h2>Tren kehadiran per hari</h2>
            </div>
            <span class="chart-note">Placeholder</span>
        </div>

        <div class="chart-area">
            <div class="chart-grid-lines">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>

            <svg viewBox="0 0 680 260" class="line-chart" aria-hidden="true">
                <polyline
                    points="20,210 110,160 200,172 290,110 380,138 470,88 560,128 650,62"
                    fill="none"
                    stroke="#d97706"
                    stroke-width="5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <polyline
                    points="20,230 110,192 200,198 290,150 380,166 470,132 560,176 650,118"
                    fill="none"
                    stroke="#f2b36b"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-dasharray="10 10"
                />
                <circle cx="20" cy="210" r="6" fill="#d97706" />
                <circle cx="110" cy="160" r="6" fill="#d97706" />
                <circle cx="200" cy="172" r="6" fill="#d97706" />
                <circle cx="290" cy="110" r="6" fill="#d97706" />
                <circle cx="380" cy="138" r="6" fill="#d97706" />
                <circle cx="470" cy="88" r="6" fill="#d97706" />
                <circle cx="560" cy="128" r="6" fill="#d97706" />
                <circle cx="650" cy="62" r="6" fill="#d97706" />
            </svg>

            <div class="chart-labels">
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span>Fri</span>
                <span>Sat</span>
                <span>Sun</span>
                <span>Next</span>
            </div>
        </div>
    </section>
@endsection
