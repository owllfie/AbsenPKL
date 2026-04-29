@extends('layouts.admin')

@section('title', 'Rekap Absensi Saya')
@section('admin_title', 'Rekap Absensi Saya')

@section('admin_content')
    <section class="page-panel">
        <style>
            .rekap-student-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
                margin: 0 1.5rem 1.5rem;
            }
            .stat-card {
                padding: 1.5rem;
                border-radius: 1.5rem;
                background: white;
                border: 1px solid var(--line);
                box-shadow: var(--shadow);
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .stat-val {
                font-size: 2.5rem;
                font-weight: 900;
                margin: 0.5rem 0;
            }
            .stat-label {
                font-weight: 700;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: 0.1em;
                font-size: 0.75rem;
            }
            .calendar-list {
                display: grid;
                gap: 1rem;
                margin: 0 1.5rem 1.5rem;
            }
            .calendar-item {
                display: flex;
                align-items: center;
                gap: 1.5rem;
                padding: 1.25rem;
                background: white;
                border: 1px solid var(--line);
                border-radius: 1.25rem;
                transition: all 0.2s;
            }
            .calendar-date {
                width: 4rem;
                text-align: center;
                flex-shrink: 0;
            }
            .calendar-day { font-weight: 800; font-size: 1.1rem; color: var(--primary-deep); }
            .calendar-num { font-size: 0.8rem; color: var(--muted); font-weight: 600; }
            
            .calendar-status {
                flex-grow: 1;
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            .status-pill {
                padding: 0.5rem 1rem;
                border-radius: 999px;
                font-weight: 800;
                font-size: 0.75rem;
            }
            .status-1 { background: #dcfce7; color: #166534; }
            .status-2 { background: #fef3c7; color: #92400e; }
            .status-3 { background: #dbeafe; color: #1d4ed8; }
            .status-4 { background: #fee2e2; color: #b91c1c; }
            .status-none { background: #f1f5f9; color: #94a3b8; }
        </style>

        <div class="page-panel-header">
            <div>
                <p class="eyebrow">Rekap Mingguan</p>
                <p class="lede">Ringkasan kehadiran Anda periode {{ $dates[0]->translatedFormat('d M') }} - {{ $dates[6]->translatedFormat('d M Y') }}</p>
            </div>
            
            <form method="GET" action="{{ route('absensi.rekap') }}" class="week-selector" style="display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 1rem; border: 1px solid var(--line);">
                <input type="date" name="start_date" value="{{ $startOfWeek->toDateString() }}" onchange="this.form.submit()" style="border: none; outline: none; font-weight: 700; color: var(--primary-deep);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </form>
        </div>

        <div class="rekap-student-grid">
            <div class="stat-card">
                <span class="stat-label">Hadir</span>
                <span class="stat-val" style="color: #166534;">{{ $attendance->where('status', 1)->count() }}</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Izin/Sakit</span>
                <span class="stat-val" style="color: #92400e;">{{ $attendance->whereIn('status', [2,3])->count() }}</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Alpha</span>
                <span class="stat-val" style="color: #b91c1c;">{{ $attendance->where('status', 4)->count() }}</span>
            </div>
        </div>

        <div class="calendar-list">
            @foreach($dates as $date)
                @php $data = $attendance->get($date->toDateString()); @endphp
                <div class="calendar-item">
                    <div class="calendar-date">
                        <div class="calendar-day">{{ $date->translatedFormat('D') }}</div>
                        <div class="calendar-num">{{ $date->translatedFormat('d M') }}</div>
                    </div>
                    <div class="calendar-status">
                        @if($data)
                            <span class="status-pill status-{{ $data->status }}">
                                {{ match((int)$data->status) { 1=>'HADIR', 2=>'IZIN', 3=>'SAKIT', 4=>'ALPHA', default=>'UNKNOWN' } }}
                            </span>
                            @if($data->jam_datang)
                                <span style="font-size: 0.8rem; color: var(--muted); font-weight: 600;">
                                    Masuk: {{ \Carbon\Carbon::parse($data->jam_datang)->format('H:i') }}
                                </span>
                            @endif
                        @else
                            <span class="status-pill status-none">TIDAK ADA DATA</span>
                        @endif
                    </div>
                    @if($data && $data->keterangan)
                        <div style="font-size: 0.8rem; color: var(--muted); font-style: italic;">
                            "{{ $data->keterangan }}"
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endsection
