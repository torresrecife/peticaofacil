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
        }
        .sidebar {
            background: #102a43;
            color: #d9e2ec;
            padding: 24px 20px;
        }
        .sidebar h1 {
            margin: 0 0 24px;
            font-size: 20px;
            color: #f0f4f8;
        }
        .sidebar a {
            color: #d9e2ec;
            display: block;
            padding: 10px 0;
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
        @media (max-width: 900px) {
            .shell {
                grid-template-columns: 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
@auth
    <div class="shell">
        <aside class="sidebar">
            <h1>Peticao Facil</h1>
            <a href="{{ route('dashboard') }}">Painel</a>
            <a href="{{ route('admin.usuarios.index') }}">Usuarios</a>
            <a href="{{ route('admin.setores.index') }}">Setores</a>
            <a href="{{ route('admin.clientes.index') }}">Clientes</a>
            <a href="{{ route('admin.servidores.index') }}">Servidores SQL</a>
            <a href="{{ route('admin.modelos.index') }}">Modelos</a>
            <a href="{{ route('peticoes.index') }}">Montagem</a>
            <a href="{{ route('pecas.index') }}">Pecas salvas</a>
            <a href="{{ route('status') }}">Status da migracao</a>
        </aside>
        <main class="content">
            <div class="topbar">
                <div>
                    <strong>{{ auth()->user()->nome_usu }}</strong>
                    <div>{{ auth()->user()->nivel_usu }}</div>
                </div>
                <form method="post" action="{{ route('logout') }}">
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
</body>
</html>
