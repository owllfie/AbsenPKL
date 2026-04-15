<style>
:root {
    --bg: #fff8f1;
    --bg-deep: #f1e1d0;
    --surface: rgba(255, 252, 247, 0.84);
    --surface-strong: #fffdfa;
    --line: rgba(170, 117, 51, 0.16);
    --text: #2d2118;
    --muted: #6c594c;
    --primary: #d97706;
    --primary-deep: #b45309;
    --success: #e7f5e8;
    --shadow: 0 24px 60px rgba(124, 80, 27, 0.16);
    --sidebar-width: 14rem;
    --sidebar-collapsed-width: 5.5rem;
}

* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    min-height: 100%;
    color: var(--text);
    font-family: sans-serif;
    background:
        radial-gradient(circle at top left, rgba(238, 179, 117, 0.45), transparent 26%),
        linear-gradient(135deg, var(--bg), #f8efe5 48%, var(--bg-deep));
}

body {
    min-height: 100vh;
}

button, input {
    font: inherit;
}

.page-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
}

.ambient {
    position: absolute;
    border-radius: 999px;
    filter: blur(14px);
    opacity: 0.5;
}

.ambient-one {
    top: -7rem;
    right: -6rem;
    width: 18rem;
    height: 18rem;
    background: rgba(217, 119, 6, 0.18);
}

.ambient-two {
    bottom: -9rem;
    left: -5rem;
    width: 19rem;
    height: 19rem;
    background: rgba(180, 83, 9, 0.12);
}

.content-wrap {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 2rem;
}

body.dashboard-page .content-wrap {
    display: block;
    padding: 0;
}

.auth-card,
.dashboard-card {
    width: min(100%, 31rem);
    padding: 2.25rem;
    border-radius: 1.8rem;
    border: 1px solid var(--line);
    background: var(--surface);
    backdrop-filter: blur(12px);
    box-shadow: var(--shadow);
}

.dashboard-card {
    width: min(100%, 50rem);
}

.dashboard-shell {
    display: grid;
    grid-template-columns: var(--sidebar-width) minmax(0, 1fr);
    gap: 0;
    width: 100%;
    min-height: 100vh;
    transition: grid-template-columns 0.25s ease;
}

.sidebar-toggle-input {
    display: none;
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    border-right: 1px solid var(--line);
    background: rgba(255, 249, 242, 0.92);
    backdrop-filter: blur(12px);
    box-shadow: var(--shadow);
    min-height: 100vh;
    width: var(--sidebar-width);
    transition: width 0.25s ease, padding 0.25s ease, transform 0.25s ease;
    overflow: hidden;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1.2rem;
    border-bottom: 1px solid var(--line);
    color: inherit;
    text-decoration: none;
}

.brand-mark {
    display: grid;
    place-items: center;
    width: 3rem;
    height: 3rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--primary), #efac52);
    color: #fffdfa;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.sidebar-brand p {
    margin: 0.2rem 0 0;
    color: var(--muted);
    font-size: 0.92rem;
}

.sidebar-nav {
    display: grid;
    gap: 0.45rem;
}

.sidebar-link {
    display: block;
    padding: 0.88rem 1rem;
    border-radius: 1rem;
    color: var(--text);
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
}

.sidebar-link:hover,
.sidebar-link.active {
    background: rgba(217, 119, 6, 0.12);
    transform: translateX(3px);
}

.dashboard-main {
    display: grid;
    gap: 0;
    min-width: 0;
    padding: 0 0 1.5rem;
    align-content: start;
}

.topbar,
.chart-panel,
.stat-card {
    border: 1px solid var(--line);
    background: var(--surface);
    backdrop-filter: blur(12px);
    box-shadow: var(--shadow);
}

.topbar,
.chart-panel {
    padding: 1.5rem;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
    padding: 1rem 1.25rem;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-bottom-left-radius: 1.35rem;
    border-bottom-right-radius: 1.35rem;
    position: relative;
    z-index: 10;
}

.topbar-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
}

.topbar-title > div {
    min-width: 0;
}

.topbar .eyebrow {
    margin-bottom: 0.35rem;
}

.topbar h1 {
    font-size: clamp(1.8rem, 3vw, 2.4rem);
}

.sidebar-toggle-button {
    display: inline-grid;
    gap: 0.28rem;
    padding: 0.8rem;
    border-radius: 0.9rem;
    cursor: pointer;
    border: 1px solid var(--line);
    background: rgba(255, 251, 246, 0.94);
}

.sidebar-toggle-button span {
    width: 1.05rem;
    height: 2px;
    background: var(--text);
    border-radius: 999px;
}

.profile-dropdown {
    position: relative;
    z-index: 50;
}

.profile-trigger {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    list-style: none;
    cursor: pointer;
    padding: 0.55rem 0.7rem;
    border-radius: 999px;
    background: rgba(255, 251, 246, 0.94);
    border: 1px solid var(--line);
}

.profile-trigger::-webkit-details-marker {
    display: none;
}

.profile-avatar {
    display: grid;
    place-items: center;
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--primary), #edab56);
    color: #fffdfa;
    font-weight: 700;
}

.profile-meta {
    display: grid;
    gap: 0.1rem;
}

.profile-meta small {
    color: var(--muted);
}

.profile-menu {
    position: absolute;
    top: calc(100% + 0.65rem);
    right: 0;
    display: grid;
    gap: 0.5rem;
    min-width: 15rem;
    padding: 0.8rem;
    border-radius: 1.1rem;
    border: 1px solid var(--line);
    background: #fffdf9;
    box-shadow: 0 20px 40px rgba(103, 65, 20, 0.16);
    z-index: 100;
}

.profile-link,
.profile-button {
    width: 100%;
    text-align: left;
    padding: 0.78rem 0.9rem;
    border: 0;
    border-radius: 0.9rem;
    background: transparent;
    color: var(--text);
    text-decoration: none;
    cursor: pointer;
}

.profile-link:hover,
.profile-button:hover {
    background: rgba(217, 119, 6, 0.09);
}

.topbar h1,
.chart-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.page-panel {
    border: 1px solid var(--line);
    border-radius: 1.6rem;
    background: var(--surface);
    backdrop-filter: blur(12px);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin: 1.5rem 1.5rem 0;
}

.page-panel-header {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.5rem 1.5rem 0;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
    padding: 1.5rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 62rem;
}

.data-table th,
.data-table td {
    padding: 0.95rem 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(170, 117, 51, 0.14);
    vertical-align: top;
}

.data-table th {
    color: var(--primary-deep);
    font-size: 0.78rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.sort-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: inherit;
    text-decoration: none;
}

.sort-link.active {
    color: var(--primary-deep);
}

.sort-indicator {
    display: inline-grid;
    place-items: center;
    min-width: 1rem;
    font-size: 0.85rem;
    line-height: 1;
    opacity: 0.72;
}

.sort-link.active .sort-indicator {
    opacity: 1;
}

.data-table tbody tr:hover {
    background: rgba(217, 119, 6, 0.04);
}

.empty-cell {
    text-align: center;
    color: var(--muted);
    padding: 2rem 1rem;
}

.table-toolbar {
    display: flex;
    align-items: end;
    justify-content: end;
    gap: 0.85rem;
    flex-wrap: wrap;
}

.table-search,
.table-filter,
.table-page-size {
    display: grid;
    gap: 0.4rem;
}

.table-search span,
.table-filter span,
.table-page-size span {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--primary-deep);
}

.table-search input,
.table-filter select,
.table-page-size select {
    min-height: 2.9rem;
    border-radius: 0.95rem;
    border: 1px solid rgba(170, 117, 51, 0.18);
    background: rgba(255, 253, 249, 0.96);
    color: var(--text);
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.table-search input {
    width: min(22rem, 72vw);
    padding: 0.8rem 0.95rem;
}

.table-filter select,
.table-page-size select {
    padding: 0.8rem 2.2rem 0.8rem 0.95rem;
}

.table-search input:focus,
.table-filter select:focus,
.table-page-size select:focus {
    border-color: rgba(217, 119, 6, 0.45);
    box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1);
}

.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0 1.5rem 1.5rem;
}

.table-summary {
    margin: 0;
    color: var(--muted);
}

.pager {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.page-panel-actions {
    display: flex;
    justify-content: end;
    padding: 0 1.5rem 1.5rem;
}

.pager-link {
    min-width: 2.6rem;
    padding: 0.65rem 0.9rem;
    border-radius: 0.9rem;
    border: 1px solid var(--line);
    background: rgba(255, 251, 246, 0.94);
    color: var(--text);
    text-align: center;
    text-decoration: none;
}

.pager-link.active {
    background: linear-gradient(135deg, var(--primary), #eeab54);
    border-color: transparent;
    color: #fffdfa;
}

.pager-link.disabled {
    opacity: 0.45;
    pointer-events: none;
}

.placeholder-panel {
    padding: 1.5rem;
}

.placeholder-panel h2 {
    margin: 0;
    font-size: 1.5rem;
}

.manage-access-form {
    display: grid;
    gap: 1rem;
}

.access-table {
    min-width: 72rem;
}

.access-checkbox {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    white-space: nowrap;
}

.stat-card {
    padding: 1.35rem;
    border-radius: 1.5rem;
}

.stat-card strong {
    display: block;
    margin: 0.5rem 0 0.35rem;
    font-size: 2.2rem;
}

.stat-card p {
    margin: 0;
    color: var(--muted);
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.chart-note {
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: rgba(217, 119, 6, 0.1);
    color: var(--primary-deep);
    font-size: 0.9rem;
    font-weight: 600;
}

.chart-area {
    position: relative;
    padding: 1rem 0 0.2rem;
}

.chart-grid-lines {
    position: absolute;
    inset: 1rem 0 2rem;
    display: grid;
    grid-template-rows: repeat(4, 1fr);
    pointer-events: none;
}

.chart-grid-lines span {
    border-top: 1px dashed rgba(170, 117, 51, 0.22);
}

.line-chart {
    width: 100%;
    height: auto;
    display: block;
    position: relative;
    z-index: 1;
}

.chart-labels {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
    margin-top: 0.35rem;
    color: var(--muted);
    font-size: 0.9rem;
    text-align: center;
}

.brand-block,
.dashboard-header {
    margin-bottom: 1.75rem;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 1rem;
}

.eyebrow,
.panel-label {
    margin: 0 0 0.65rem;
    font-size: 0.72rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--primary-deep);
    font-weight: 700;
}

h1 {
    margin: 0;
    font-size: clamp(2rem, 4vw, 2.8rem);
    line-height: 1.08;
}

.lede {
    margin: 0.9rem 0 0;
    color: var(--muted);
    line-height: 1.6;
}

.auth-form {
    display: grid;
    gap: 1rem;
}

.field {
    display: grid;
    gap: 0.45rem;
}

.field span {
    font-size: 0.95rem;
    font-weight: 600;
}

.field input {
    width: 100%;
    padding: 0.92rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(170, 117, 51, 0.2);
    background: var(--surface-strong);
    color: var(--text);
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.field input:focus {
    border-color: rgba(217, 119, 6, 0.55);
    box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.12);
    transform: translateY(-1px);
}

.checkbox-row {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    font-size: 0.95rem;
    color: var(--muted);
}

.primary-button,
.secondary-button {
    border: 0;
    border-radius: 999px;
    padding: 0.95rem 1.4rem;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.primary-button {
    color: #fffdfa;
    background: linear-gradient(135deg, var(--primary), #e59b34);
    box-shadow: 0 16px 30px rgba(217, 119, 6, 0.22);
}

.secondary-button {
    color: var(--text);
    background: rgba(255, 251, 246, 0.88);
    border: 1px solid var(--line);
}

.primary-button:hover,
.secondary-button:hover {
    transform: translateY(-2px);
}

.error-text {
    color: #b42318;
    font-size: 0.85rem;
}

.status-banner {
    margin-bottom: 1.25rem;
    padding: 0.95rem 1rem;
    border-radius: 1rem;
    background: var(--success);
    color: #224625;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.student-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin: 1.5rem 1.5rem 0;
}

.compact-actions {
    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
}

.student-action-card {
    display: block;
    padding: 1.75rem;
    border-radius: 1.6rem;
    border: 1px solid var(--line);
    background: linear-gradient(135deg, rgba(255, 248, 241, 0.96), rgba(255, 255, 255, 0.72));
    box-shadow: var(--shadow);
    color: inherit;
    text-decoration: none;
}

.student-action-card strong {
    display: block;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    margin-bottom: 0.55rem;
}

.student-action-card p {
    margin: 0;
    color: var(--muted);
}

.compact-action-card {
    padding: 1.2rem;
}

.compact-action-card strong {
    font-size: 1.2rem;
}

.student-history-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.student-timeline {
    display: grid;
    gap: 0.85rem;
    padding: 0 1.5rem 1.5rem;
}

.timeline-item {
    display: grid;
    gap: 0.35rem;
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(170, 117, 51, 0.14);
    background: rgba(255, 255, 255, 0.56);
}

.timeline-item span,
.timeline-empty {
    color: var(--muted);
}

.info-panel {
    padding: 1.25rem;
    border-radius: 1.25rem;
    border: 1px solid var(--line);
    background: rgba(255, 255, 255, 0.58);
}

.info-panel strong {
    font-size: 1.05rem;
}

@media (max-width: 640px) {
    .content-wrap {
        padding: 1rem;
    }

    .auth-card,
    .dashboard-card {
        padding: 1.4rem;
        border-radius: 1.4rem;
    }

    .dashboard-header,
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
    }

    .student-quick-actions,
    .student-history-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1080px) {
    .dashboard-shell {
        grid-template-columns: 1fr;
    }

    .sidebar {
        min-height: auto;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .topbar {
        grid-template-columns: 1fr;
        display: grid;
        width: 100%;
        border-radius: 0 0 1.35rem 1.35rem;
    }

    .topbar-title {
        align-items: start;
    }

    .page-panel-header,
    .table-footer {
        grid-template-columns: 1fr;
        display: grid;
    }

    .table-toolbar {
        justify-content: start;
    }

    .table-search input {
        width: 100%;
    }

    .chart-labels {
        grid-template-columns: repeat(4, 1fr);
    }
}

#sidebar-toggle:checked ~ .sidebar {
    width: var(--sidebar-collapsed-width);
    padding-left: 1rem;
    padding-right: 1rem;
}

#sidebar-toggle:checked ~ .dashboard-main {
    padding-left: 1rem;
    padding-right: 1rem;
}

#sidebar-toggle:checked ~ .sidebar .sidebar-brand {
    justify-content: center;
}

#sidebar-toggle:checked ~ .sidebar .sidebar-brand > div:last-child {
    display: none;
}

#sidebar-toggle:checked ~ .sidebar .sidebar-nav {
    justify-items: center;
    gap: 0.6rem;
}

#sidebar-toggle:checked ~ .sidebar .sidebar-link {
    width: 100%;
    min-height: 2.9rem;
    padding: 0.8rem 0;
    text-indent: -9999px;
    position: relative;
    overflow: hidden;
    transform: none;
}

#sidebar-toggle:checked ~ .sidebar .sidebar-link::before {
    content: '';
    position: absolute;
    inset: 50% auto auto 50%;
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 999px;
    background: currentColor;
    transform: translate(-50%, -50%);
    opacity: 0.7;
}

#sidebar-toggle:checked ~ .dashboard-shell,
#sidebar-toggle:checked ~ .sidebar,
#sidebar-toggle:checked ~ .dashboard-main {
    transition: all 0.25s ease;
}

.dashboard-shell:has(#sidebar-toggle:checked) {
    grid-template-columns: var(--sidebar-collapsed-width) minmax(0, 1fr);
}
</style>
