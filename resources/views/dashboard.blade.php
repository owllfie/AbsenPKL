@extends('layouts.admin')

@section('title', 'Dashboard')
@section('admin_title', 'Ringkasan aktivitas PKL')

@section('admin_content')
    <section class="stats-grid" id="dashboard-stats">
        <article class="stat-card">
            <span class="panel-label">Total Siswa Hadir Hari Ini</span>
            <strong data-summary="hadir">{{ $dashboardData['summary']['hadir'] }}</strong>
            <p data-summary-caption="hadir">{{ $dashboardData['summary']['attendance_rate'] }}% dari {{ $dashboardData['summary']['total_students'] }} siswa aktif.</p>
        </article>

        <article class="stat-card">
            <span class="panel-label">Total Izin Hari Ini</span>
            <strong data-summary="izin">{{ $dashboardData['summary']['izin'] }}</strong>
            <p>Izin tercatat resmi pada {{ $dashboardData['summary']['date_label'] }}.</p>
        </article>

        <article class="stat-card">
            <span class="panel-label">Tanpa Keterangan Hari Ini</span>
            <strong data-summary="alpha">{{ $dashboardData['summary']['alpha'] }}</strong>
            <p data-summary-caption="alpha">Belum hadir atau alpha dari total {{ $dashboardData['summary']['total_students'] }} siswa.</p>
        </article>
    </section>

    <section class="chart-panel">
        <div class="chart-header">
            <div>
                <p class="eyebrow">Grafik Harian</p>
                <h2>Tren hadir vs tidak hadir 7 hari terakhir</h2>
            </div>
            <span class="chart-note" id="chart-note">Update {{ $dashboardData['summary']['updated_at'] }}</span>
        </div>

        <div class="dashboard-chart-layout">
            <div class="dashboard-chart-main">
                <div class="chart-legend">
                    <span><i class="legend-dot legend-dot-solid"></i>Hadir</span>
                    <span><i class="legend-dot legend-dot-dashed"></i>Tidak hadir</span>
                </div>

                <div class="chart-area">
                    <div class="chart-grid-lines">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <svg viewBox="0 0 680 260" class="line-chart" aria-hidden="true">
                        <polyline id="present-line" fill="none" stroke="#d97706" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        <polyline id="absent-line" fill="none" stroke="#f2b36b" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="10 10"></polyline>
                        <g id="present-points"></g>
                    </svg>

                    <div class="chart-labels" id="chart-labels"></div>
                </div>
            </div>

            <aside class="dashboard-chart-side">
                <div class="chart-side-header">
                    <p class="eyebrow">Status Hari Ini</p>
                    <h3>Distribusi kehadiran</h3>
                    <span class="chart-note" id="date-note">{{ $dashboardData['summary']['date_label'] }}</span>
                </div>

                <div class="live-breakdown" id="live-breakdown">
                    <article class="live-breakdown-item">
                        <span>Hadir</span>
                        <strong data-breakdown="hadir">{{ $dashboardData['summary']['hadir'] }}</strong>
                    </article>
                    <article class="live-breakdown-item">
                        <span>Izin</span>
                        <strong data-breakdown="izin">{{ $dashboardData['summary']['izin'] }}</strong>
                    </article>
                    <article class="live-breakdown-item">
                        <span>Tidak Hadir</span>
                        <strong data-breakdown="tidak_hadir">{{ $dashboardData['summary']['tidak_hadir'] }}</strong>
                    </article>
                </div>
            </aside>
        </div>
    </section>

    <style>
        .dashboard-chart-layout {
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(260px, 0.9fr);
            gap: 1.1rem;
            align-items: stretch;
        }
        .dashboard-chart-main {
            min-width: 0;
        }
        .dashboard-chart-side {
            display: grid;
            gap: 1rem;
            padding: 1rem 1rem 1.1rem;
            border-radius: 1.35rem;
            background: linear-gradient(180deg, rgba(255, 252, 247, 0.92), rgba(252, 244, 232, 0.88));
            border: 1px solid rgba(170, 117, 51, 0.12);
        }
        .chart-side-header {
            display: grid;
            gap: 0.45rem;
        }
        .chart-side-header .eyebrow {
            margin-bottom: 0;
        }
        .chart-side-header h3 {
            margin: 0;
            color: var(--primary-deep);
            font-size: 1.15rem;
        }
        .chart-legend {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 0.9rem;
            color: var(--muted);
        }
        .chart-legend span {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .legend-dot {
            width: 1.5rem;
            height: 0;
            border-top: 3px solid #d97706;
            border-radius: 999px;
            display: inline-block;
        }
        .legend-dot-dashed {
            border-top-style: dashed;
            border-top-color: #f2b36b;
        }
        .live-breakdown {
            display: grid;
            gap: 0.9rem;
        }
        .chart-labels {
            grid-template-columns: repeat(7, 1fr);
        }
        .live-breakdown-item {
            padding: 1rem 1.1rem;
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(170, 117, 51, 0.12);
        }
        .live-breakdown-item span {
            display: block;
            margin-bottom: 0.45rem;
            color: var(--muted);
        }
        .live-breakdown-item strong {
            display: block;
            font-size: 1.6rem;
            color: var(--primary-deep);
        }
        @media (max-width: 960px) {
            .dashboard-chart-layout {
                grid-template-columns: 1fr;
            }
            .dashboard-chart-side {
                padding: 0;
                background: transparent;
                border: none;
            }
            .live-breakdown {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 760px) {
            .live-breakdown {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        (() => {
            const liveUrl = @json(route('dashboard.live'));
            let dashboardData = @json($dashboardData);
            let refreshTimer = null;

            const presentLine = document.getElementById('present-line');
            const absentLine = document.getElementById('absent-line');
            const presentPoints = document.getElementById('present-points');
            const chartLabels = document.getElementById('chart-labels');
            const chartNote = document.getElementById('chart-note');
            const dateNote = document.getElementById('date-note');

            function buildPoints(values, maxValue) {
                const width = 630;
                const height = 180;
                const left = 20;
                const top = 30;
                const bottom = 210;
                const step = values.length > 1 ? width / (values.length - 1) : width;

                return values.map((value, index) => {
                    const x = left + (index * step);
                    const y = bottom - ((value / maxValue) * height);

                    return {
                        x: Number(x.toFixed(2)),
                        y: Number(y.toFixed(2)),
                        value,
                    };
                });
            }

            function renderChart(trend) {
                const presentValues = trend.map(item => Number(item.hadir || 0));
                const absentValues = trend.map(item => Number(item.tidak_hadir || 0));
                const maxValue = Math.max(1, ...presentValues, ...absentValues);
                const presentCoords = buildPoints(presentValues, maxValue);
                const absentCoords = buildPoints(absentValues, maxValue);

                presentLine.setAttribute('points', presentCoords.map(point => `${point.x},${point.y}`).join(' '));
                absentLine.setAttribute('points', absentCoords.map(point => `${point.x},${point.y}`).join(' '));

                presentPoints.innerHTML = presentCoords.map((point) => (
                    `<circle cx="${point.x}" cy="${point.y}" r="6" fill="#d97706"></circle>`
                )).join('');

                chartLabels.innerHTML = trend.map((item) => `<span title="${item.full_label}">${item.label}</span>`).join('');
            }

            function renderSummary(summary) {
                document.querySelector('[data-summary="hadir"]').textContent = summary.hadir;
                document.querySelector('[data-summary="izin"]').textContent = summary.izin;
                document.querySelector('[data-summary="alpha"]').textContent = summary.alpha;
                document.querySelector('[data-summary-caption="hadir"]').textContent = `${summary.attendance_rate}% dari ${summary.total_students} siswa aktif.`;
                document.querySelector('[data-summary-caption="alpha"]').textContent = `Belum hadir atau alpha dari total ${summary.total_students} siswa.`;
                document.querySelector('[data-breakdown="hadir"]').textContent = summary.hadir;
                document.querySelector('[data-breakdown="izin"]').textContent = summary.izin;
                document.querySelector('[data-breakdown="tidak_hadir"]').textContent = summary.tidak_hadir;
                chartNote.textContent = `Update ${summary.updated_at}`;
                dateNote.textContent = summary.date_label;
            }

            function renderDashboard(payload) {
                renderSummary(payload.summary);
                renderChart(payload.trend);
            }

            async function refreshDashboard() {
                try {
                    const response = await fetch(liveUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    dashboardData = await response.json();
                    renderDashboard(dashboardData);
                } catch (error) {
                    console.error('Gagal memperbarui dashboard live.', error);
                }
            }

            renderDashboard(dashboardData);
            refreshTimer = window.setInterval(refreshDashboard, 15000);

            window.addEventListener('beforeunload', () => {
                if (refreshTimer) {
                    window.clearInterval(refreshTimer);
                }
            });
        })();
    </script>
@endsection
