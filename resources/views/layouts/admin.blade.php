@extends('layouts.app')

@section('body_class', 'dashboard-page')

@php
    $navItems = app(\App\Services\AccessControlService::class)->allowedModulesForUser(auth()->user());
    $roleName = auth()->user()->roleRelation?->role ?? 'user';
    $webSettingService = app(\App\Services\WebSettingService::class);
    $webSettings = $webSettingService->settings();
    $logoUrl = $webSettingService->logoUrl();
@endphp

@section('content')
    <section class="dashboard-shell">
        <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle-input">

        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand logo-link">
                <div class="brand-block">
                    <strong>{{ $webSettings['web_name'] }}</strong>
                    <p>{{ ucfirst($roleName) }} Panel</p>
                    <div class="brand-mark {{ $logoUrl ? 'brand-mark-landscape' : '' }}">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $webSettings['web_name'] }} logo">
                        @else
                            {{ $webSettingService->brandMarkText() }}
                        @endif
                    </div>
                </div>
            </a>

            <nav class="sidebar-nav">
                @foreach ($navItems as $item)
                    @php
                        $url = route('admin.module', $item['key']);
                        $isActive = request()->routeIs('admin.module') && request()->route('module') === $item['key'];
                        
                        if ($item['key'] === 'manage-access') {
                            $url = route('manage-access');
                            $isActive = request()->routeIs('manage-access');
                        } elseif ($item['key'] === 'activity-log') {
                            $url = route('activity-log');
                            $isActive = request()->routeIs('activity-log');
                        } elseif ($item['key'] === 'chatbot') {
                            continue;
                        } elseif ($item['key'] === 'absensi' && auth()->user()->role == 1) {
                            $url = route('siswa.absensi');
                            $isActive = request()->routeIs('siswa.absensi');
                        } elseif ($item['key'] === 'absensi-rekap') {
                            $url = route('absensi.rekap');
                            $isActive = request()->routeIs('absensi.rekap');
                        } elseif ($item['key'] === 'bimbingan') {
                            $url = route('bimbingan.index');
                            $isActive = request()->routeIs('bimbingan.*');
                        } elseif ($item['key'] === 'agenda' && auth()->user()->role == 1) {
                            $url = route('siswa.agenda');
                            $isActive = request()->routeIs('siswa.agenda');
                        } elseif ($item['key'] === 'agenda' && in_array((int) auth()->user()->role, [3, 4], true)) {
                            $url = route('agenda.review');
                            $isActive = request()->routeIs('agenda.review');
                        }
                    @endphp
                    <a href="{{ $url }}" class="sidebar-link {{ $isActive ? 'active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-title">
                    <label for="sidebar-toggle" class="sidebar-toggle-button" aria-label="Toggle sidebar">
                        <span></span>
                        <span></span>
                        <span></span>
                    </label>

                    <div>
                        <p class="eyebrow">Dashboard {{ ucfirst($roleName) }}</p>
                        <h1>@yield('admin_title', 'Ringkasan aktivitas PKL')</h1>
                    </div>
                </div>

                <details class="profile-dropdown">
                    <summary class="profile-trigger">
                        <span class="profile-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="profile-meta">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->roleRelation?->role ?? 'superadmin' }}</small>
                        </span>
                    </summary>

                    <div class="profile-menu">
                        <a href="#" class="profile-link">Change Profile Settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-button">Logout</button>
                        </form>
                    </div>
                </details>
            </header>

            @if (session('status'))
                <div class="status-banner">{{ session('status') }}</div>
            @endif

            @if (session('success'))
                <div class="status-banner">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="status-banner" style="background: #fee2e2; color: #b91c1c; border-color: #fecaca;">
                    {{ session('error') }}
                </div>
            @endif

            @yield('admin_content')
        </div>

        @if(app(\App\Services\AccessControlService::class)->canAccess(auth()->user(), 'chatbot'))
            <button class="chatbot-toggle" id="chatbot-toggle" title="Tanya AI">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
            </button>

            <div class="chatbot-floating-panel" id="chatbot-panel">
                <div class="chatbot-panel-header">
                    <div>
                        <strong>Asisten PKL</strong>
                        <p>AI siap membantu Anda</p>
                    </div>
                    <button class="chatbot-close" id="chatbot-close">&times;</button>
                </div>
                <div class="chatbot-messages" id="chatbot-messages"></div>
                <form class="chatbot-form" id="chatbot-form">
                    <div class="chatbot-form-row">
                        <textarea id="chatbot-input" class="chatbot-input" rows="1" placeholder="Tanya sesuatu..."></textarea>
                        <button type="submit" class="chatbot-send" id="chatbot-send">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        </button>
                    </div>
                </form>
            </div>

            <style>
                .chatbot-toggle {
                    position: fixed;
                    right: 2rem;
                    bottom: 2rem;
                    width: 3.5rem;
                    height: 3.5rem;
                    border-radius: 999px;
                    background: linear-gradient(135deg, #d97706, #f0a540);
                    color: white;
                    border: none;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
                    display: grid;
                    place-items: center;
                    z-index: 1000;
                    transition: transform 0.2s, box-shadow 0.2s;
                }
                .chatbot-toggle:hover {
                    transform: scale(1.05);
                    box-shadow: 0 6px 16px rgba(217, 119, 6, 0.4);
                }
                .chatbot-floating-panel {
                    position: fixed;
                    right: 2rem;
                    bottom: 6.5rem;
                    width: 22rem;
                    height: 32rem;
                    max-height: calc(100vh - 10rem);
                    background: #fffdfa;
                    border-radius: 1.25rem;
                    box-shadow: 0 12px 32px rgba(0,0,0,0.15);
                    display: none;
                    flex-direction: column;
                    overflow: hidden;
                    z-index: 1000;
                    border: 1px solid rgba(170, 117, 51, 0.12);
                }
                .chatbot-floating-panel.active { display: flex; }
                .chatbot-panel-header {
                    padding: 1rem 1.25rem;
                    background: linear-gradient(180deg, rgba(255, 248, 238, 0.96), rgba(255, 252, 247, 0.96));
                    border-bottom: 1px solid rgba(170, 117, 51, 0.1);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .chatbot-panel-header strong { display: block; color: var(--text); }
                .chatbot-panel-header p { font-size: 0.75rem; color: var(--muted); margin: 0; }
                .chatbot-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    color: var(--muted);
                    cursor: pointer;
                    line-height: 1;
                }
                .chatbot-messages {
                    flex: 1;
                    overflow-y: auto;
                    padding: 1rem;
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                    background: #fffcf7;
                }
                .chatbot-row { display: flex; gap: 0.5rem; align-items: flex-start; }
                .chatbot-row.user { flex-direction: row-reverse; }
                .chatbot-bubble {
                    padding: 0.65rem 0.85rem;
                    border-radius: 1rem;
                    font-size: 0.875rem;
                    line-height: 1.5;
                    max-width: 85%;
                    word-break: break-word;
                }
                .chatbot-row.bot .chatbot-bubble {
                    background: white;
                    border: 1px solid rgba(170, 117, 51, 0.1);
                    color: var(--text);
                }
                .chatbot-row.user .chatbot-bubble {
                    background: linear-gradient(135deg, #d97706, #f0a540);
                    color: white;
                }
                .chatbot-form {
                    padding: 1rem;
                    border-top: 1px solid rgba(170, 117, 51, 0.1);
                    background: white;
                }
                .chatbot-form-row { display: flex; gap: 0.5rem; align-items: flex-end; }
                .chatbot-input {
                    flex: 1;
                    border: 1px solid rgba(170, 117, 51, 0.18);
                    border-radius: 0.75rem;
                    padding: 0.65rem 0.85rem;
                    font-size: 0.875rem;
                    resize: none;
                    outline: none;
                    background: #fffdfa;
                }
                .chatbot-input:focus { border-color: #d97706; }
                .chatbot-send {
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 0.75rem;
                    background: #d97706;
                    color: white;
                    border: none;
                    cursor: pointer;
                    display: grid;
                    place-items: center;
                    flex-shrink: 0;
                }
                .chatbot-send:disabled { opacity: 0.5; cursor: not-allowed; }
                
                @media (max-width: 480px) {
                    .chatbot-floating-panel {
                        right: 1rem;
                        bottom: 5.5rem;
                        width: calc(100vw - 2rem);
                        height: 28rem;
                    }
                    .chatbot-toggle {
                        right: 1rem;
                        bottom: 1rem;
                    }
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const toggleBtn = document.getElementById('chatbot-toggle');
                    const closeBtn = document.getElementById('chatbot-close');
                    const panel = document.getElementById('chatbot-panel');
                    const messagesEl = document.getElementById('chatbot-messages');
                    const formEl = document.getElementById('chatbot-form');
                    const inputEl = document.getElementById('chatbot-input');
                    const sendEl = document.getElementById('chatbot-send');
                    const history = [];
                    let initialGreeted = false;

                    toggleBtn.addEventListener('click', () => {
                        panel.classList.toggle('active');
                        if (panel.classList.contains('active')) {
                            inputEl.focus();
                            if (!initialGreeted) {
                                appendMessage('bot', 'Halo! Saya asisten AI. Ada yang bisa saya bantu terkait data absensi atau agenda PKL hari ini?');
                                initialGreeted = true;
                            }
                        }
                    });

                    closeBtn.addEventListener('click', () => {
                        panel.classList.remove('active');
                    });

                    function escapeHtml(text) {
                        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    }

                    function appendMessage(role, text) {
                        const row = document.createElement('div');
                        row.className = `chatbot-row ${role}`;
                        
                        const bubble = document.createElement('div');
                        bubble.className = 'chatbot-bubble';
                        bubble.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
                        
                        row.appendChild(bubble);
                        messagesEl.appendChild(row);
                        messagesEl.scrollTop = messagesEl.scrollHeight;
                        return bubble;
                    }

                    async function sendMessage() {
                        const text = inputEl.value.trim();
                        if (!text || sendEl.disabled) return;

                        inputEl.value = '';
                        inputEl.style.height = 'auto';
                        sendEl.disabled = true;
                        
                        appendMessage('user', text);
                        history.push({ role: 'user', content: text });

                        const typingBubble = appendMessage('bot', '...');

                        try {
                            const response = await fetch("{{ route('chatbot.ask') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                },
                                body: JSON.stringify({
                                    message: text,
                                    history: history.slice(-10),
                                }),
                            });

                            const data = await response.json();
                            if (!response.ok || data.error) {
                                typingBubble.innerHTML = escapeHtml(data.error || 'Terjadi kesalahan.');
                            } else {
                                typingBubble.innerHTML = escapeHtml(data.reply).replace(/\n/g, '<br>');
                                history.push({ role: 'assistant', content: data.reply });
                            }
                        } catch (error) {
                            typingBubble.innerHTML = 'Gagal terhubung.';
                        } finally {
                            sendEl.disabled = false;
                            messagesEl.scrollTop = messagesEl.scrollHeight;
                        }
                    }

                    formEl.addEventListener('submit', (e) => {
                        e.preventDefault();
                        sendMessage();
                    });

                    inputEl.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            sendMessage();
                        }
                    });

                    inputEl.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                        if (this.scrollHeight > 150) {
                            this.style.overflowY = 'scroll';
                            this.style.height = '150px';
                        } else {
                            this.style.overflowY = 'hidden';
                        }
                    });
                });
            </script>
        @endif
    </section>
@endsection
