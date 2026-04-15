@extends('layouts.admin')

@section('title', 'Chatbot PKL')
@section('admin_title', 'Chatbot Absensi PKL')

@section('admin_content')
    <section class="page-panel chatbot-page">
        <style>
            .chatbot-page { overflow: visible; }
            .chatbot-shell { padding: 1.5rem; }
            .chatbot-panel {
                display: grid;
                grid-template-rows: auto 1fr auto;
                min-height: calc(100vh - 12rem);
                border: 1px solid rgba(170, 117, 51, 0.14);
                border-radius: 1.5rem;
                background: rgba(255, 252, 247, 0.94);
                overflow: hidden;
            }
            .chatbot-panel-header {
                padding: 1rem 1.2rem;
                border-bottom: 1px solid rgba(170, 117, 51, 0.12);
                background: linear-gradient(180deg, rgba(255, 248, 238, 0.96), rgba(255, 252, 247, 0.96));
            }
            .chatbot-panel-header p { margin: 0.35rem 0 0; color: var(--muted); }
            .chatbot-messages {
                display: grid;
                align-content: start;
                gap: 0.9rem;
                padding: 1.2rem;
                overflow-y: auto;
                background:
                    radial-gradient(circle at top left, rgba(245, 158, 11, 0.08), transparent 24%),
                    linear-gradient(180deg, rgba(255, 252, 247, 0.95), rgba(255, 248, 240, 0.95));
            }
            .chatbot-row { display: flex; gap: 0.75rem; align-items: flex-start; }
            .chatbot-row.user { justify-content: flex-end; }
            .chatbot-avatar {
                display: grid;
                place-items: center;
                width: 2.2rem;
                height: 2.2rem;
                border-radius: 999px;
                font-size: 0.76rem;
                font-weight: 700;
                flex-shrink: 0;
            }
            .chatbot-avatar.bot { background: rgba(217, 119, 6, 0.14); color: var(--primary-deep); }
            .chatbot-avatar.user { background: linear-gradient(135deg, #d97706, #f0a540); color: #fffdfa; }
            .chatbot-bubble-wrap { max-width: min(80%, 46rem); }
            .chatbot-bubble {
                padding: 0.9rem 1rem;
                border-radius: 1.1rem;
                border: 1px solid rgba(170, 117, 51, 0.12);
                background: #fffdfa;
                line-height: 1.65;
                white-space: pre-wrap;
                word-break: break-word;
            }
            .chatbot-row.user .chatbot-bubble {
                background: linear-gradient(135deg, #d97706, #f0a540);
                color: #fffdfa;
                border-color: transparent;
            }
            .chatbot-time { margin-top: 0.35rem; font-size: 0.75rem; color: var(--muted); }
            .chatbot-row.user .chatbot-time { text-align: right; }
            .chatbot-form {
                display: grid;
                gap: 0.85rem;
                padding: 1rem 1.2rem 1.2rem;
                border-top: 1px solid rgba(170, 117, 51, 0.12);
                background: rgba(255, 252, 247, 0.96);
            }
            .chatbot-form-row { display: flex; gap: 0.75rem; }
            .chatbot-input {
                width: 100%;
                min-height: 3.25rem;
                max-height: 10rem;
                resize: vertical;
                padding: 0.95rem 1rem;
                border: 1px solid rgba(170, 117, 51, 0.18);
                border-radius: 1rem;
                background: #fffdfa;
                color: var(--text);
                outline: none;
            }
            .chatbot-input:focus {
                border-color: rgba(217, 119, 6, 0.4);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.08);
            }
            .chatbot-send {
                min-width: 8rem;
                border: 0;
                border-radius: 1rem;
                background: linear-gradient(135deg, #d97706, #f0a540);
                color: #fffdfa;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 14px 28px rgba(217, 119, 6, 0.22);
            }
            .chatbot-send:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: none; }
            .chatbot-form small { color: var(--muted); }
            @media (max-width: 640px) {
                .chatbot-shell { padding: 1rem; }
                .chatbot-form-row { grid-template-columns: 1fr; display: grid; }
                .chatbot-send { min-height: 3rem; }
                .chatbot-bubble-wrap { max-width: 100%; }
            }
        </style>

        <div class="chatbot-shell">
            <section class="chatbot-panel">
                <div class="chatbot-panel-header">
                    <h3>Chat Absensi PKL</h3>
                    <p>Tanyakan ringkasan hadir, siswa yang tidak masuk, atau rekap absensi hari ini: {{ $stats['date_label'] }}</p>
                </div>

                <div class="chatbot-messages" id="chatbot-messages"></div>

                <form class="chatbot-form" id="chatbot-form">
                    <div class="chatbot-form-row">
                        <textarea id="chatbot-input" class="chatbot-input" rows="2" maxlength="500" placeholder="Contoh: siapa saja yang tidak hadir hari ini?"></textarea>
                        <button type="submit" class="chatbot-send" id="chatbot-send">Kirim</button>
                    </div>
                    <small>Maksimal 500 karakter per pertanyaan.</small>
                </form>
            </section>
        </div>

        <script>
            const messagesEl = document.getElementById('chatbot-messages');
            const formEl = document.getElementById('chatbot-form');
            const inputEl = document.getElementById('chatbot-input');
            const sendEl = document.getElementById('chatbot-send');
            const history = [];

            function escapeHtml(text) {
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function nowLabel() {
                return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }

            function appendMessage(role, text) {
                const row = document.createElement('div');
                row.className = `chatbot-row ${role}`;

                const avatar = document.createElement('div');
                avatar.className = `chatbot-avatar ${role === 'user' ? 'user' : 'bot'}`;
                avatar.textContent = role === 'user' ? 'YOU' : 'AI';

                const wrap = document.createElement('div');
                wrap.className = 'chatbot-bubble-wrap';

                const bubble = document.createElement('div');
                bubble.className = 'chatbot-bubble';
                bubble.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');

                const time = document.createElement('div');
                time.className = 'chatbot-time';
                time.textContent = nowLabel();

                wrap.appendChild(bubble);
                wrap.appendChild(time);

                if (role === 'user') {
                    row.appendChild(wrap);
                    row.appendChild(avatar);
                } else {
                    row.appendChild(avatar);
                    row.appendChild(wrap);
                }

                messagesEl.appendChild(row);
                messagesEl.scrollTop = messagesEl.scrollHeight;

                return bubble;
            }

            async function sendMessage(message) {
                const text = (message ?? inputEl.value).trim();

                if (!text || sendEl.disabled) {
                    return;
                }

                inputEl.value = '';
                sendEl.disabled = true;
                appendMessage('user', text);
                history.push({ role: 'user', content: text });

                const typingBubble = appendMessage('assistant', 'Sedang memproses data absensi...');

                try {
                    const response = await fetch(@json(route('chatbot.ask')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            message: text,
                            history: history.slice(-10),
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok || data.error) {
                        typingBubble.innerHTML = escapeHtml(data.error || 'Terjadi kesalahan saat meminta jawaban.');
                    } else {
                        typingBubble.innerHTML = escapeHtml(data.reply).replace(/\n/g, '<br>');
                        history.push({ role: 'assistant', content: data.reply });
                    }
                } catch (error) {
                    typingBubble.innerHTML = 'Gagal terhubung ke server.';
                } finally {
                    sendEl.disabled = false;
                    inputEl.focus();
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                }
            }

            formEl.addEventListener('submit', async (event) => {
                event.preventDefault();
                await sendMessage();
            });

            inputEl.addEventListener('keydown', async (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    await sendMessage();
                }
            });

            appendMessage('assistant', 'Halo. Saya siap bantu membaca data absensi PKL. Coba tanyakan jumlah hadir hari ini, siswa yang alpha, atau rekap absensi bulan ini.');
        </script>
    </section>
@endsection
