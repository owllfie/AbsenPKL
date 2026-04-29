@extends('layouts.admin')

@section('title', 'Rekap Mingguan Absensi')
@section('admin_title', 'Rekap Mingguan Absensi')

@section('admin_content')
    <section class="page-panel">
        <style>
            .rekap-toolbar {
                margin: 0 1.5rem 1.5rem;
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 1rem;
                flex-wrap: wrap;
            }
            .rekap-card {
                margin: 0 1.5rem 1.5rem;
                padding: 1.5rem;
                background: #ffffff;
                border: 1px solid var(--line);
                border-radius: 1.5rem;
                box-shadow: var(--shadow);
                overflow-x: auto;
            }
            .table-rekap {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.9rem;
            }
            .table-rekap th, .table-rekap td {
                padding: 0.75rem;
                border: 1px solid var(--line);
                text-align: center;
            }
            .table-rekap th {
                background: #f8fafc;
                color: var(--primary-deep);
                font-weight: 800;
                text-transform: uppercase;
                font-size: 0.7rem;
                letter-spacing: 0.05em;
            }
            .table-rekap td.student-info {
                text-align: left;
                background: #fffdfa;
                font-weight: 700;
                min-width: 200px;
            }
            .status-dot {
                width: 2rem;
                height: 2rem;
                border-radius: 0.5rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                font-size: 0.75rem;
            }
            .status-1 { background: #dcfce7; color: #166534; } /* Hadir */
            .status-2 { background: #fef3c7; color: #92400e; } /* Izin */
            .status-3 { background: #dbeafe; color: #1d4ed8; } /* Sakit */
            .status-4 { background: #fee2e2; color: #b91c1c; } /* Alpha */
            .status-none { background: #f1f5f9; color: #94a3b8; }

            .summary-badge {
                display: inline-flex;
                padding: 0.25rem 0.5rem;
                border-radius: 0.4rem;
                font-size: 0.7rem;
                font-weight: 700;
                margin: 0 0.1rem;
            }
            
            .week-selector {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                background: white;
                padding: 0.5rem 1rem;
                border-radius: 1rem;
                border: 1px solid var(--line);
            }
            .week-selector input {
                border: none;
                outline: none;
                font-weight: 700;
                color: var(--primary-deep);
            }
        </style>

        <div class="page-panel-header">
            <div>
                <p class="eyebrow">Laporan Presensi</p>
                <p class="lede">Rekapitulasi kehadiran siswa dalam satu minggu terakhir.</p>
            </div>
        </div>

        <div class="rekap-toolbar">
            <form method="GET" action="{{ route('absensi.rekap') }}" class="week-selector">
                <span style="font-size: 0.8rem; color: var(--muted); font-weight: 600;">Mulai Tanggal:</span>
                <input type="date" name="start_date" value="{{ $startOfWeek->toDateString() }}" onchange="this.form.submit()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </form>

            <div style="display: flex; gap: 0.5rem;">
                <div class="status-dot status-1" style="width: auto; padding: 0 0.75rem; height: 2.5rem;">H: Hadir</div>
                <div class="status-dot status-2" style="width: auto; padding: 0 0.75rem; height: 2.5rem;">I: Izin</div>
                <div class="status-dot status-3" style="width: auto; padding: 0 0.75rem; height: 2.5rem;">S: Sakit</div>
                <div class="status-dot status-4" style="width: auto; padding: 0 0.75rem; height: 2.5rem;">A: Alpha</div>
            </div>
        </div>

        <div class="rekap-card">
            <table class="table-rekap">
                <thead>
                    <tr>
                        <th rowspan="2">Nama Siswa / Rombel</th>
                        <th colspan="7">Periode: {{ $dates[0]->translatedFormat('d M') }} - {{ $dates[6]->translatedFormat('d M Y') }}</th>
                        <th colspan="4">Total</th>
                    </tr>
                    <tr>
                        @foreach($dates as $date)
                            <th>
                                {{ $date->translatedFormat('D') }}<br>
                                {{ $date->translatedFormat('d/m') }}
                            </th>
                        @endforeach
                        <th class="status-1">H</th>
                        <th class="status-2">I</th>
                        <th class="status-3">S</th>
                        <th class="status-4">A</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $item)
                        <tr>
                            <td class="student-info">
                                <div>{{ $item['nama'] }}</div>
                                <div style="font-size: 0.7rem; color: var(--muted); font-weight: 500;">{{ $item['rombel'] }} | {{ $item['nis'] }}</div>
                            </td>
                            @foreach($dates as $date)
                                @php $status = $item['status_per_hari'][$date->toDateString()]; @endphp
                                <td>
                                    @if($status)
                                        <div class="status-dot status-{{ $status }}">
                                            {{ match((int)$status) { 1=>'H', 2=>'I', 3=>'S', 4=>'A', default=>'' } }}
                                        </div>
                                    @else
                                        <div class="status-dot status-none">-</div>
                                    @endif
                                </td>
                            @endforeach
                            <td style="font-weight: 800; color: #166534;">{{ $item['summary']['hadir'] }}</td>
                            <td style="font-weight: 800; color: #92400e;">{{ $item['summary']['izin'] }}</td>
                            <td style="font-weight: 800; color: #1d4ed8;">{{ $item['summary']['sakit'] }}</td>
                            <td style="font-weight: 800; color: #b91c1c;">{{ $item['summary']['alpha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="padding: 3rem; color: var(--muted);">Tidak ada data siswa untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
