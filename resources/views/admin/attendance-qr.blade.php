@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>

            <form action="{{ route('attendance.qr.refresh') }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary">Generate QR Baru</button>
            </form>
        </div>

        <div style="display:grid; gap:1.5rem; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); align-items:start;">
            <article style="background:#fffdfa; border:1px solid rgba(170, 117, 51, 0.14); border-radius:1.5rem; padding:1.5rem;">
                <div id="attendance-qr" style="display:grid; place-items:center; min-height:280px;"></div>
            </article>

            <article style="background:#fffdfa; border:1px solid rgba(170, 117, 51, 0.14); border-radius:1.5rem; padding:1.5rem; display:grid; gap:1rem;">
                <div>
                    <small style="display:block; color:var(--muted);">Payload</small>
                    <strong style="word-break:break-all;">{{ $token->payload }}</strong>
                </div>
                <div>
                    <small style="display:block; color:var(--muted);">Token</small>
                    <strong>{{ $token->token }}</strong>
                </div>
                <div>
                    <small style="display:block; color:var(--muted);">Aktif Sampai</small>
                    <strong>{{ \Carbon\Carbon::parse($token->expires_at)->format('d M Y H:i') }}</strong>
                </div>
                <div>
                    <small style="display:block; color:var(--muted);">Sudah Dipakai</small>
                    <strong>{{ $token->used_count }} kali scan</strong>
                </div>
            </article>
        </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById('attendance-qr'), {
            text: @json($token->payload),
            width: 260,
            height: 260,
        });
    </script>
@endsection
