@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <style>
            .backup-shell {
                display: grid;
                gap: 1.25rem;
                padding: 0.75rem 1rem 0;
            }
            .backup-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1.25rem;
            }
            .backup-card {
                padding: 1.35rem;
                border-radius: 1.4rem;
                background: rgba(255, 255, 255, 0.76);
                border: 1px solid rgba(170, 117, 51, 0.12);
            }
            .backup-card h3 {
                margin: 0 0 0.35rem;
                color: var(--primary-deep);
            }
            .backup-card p {
                margin: 0;
                color: var(--muted);
                line-height: 1.6;
            }
            .backup-meta {
                display: grid;
                gap: 0.5rem;
                margin-top: 1rem;
                padding: 1rem;
                border-radius: 1rem;
                background: linear-gradient(180deg, rgba(255, 248, 238, 0.92), rgba(255, 252, 247, 0.96));
                border: 1px solid rgba(170, 117, 51, 0.12);
            }
            .backup-meta strong {
                color: var(--text);
            }
            .backup-actions,
            .import-form {
                display: grid;
                gap: 1rem;
                margin-top: 1.25rem;
            }
            .backup-button {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                min-height: 3.2rem;
                padding: 0.95rem 1.2rem;
                border: none;
                border-radius: 1rem;
                background: linear-gradient(135deg, var(--primary), #efac52);
                color: #fff;
                font-weight: 800;
                text-decoration: none;
                cursor: pointer;
                box-shadow: 0 18px 34px -24px rgba(180, 83, 9, 0.8);
            }
            .backup-button.secondary {
                background: #fffdfa;
                color: var(--primary-deep);
                border: 1px solid rgba(170, 117, 51, 0.14);
                box-shadow: none;
            }
            .import-field {
                display: grid;
                gap: 0.45rem;
            }
            .import-field label {
                font-weight: 700;
                color: var(--text);
            }
            .import-field input {
                width: 100%;
                padding: 0.85rem 0.95rem;
                border-radius: 1rem;
                border: 1px solid rgba(170, 117, 51, 0.14);
                background: #fffdfa;
                color: var(--text);
                outline: none;
            }
            .import-field input:focus {
                border-color: rgba(217, 119, 6, 0.32);
                box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.08);
            }
            .danger-note {
                padding: 1rem;
                border-radius: 1rem;
                background: rgba(254, 242, 242, 0.96);
                border: 1px solid rgba(248, 113, 113, 0.18);
                color: #991b1b;
                line-height: 1.6;
            }
            @media (max-width: 900px) {
                .backup-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>
        </div>

        <div class="backup-shell">
            <div class="backup-grid">
                <section class="backup-card">
                    <h3>Backup Database</h3>
                    <p>Unduh salinan database yang sedang aktif dalam format SQL.</p>

                    <div class="backup-meta">
                        <strong>Database: {{ $databaseName }}</strong>
                        <span>Host: {{ $databaseHost }}</span>
                        <span>Format file: `.sql`</span>
                    </div>

                    <div class="backup-actions">
                        <a href="{{ route('admin.backup-database.export') }}" class="backup-button">
                            Download Backup Database
                        </a>
                    </div>
                </section>

                <section class="backup-card">
                    <h3>Import Database</h3>
                    <p>Unggah file SQL untuk memulihkan isi database saat ini.</p>

                    <div class="danger-note">
                        Import akan menimpa data sesuai isi file SQL. Lakukan backup terlebih dahulu sebelum import.
                    </div>

                    <form action="{{ route('admin.backup-database.import') }}" method="POST" enctype="multipart/form-data" class="import-form">
                        @csrf
                        <div class="import-field">
                            <label for="database_file">File SQL</label>
                            <input id="database_file" name="database_file" type="file" accept=".sql,.txt" required>
                        </div>

                        @if ($errors->any())
                            <div class="status-banner" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button type="submit" class="backup-button secondary">Import Database</button>
                    </form>
                </section>
            </div>
        </div>
    </section>
@endsection
