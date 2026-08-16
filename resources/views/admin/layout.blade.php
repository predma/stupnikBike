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
        .file-manager-grid {
            gap: 18px;
        }
        .breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 18px;
            color: var(--muted);
        }
        .breadcrumbs a {
            color: var(--text);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(148, 163, 184, 0.08);
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
        .file-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .folder-grid,
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
        }
        .folder-card,
        .media-card {
            border: 1px solid rgba(148, 163, 184, 0.14);
            background: rgba(255, 255, 255, 0.035);
            border-radius: 18px;
            padding: 12px;
        }
        .folder-open {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .folder-icon {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.25), rgba(74, 222, 128, 0.18));
            color: #bae6fd;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }
        .media-preview {
            width: 100%;
            height: 150px;
            padding: 0;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.9);
            cursor: pointer;
        }
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .media-meta {
            display: grid;
            gap: 3px;
            margin: 10px 0;
            min-height: 42px;
        }
        .media-meta strong {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .media-meta span {
            color: var(--muted);
            font-size: 12px;
        }
        .media-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .link-danger {
            border: 0;
            background: transparent;
            color: #fda4af;
            cursor: pointer;
            padding: 6px 0;
        }
        @media (max-width: 1200px) {
            .stats, .dashboard-panels { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: relative; height: auto; border-right: 0; border-bottom: 1px solid var(--line); }
        }
        @media (max-width: 720px) {
            .content { padding: 18px; }
            .topbar, .table-head { flex-direction: column; align-items: stretch; }
            .stats, .dashboard-panels { grid-template-columns: 1fr; }
            th, td { padding: 12px 14px; }
            .file-toolbar, .inline-form, .image-picker { grid-template-columns: 1fr; }
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
