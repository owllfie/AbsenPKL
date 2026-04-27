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
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background:
                radial-gradient(circle at top left, rgba(245, 158, 11, 0.16), transparent 38%),
                linear-gradient(180deg, #ffffff 0%, #fffaf2 100%);
            padding: 1.75rem;
            border-radius: 1.75rem;
            width: min(100%, 460px);
            border: 1px solid rgba(217, 119, 6, 0.12);
            box-shadow: 0 30px 80px -30px rgba(15, 23, 42, 0.45);
        }
        .izin-modal-header {
            display: grid;
            gap: 0.45rem;
            margin-bottom: 1.25rem;
        }
        .izin-modal-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
        }
        .izin-modal-subtitle {
            margin: 0;
            color: #64748b;
            line-height: 1.55;
            font-size: 0.95rem;
        }
        .izin-label {
            display: block;
            margin-bottom: 0.65rem;
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            color: #92400e;
        }
        .izin-textarea {
            width: 100%;
            min-height: 132px;
            resize: vertical;
            padding: 1rem 1.05rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.92);
            color: #0f172a;
            font: inherit;
            line-height: 1.5;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .izin-textarea:focus {
            outline: none;
            border-color: rgba(217, 119, 6, 0.55);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.16);
            background: #fff;
        }
        .izin-helper {
            margin-top: 0.65rem;
            font-size: 0.85rem;
            color: #64748b;
        }
        .izin-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
            margin-top: 1.5rem;
        }
        .izin-btn {
            min-height: 3.25rem;
            border-radius: 1rem;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s, background 0.18s;
        }
        .izin-btn:hover {
            transform: translateY(-1px);
        }
        .izin-btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #475569;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }
        .izin-btn-secondary:hover {
            border-color: rgba(148, 163, 184, 0.4);
            box-shadow: 0 12px 24px -18px rgba(15, 23, 42, 0.5);
        }
        .izin-btn-primary {
            border: none;
            color: #fff;
            background: linear-gradient(135deg, #d97706, #f59e0b);
            box-shadow: 0 18px 30px -18px rgba(217, 119, 6, 0.75);
        }
        .izin-btn-primary:hover {
            box-shadow: 0 24px 36px -20px rgba(217, 119, 6, 0.82);
        }
        @media (max-width: 520px) {
            .modal {
                padding: 1rem;
            }
            .modal-content {
                padding: 1.25rem;
                border-radius: 1.4rem;
            }
            .izin-actions {
                grid-template-columns: 1fr;
            }
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
                    <button class="btn-izin" type="button" id="open-izin-modal">Minta Izin</button>
                @endif
            </div>
        @endif
    </div>

    <div id="izin-modal" class="modal">
        <div class="modal-content">
            <div class="izin-modal-header">
                <p class="eyebrow" style="margin: 0;">Absensi Hari Ini</p>
                <h3 class="izin-modal-title">Formulir Izin</h3>
                <p class="izin-modal-subtitle">Tulis alasan izin dengan singkat dan jelas. Data ini akan masuk ke catatan absensi hari ini.</p>
            </div>
            <form action="{{ route('siswa.absensi.izin') }}" method="POST">
                @csrf
                <div>
                    <label class="izin-label" for="izin-keterangan">ALASAN IZIN</label>
                    <textarea id="izin-keterangan" name="keterangan" class="izin-textarea" rows="4" placeholder="Contoh: Sakit, kontrol ke dokter, atau ada keperluan keluarga." required></textarea>
                    <p class="izin-helper">Gunakan alasan yang spesifik agar mudah diverifikasi pembimbing atau sekolah.</p>
                </div>
                <div class="izin-actions">
                    <button type="button" class="izin-btn izin-btn-secondary" id="close-izin-modal">Batal</button>
                    <button type="submit" class="izin-btn izin-btn-primary">Kirim Izin</button>
                </div>
            </form>
        </div>
    </div>

    <canvas id="selfie-canvas" style="display: none;"></canvas>
</section>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    const startBtn = document.getElementById('start-scan');
    const izinModal = document.getElementById('izin-modal');
    const openIzinModalBtn = document.getElementById('open-izin-modal');
    const closeIzinModalBtn = document.getElementById('close-izin-modal');
    const readerDiv = document.getElementById('reader');
    const selfieOverlay = document.getElementById('selfie-overlay');
    const video = document.getElementById('selfie-video');
    const captureBtn = document.getElementById('capture-btn');
    const canvas = document.getElementById('selfie-canvas');
    
    let html5QrCode;
    let qrData = null;

    function openIzinModal() {
        if (!izinModal) {
            return;
        }

        izinModal.style.display = 'flex';
        document.getElementById('izin-keterangan')?.focus();
    }

    function closeIzinModal() {
        if (!izinModal) {
            return;
        }

        izinModal.style.display = 'none';
    }

    openIzinModalBtn?.addEventListener('click', openIzinModal);
    closeIzinModalBtn?.addEventListener('click', closeIzinModal);

    izinModal?.addEventListener('click', (event) => {
        if (event.target === izinModal) {
            closeIzinModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeIzinModal();
        }
    });
    
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
