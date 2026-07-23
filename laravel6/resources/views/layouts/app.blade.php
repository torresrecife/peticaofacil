<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Peticao Facil')</title>
    @stack('head')
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f7;
            color: #243b53;
        }
        a {
            color: #1f5f8b;
            text-decoration: none;
        }
        .shell {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
            transition: grid-template-columns .2s ease;
        }
        .shell.is-collapsed {
            grid-template-columns: 72px 1fr;
        }
        .sidebar {
            background: #102a43;
            color: #d9e2ec;
            padding: 24px 20px;
            overflow: hidden;
            transition: padding .2s ease;
        }
        .shell.is-collapsed .sidebar {
            padding: 24px 12px;
        }
        .sidebar h1 {
            margin: 0 0 24px;
            font-size: 20px;
            color: #f0f4f8;
            white-space: nowrap;
            transition: opacity .15s ease;
        }
        .sidebar-nav {
            display: grid;
            gap: 4px;
        }
        .sidebar-link {
            color: #d9e2ec;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 6px;
            transition: background .15s ease, color .15s ease;
        }
        .sidebar-link:hover {
            background: rgba(217, 226, 236, 0.08);
            color: #f0f4f8;
        }
        .sidebar-link__icon {
            width: 24px;
            flex: 0 0 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-link__icon svg,
        .sidebar-brand__icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .sidebar-link__label,
        .sidebar-brand__label {
            white-space: nowrap;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 0 24px;
            min-height: 32px;
        }
        .sidebar-brand__icon {
            width: 24px;
            flex: 0 0 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .shell.is-collapsed .sidebar-link__label,
        .shell.is-collapsed .sidebar-brand__label {
            opacity: 0;
            pointer-events: none;
        }
        .content {
            padding: 24px 32px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-toggle {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 18px;
            line-height: 1;
        }
        .panel {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 6px;
            padding: 20px;
        }
        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .stat {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 6px;
            padding: 16px;
        }
        .stat strong {
            display: block;
            font-size: 28px;
            margin-top: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #d9e2ec;
            vertical-align: top;
        }
        th {
            font-size: 13px;
            color: #486581;
        }
        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .button, button {
            background: #1f5f8b;
            color: #fff;
            border: 0;
            border-radius: 4px;
            padding: 10px 14px;
            cursor: pointer;
            font-size: 14px;
        }
        .button.secondary {
            background: #627d98;
        }
        .button.link {
            display: inline-block;
        }
        .pagination-wrap {
            margin-top: 16px;
        }
        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .pagination li {
            margin: 0;
        }
        .pagination a,
        .pagination span {
            display: inline-flex;
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            align-items: center;
            justify-content: center;
            border: 1px solid #bcccdc;
            border-radius: 4px;
            background: #fff;
            color: #243b53;
            box-sizing: border-box;
        }
        .pagination a:hover {
            border-color: #1f5f8b;
            color: #1f5f8b;
        }
        .pagination .active span {
            background: #1f5f8b;
            border-color: #1f5f8b;
            color: #fff;
        }
        .pagination .disabled span {
            background: #f0f4f8;
            color: #9fb3c8;
            border-color: #d9e2ec;
        }
        .flash {
            background: #e3fcec;
            color: #1f5134;
            border: 1px solid #b7ebc6;
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .errors {
            background: #fdecea;
            color: #8a1f17;
            border: 1px solid #f5c6cb;
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        input, select, textarea {
            border: 1px solid #bcccdc;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 14px;
        }
        textarea {
            min-height: 110px;
            resize: vertical;
            font-family: Arial, sans-serif;
        }
        .stack {
            display: grid;
            gap: 16px;
        }
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .section-title h3,
        .section-title h2 {
            margin: 0;
        }
        .panel-muted {
            background: #f8fbfd;
            border: 1px solid #d9e2ec;
            border-radius: 6px;
            padding: 16px;
        }
        .editor-note {
            font-size: 13px;
            color: #627d98;
        }
        .accordion-item {
            border: 1px solid #d9e2ec;
            border-radius: 6px;
            background: #fff;
        }
        .accordion-item summary {
            cursor: pointer;
            list-style: none;
            padding: 14px 16px;
            font-weight: bold;
        }
        .accordion-item summary::-webkit-details-marker {
            display: none;
        }
        .accordion-body {
            padding: 0 16px 16px;
        }
        .login-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 6px;
            padding: 24px;
        }
        mark.diff-changed {
            background: #ffe58f;
            color: inherit;
            padding: 0;
        }
        mark.diff-added {
            background: #c6f6d5;
            color: inherit;
            padding: 0;
        }
        mark.diff-removed {
            background: #fed7d7;
            color: inherit;
            padding: 0;
        }
        @media (max-width: 900px) {
            .shell {
                grid-template-columns: 1fr;
            }
            .shell.is-collapsed {
                grid-template-columns: 1fr;
            }
            .sidebar {
                padding: 16px;
            }
            .shell.is-collapsed .sidebar {
                display: none;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .content {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>
@auth
    <div class="shell" id="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="sidebar-brand__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 3h9l3 3v15H6z"></path>
                        <path d="M15 3v4h4"></path>
                        <path d="M9 13h6"></path>
                        <path d="M9 17h4"></path>
                    </svg>
                </span>
                <strong class="sidebar-brand__label">Peticao Facil</strong>
            </div>
            <nav class="sidebar-nav">
                <a class="sidebar-link" href="{{ route('dashboard') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 10.5 12 3l9 7.5"></path>
                            <path d="M5 9.5V21h14V9.5"></path>
                            <path d="M9 21v-6h6v6"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Painel</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.usuarios.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9.5" cy="7" r="3"></circle>
                            <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M14.5 4.2a3 3 0 0 1 0 5.6"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Usuarios</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.setores.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 21h18"></path>
                            <path d="M5 21V7l7-4 7 4v14"></path>
                            <path d="M9 9h.01"></path>
                            <path d="M9 13h.01"></path>
                            <path d="M15 9h.01"></path>
                            <path d="M15 13h.01"></path>
                            <path d="M10 21v-4h4v4"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Setores</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.clientes.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 3h10l3 3v15H7z"></path>
                            <path d="M17 3v4h4"></path>
                            <path d="M10 11h7"></path>
                            <path d="M10 15h7"></path>
                            <path d="M10 19h4"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Clientes</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.servidores-normalizados.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="6" rx="2"></rect>
                            <rect x="3" y="14" width="18" height="6" rx="2"></rect>
                            <path d="M7 7h.01"></path>
                            <path d="M7 17h.01"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Servidores SQL</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.modelos-normalizados.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M6 3h9l3 3v15H6z"></path>
                            <path d="M15 3v4h4"></path>
                            <path d="M9 12h6"></path>
                            <path d="M9 16h6"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Modelos</span>
                </a>
                <a class="sidebar-link" href="{{ route('admin.listas.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="6" y="4" width="12" height="17" rx="2"></rect>
                            <path d="M9 4.5h6"></path>
                            <path d="M9 9h6"></path>
                            <path d="M9 13h6"></path>
                            <path d="M9 17h4"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Listas</span>
                </a>
                <a class="sidebar-link" href="{{ route('peticoes.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Montagem</span>
                </a>
                <a class="sidebar-link" href="{{ route('pecas.index') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <path d="M3 10h18"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Pecas salvas</span>
                </a>
                <a class="sidebar-link" href="{{ route('status') }}">
                    <span class="sidebar-link__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 19h16"></path>
                            <path d="M7 16V9"></path>
                            <path d="M12 16V5"></path>
                            <path d="M17 16v-4"></path>
                        </svg>
                    </span>
                    <span class="sidebar-link__label">Status da migracao</span>
                </a>
            </nav>
        </aside>
        <main class="content">
            <div class="topbar">
                <div class="topbar-left">
                    <button type="button" class="button secondary sidebar-toggle" id="sidebar-toggle" aria-label="Recolher menu lateral" aria-expanded="true">☰</button>
                    <div>
                        <strong>{{ auth()->user()->nome_usu }}</strong>
                        <div>{{ auth()->user()->nivel_usu }}</div>
                    </div>
                </div>
                <form method="get" action="{{ route('legacy.logout') }}">
                    @csrf
                    <button type="submit" class="button secondary">Sair</button>
                </form>
            </div>

            @if(session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="errors">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@else
    @yield('content')
@endauth
@stack('scripts')
@auth
<script>
document.addEventListener('DOMContentLoaded', function () {
    var shell = document.getElementById('app-shell');
    var toggle = document.getElementById('sidebar-toggle');
    var storageKey = 'peticaofacil.sidebar.collapsed';

    if (!shell || !toggle) {
        return;
    }

    function applyState(collapsed) {
        shell.classList.toggle('is-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.setAttribute('aria-label', collapsed ? 'Expandir menu lateral' : 'Recolher menu lateral');
    }

    applyState(window.localStorage.getItem(storageKey) === '1');

    toggle.addEventListener('click', function () {
        var collapsed = !shell.classList.contains('is-collapsed');
        applyState(collapsed);
        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    });
});
</script>
@endauth
</body>
</html>
