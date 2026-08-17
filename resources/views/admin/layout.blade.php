<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Stupnik Bike Admin' }}</title>
    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(11, 18, 32, 0.88);
            --panel-2: rgba(16, 24, 40, 0.92);
            --line: rgba(148, 163, 184, 0.18);
            --text: #e5eefb;
            --muted: #92a3bd;
            --accent: #4ade80;
            --accent-2: #38bdf8;
            --danger: #fb7185;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(74, 222, 128, 0.10), transparent 22%),
                linear-gradient(180deg, #04101e 0%, #07111f 60%, #050b15 100%);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }
        .sidebar {
            padding: 28px 20px;
            background: linear-gradient(180deg, rgba(6, 11, 20, 0.96), rgba(9, 15, 28, 0.98));
            border-right: 1px solid var(--line);
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .brand-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            box-shadow: 0 14px 35px rgba(56, 189, 248, 0.25);
        }
        .brand h1 {
            margin: 0;
            font-size: 18px;
            line-height: 1.1;
        }
        .brand p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }
        .nav {
            display: grid;
            gap: 8px;
        }
        .nav a {
            padding: 12px 14px;
            border-radius: 14px;
            color: var(--muted);
            background: transparent;
            border: 1px solid transparent;
            transition: 0.18s ease;
        }
        .nav a:hover, .nav a.active {
            background: rgba(56, 189, 248, 0.10);
            border-color: rgba(56, 189, 248, 0.18);
            color: var(--text);
        }
        .content {
            padding: 28px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .topbar .meta {
            color: var(--muted);
            font-size: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 14px;
            padding: 11px 16px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent-2), #0ea5e9);
            color: white;
            box-shadow: 0 12px 24px rgba(14, 165, 233, 0.22);
        }
        .btn.secondary {
            background: rgba(148, 163, 184, 0.12);
            color: var(--text);
            box-shadow: none;
            border: 1px solid var(--line);
        }
        .btn.danger {
            background: rgba(251, 113, 133, 0.14);
            color: #fecdd3;
            box-shadow: none;
            border: 1px solid rgba(251, 113, 133, 0.28);
        }
        .grid {
            display: grid;
            gap: 20px;
        }
        .stats {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.20);
        }
        .card h2, .card h3 { margin: 0; }
        .stat-value {
            font-size: 30px;
            font-weight: 800;
            margin-top: 12px;
        }
        .stat-label {
            color: var(--muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .table-card { padding: 0; overflow: hidden; }
        .table-head {
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            text-align: left;
            vertical-align: top;
        }
        th {
            color: #9fb0c8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            background: rgba(15, 23, 42, 0.40);
        }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
            color: var(--text);
            font-size: 12px;
        }
        .muted { color: var(--muted); }
        .dashboard-panels {
            grid-template-columns: 2fr 1fr 1fr;
        }
        .panel-list {
            display: grid;
            gap: 12px;
        }
        .panel-item {
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.10);
        }
        .login-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .login-card {
            width: min(520px, 100%);
            background: rgba(8, 14, 26, 0.94);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
        }
        .field {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }
        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.9);
            color: white;
            border-radius: 14px;
            padding: 13px 14px;
            outline: none;
        }
        .field select,
        .field textarea {
            resize: vertical;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: rgba(56, 189, 248, 0.55);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.10);
        }
        .error {
            color: #fda4af;
            font-size: 13px;
            margin-top: 8px;
        }
        .image-picker {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }
        .image-preview {
            width: 140px;
            height: 92px;
            border: 1px solid var(--line);
            border-radius: 16px;
            object-fit: cover;
            background: rgba(15, 23, 42, 0.9);
            display: none;
        }
        .image-preview[src]:not([src=""]) {
            display: block;
        }
        .btn.compact {
            border-radius: 11px;
            padding: 8px 11px;
            font-size: 12px;
        }
        .file-toolbar {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 16px;
        }
        .inline-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }
        .inline-form input {
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.9);
            color: white;
            border-radius: 14px;
            padding: 13px 14px;
            outline: none;
            width: 100%;
        }
        .fm-shell {
            display: grid;
            grid-template-columns: 310px minmax(0, 1fr);
            min-height: calc(100vh - 170px);
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 28px;
            background: rgba(7, 13, 25, 0.72);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.25);
        }
        .fm-sidebar {
            border-right: 1px solid rgba(148, 163, 184, 0.16);
            background:
                linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(9, 15, 28, 0.96)),
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.16), transparent 35%);
            padding: 18px;
            display: grid;
            grid-template-rows: auto auto minmax(220px, 1fr) auto;
            gap: 14px;
        }
        .fm-sidebar-head,
        .fm-top,
        .fm-browser-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .fm-sidebar h2,
        .fm-top h2,
        .fm-browser-head h3 {
            margin: 0;
        }
        .fm-kicker {
            color: var(--accent-2);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .fm-root-link,
        .fm-folder-row {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
            border: 1px solid transparent;
            border-radius: 16px;
            padding: 11px;
            color: var(--text);
            background: rgba(255, 255, 255, 0.03);
        }
        .fm-root-link.active,
        .fm-root-link:hover,
        .fm-folder-row:hover {
            border-color: rgba(56, 189, 248, 0.24);
            background: rgba(56, 189, 248, 0.10);
        }
        .fm-root-link span:last-child,
        .fm-folder-row strong {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fm-root-link small {
            display: block;
            color: var(--muted);
            margin-top: 2px;
        }
        .fm-folder-mark,
        .fm-upload-icon {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.24), rgba(74, 222, 128, 0.16));
            color: #d7f7ff;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .08em;
        }
        .fm-folder-list {
            overflow: auto;
            display: grid;
            align-content: start;
            gap: 8px;
            padding-right: 2px;
        }
        .fm-folder-row-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }
        .fm-icon-danger {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            border: 1px solid rgba(251, 113, 133, 0.24);
            background: rgba(251, 113, 133, 0.10);
            color: #fecdd3;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }
        .fm-empty-mini {
            padding: 14px;
            color: var(--muted);
            border: 1px dashed rgba(148, 163, 184, 0.18);
            border-radius: 16px;
            text-align: center;
        }
        .fm-create-folder {
            border-top: 1px solid rgba(148, 163, 184, 0.14);
            padding-top: 14px;
            display: grid;
            gap: 8px;
        }
        .fm-create-folder label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .fm-create-folder div {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
        }
        .fm-create-folder input,
        .fm-dropzone input::file-selector-button {
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.9);
            color: white;
            border-radius: 12px;
            padding: 10px 11px;
            outline: none;
            width: 100%;
        }
        .fm-main {
            min-width: 0;
            padding: 20px;
            display: grid;
            grid-template-rows: auto auto auto minmax(0, 1fr);
            gap: 18px;
            background:
                radial-gradient(circle at 80% 0%, rgba(74, 222, 128, 0.08), transparent 30%),
                rgba(11, 18, 32, 0.56);
        }
        .fm-top {
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.13);
        }
        .fm-path {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            align-items: center;
            margin-top: 9px;
            color: var(--muted);
        }
        .fm-path a {
            color: #c7d2fe;
            background: rgba(99, 102, 241, 0.10);
            border: 1px solid rgba(129, 140, 248, 0.16);
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 12px;
        }
        .fm-stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .fm-stats span {
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(255, 255, 255, 0.04);
            border-radius: 999px;
            padding: 8px 11px;
            color: var(--muted);
            font-size: 12px;
        }
        .fm-upload {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: stretch;
        }
        .fm-dropzone {
            display: flex;
            gap: 13px;
            align-items: center;
            min-width: 0;
            border: 1px dashed rgba(56, 189, 248, 0.34);
            border-radius: 20px;
            padding: 14px;
            background: rgba(56, 189, 248, 0.065);
            cursor: pointer;
        }
        .fm-dropzone span:nth-child(2) {
            display: grid;
            gap: 2px;
            min-width: 0;
        }
        .fm-dropzone small {
            color: var(--muted);
        }
        .fm-dropzone input {
            margin-left: auto;
            max-width: 310px;
            color: var(--muted);
        }
        .fm-browser-head p {
            margin: 4px 0 0;
        }
        .fm-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 16px;
            align-content: start;
            overflow: auto;
            padding: 2px 2px 10px;
        }
        .fm-file {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: rgba(255, 255, 255, 0.045);
            border-radius: 20px;
            transition: transform .16s ease, border-color .16s ease, background .16s ease;
        }
        .fm-file:hover {
            transform: translateY(-2px);
            border-color: rgba(56, 189, 248, 0.32);
            background: rgba(255, 255, 255, 0.065);
        }
        .fm-thumb {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            padding: 0;
            border: 0;
            background: rgba(15, 23, 42, 0.9);
            cursor: pointer;
        }
        .fm-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .fm-file-body {
            padding: 12px;
            display: grid;
            gap: 12px;
        }
        .fm-file-body strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 13px;
        }
        .fm-file-body span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }
        .fm-file-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            align-items: center;
        }
        .fm-empty {
            grid-column: 1 / -1;
            min-height: 260px;
            display: grid;
            place-content: center;
            gap: 8px;
            text-align: center;
            border: 1px dashed rgba(148, 163, 184, 0.20);
            border-radius: 22px;
            color: var(--muted);
            background: rgba(255, 255, 255, 0.025);
        }
        .fm-empty strong {
            color: var(--text);
            font-size: 18px;
        }
        @media (max-width: 1200px) {
            .stats, .dashboard-panels { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: relative; height: auto; border-right: 0; border-bottom: 1px solid var(--line); }
            .fm-shell { grid-template-columns: 1fr; }
            .fm-sidebar {
                border-right: 0;
                border-bottom: 1px solid rgba(148, 163, 184, 0.16);
                grid-template-rows: auto auto auto auto;
            }
            .fm-folder-list {
                max-height: 240px;
            }
        }
        @media (max-width: 720px) {
            .content { padding: 18px; }
            .topbar, .table-head { flex-direction: column; align-items: stretch; }
            .stats, .dashboard-panels { grid-template-columns: 1fr; }
            th, td { padding: 12px 14px; }
            .file-toolbar, .inline-form, .image-picker { grid-template-columns: 1fr; }
            .fm-main, .fm-sidebar { padding: 14px; }
            .fm-top, .fm-upload, .fm-sidebar-head, .fm-browser-head { grid-template-columns: 1fr; display: grid; align-items: stretch; }
            .fm-dropzone { display: grid; }
            .fm-dropzone input { max-width: 100%; margin-left: 0; }
            .fm-media-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .fm-create-folder div { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php($currentRoute = request()->route()?->getName())
    @auth
        <div class="shell">
            <aside class="sidebar">
                <div class="brand">
                    <div class="brand-badge"></div>
                    <div>
                        <h1>Stupnik Bike</h1>
                        <p>Admin dashboard</p>
                    </div>
                </div>

                <nav class="nav">
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.stations') ? 'active' : '' }}" href="{{ route('admin.stations.index') }}">Stanice</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.bikes') ? 'active' : '' }}" href="{{ route('admin.bikes.index') }}">Bicikli</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.bike-prices') ? 'active' : '' }}" href="{{ route('admin.bike-prices.index') }}">Cjenik</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.file-manager') ? 'active' : '' }}" href="{{ route('admin.file-manager.index') }}">File Manager</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.reservations') ? 'active' : '' }}" href="{{ route('admin.reservations.index') }}">Rezervacije</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.reservation-settings') ? 'active' : '' }}" href="{{ route('admin.reservation-settings.index') }}">Rezervacijske postavke</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.issues') ? 'active' : '' }}" href="{{ route('admin.issues.index') }}">Kvarovi</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.notifications') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}">Obavijesti</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.users') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Korisnici</a>
                    <a class="{{ str_contains($currentRoute ?? '', 'admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">Postavke</a>
                </nav>
            </aside>

            <main class="content">
                <div class="topbar">
                    <div>
                        <div class="pill">Općina Stupnik</div>
                        <h1 style="margin: 12px 0 6px; font-size: 32px;">{{ $pageTitle ?? 'Dashboard' }}</h1>
                        <div class="meta">{{ $pageSubtitle ?? 'Upravljanje najmom bicikala, servisom i korisnicima.' }}</div>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="btn secondary" type="submit">Odjava</button>
                    </form>
                </div>

                @if (session('status'))
                    <div class="card" style="margin-bottom: 20px; border-color: rgba(74, 222, 128, 0.35); background: rgba(20, 83, 45, 0.22);">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    @else
        @yield('content')
    @endauth
    @yield('scripts')
</body>
</html>
