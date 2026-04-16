@extends('layouts.admin')

@section('admin_title', 'Presensi Harian')

@section('admin_content')
<section class="page-panel">
    <style>
        .absensi-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 1rem;
        }
        .status-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .status-present { background: #dcfce7; color: #166534; }
        .status-absent { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        
        .scanner-box {
            width: 100%;
            aspect-ratio: 1;
            background: #000;
            border-radius: 1.5rem;
            overflow: hidden;
            position: relative;
            margin-bottom: 1.5rem;
        }
        #reader { width: 100% !important; border: none !important; }
        
        .selfie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        #selfie-video { width: 100%; height: 100%; object-fit: cover; }
        
        .action-buttons {
            display: grid;
            gap: 1rem;
        }
        .btn-scan {
            padding: 1rem;
            background: linear-gradient(135deg, #d97706, #f0a540);
            color: white;
            border: none;
            border-radius: 1rem;
            font-weight: 800;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(217, 119, 6, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .btn-izin {
            padding: 1rem;
            background: white;
            color: #475569;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-izin:hover { background: #f8fafc; border-color: #cbd5e1; }
        
        .capture-btn {
            position: absolute;
            bottom: 2rem;
            width: 4rem;
            height: 4rem;
            background: white;
            border: 4px solid rgba(217, 119, 6, 0.5);
            border-radius: 999px;
            cursor: pointer;
            box-shadow: 0 0 0 4px white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 400px;
        }
    </style>

    <div class="absensi-container">
        @if(session('success'))
            <div class="status-banner" style="margin-bottom: 1rem;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="status-banner" style="background: #fee2e2; color: #991b1b; border-color: #fecaca; margin-bottom: 1rem;">{{ session('error') }}</div>
        @endif

        <div class="status-card">
            <p class="eyebrow" style="margin-bottom: 0.5rem;">{{ $today }}</p>
            @if(!$attendance)
                <div class="status-badge status-absent">Belum Absen</div>
                <p class="lede">Silakan lakukan scan QR untuk memulai absensi.</p>
            @elseif(!$attendance->jam_pulang && $attendance->status != 2)
                <div class="status-badge status-pending">Sudah Absen Masuk</div>
                <p class="lede">Jam Masuk: {{ \Carbon\Carbon::parse($attendance->jam_datang)->format('H:i') }}</p>
                <p><small>Jangan lupa absen pulang nanti!</small></p>
            @elseif($attendance->status == 2)
                <div class="status-badge status-pending">Izin</div>
                <p class="lede">Keterangan: {{ $attendance->keterangan }}</p>
            @else
                <div class="status-badge status-present">Sudah Absen Lengkap</div>
                <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1rem;">
                    <div>
                        <small style="display:block; color: var(--muted)">Masuk</small>
                        <strong>{{ \Carbon\Carbon::parse($attendance->jam_datang)->format('H:i') }}</strong>
                    </div>
                    <div>
                        <small style="display:block; color: var(--muted)">Pulang</small>
                        <strong>{{ \Carbon\Carbon::parse($attendance->jam_pulang)->format('H:i') }}</strong>
                    </div>
                </div>
            @endif
        </div>

        @if(!$attendance || (!$attendance->jam_pulang && $attendance->status != 2))
            <div class="scanner-box" id="scanner-box">
                <div id="reader"></div>
                <div class="selfie-overlay" id="selfie-overlay">
                    <video id="selfie-video" autoplay playsinline></video>
                    <button class="capture-btn" id="capture-btn" title="Ambil Foto"></button>
                </div>
            </div>

            <div class="action-buttons">
                <button class="btn-scan" id="start-scan">
                    <span>Scan QR Code</span>
                </button>
                @if(!$attendance)
                    <button class="btn-izin" onclick="document.getElementById('izin-modal').style.display='flex'">Minta Izin</button>
                @endif
            </div>
        @endif
    </div>

    <div id="izin-modal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem;">Formulir Izin</h3>
            <form action="{{ route('siswa.absensi.izin') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem;">ALASAN IZIN</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Sakit, ada keperluan keluarga, dll." required></textarea>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="btn-cancel" style="flex: 1;" onclick="document.getElementById('izin-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn-save" style="flex: 1;">Kirim</button>
                </div>
            </form>
        </div>
    </div>

    <canvas id="selfie-canvas" style="display: none;"></canvas>
</section>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    const startBtn = document.getElementById('start-scan');
    const readerDiv = document.getElementById('reader');
    const selfieOverlay = document.getElementById('selfie-overlay');
    const video = document.getElementById('selfie-video');
    const captureBtn = document.getElementById('capture-btn');
    const canvas = document.getElementById('selfie-canvas');
    
    let html5QrCode;
    let qrData = null;

    if (startBtn) {
        startBtn.addEventListener('click', () => {
            startBtn.style.display = 'none';
            html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start(
                { facingMode: "environment" }, 
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    qrData = decodedText;
                    html5QrCode.stop().then(() => {
                        readerDiv.style.display = 'none';
                        startSelfie();
                    });
                },
                (errorMessage) => { /* ignore */ }
            ).catch(err => {
                alert("Gagal mengakses kamera: " + err);
                startBtn.style.display = 'flex';
            });
        });
    }

    async function startSelfie() {
        selfieOverlay.style.display = 'flex';
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: "user" }, 
                audio: false 
            });
            video.srcObject = stream;
        } catch (err) {
            alert("Gagal mengakses kamera depan: " + err);
        }
    }

    captureBtn.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const imageData = canvas.toDataURL('image/png');
        
        // Stop camera
        const stream = video.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
        
        submitAbsensi(imageData);
    });

    async function submitAbsensi(imageData) {
        try {
            const response = await fetch("{{ route('siswa.absensi.scan') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    qr_code: qrData,
                    image: imageData
                })
            });
            
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.message);
                window.location.reload();
            }
        } catch (err) {
            alert("Terjadi kesalahan sistem.");
            window.location.reload();
        }
    }
</script>
@endsection
