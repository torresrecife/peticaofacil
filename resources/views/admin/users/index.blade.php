@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Usuarios</h2>
    <a class="button link" href="{{ route('admin.usuarios.create') }}">Novo usuario</a>
</div>

<div class="panel" style="margin-bottom:16px;">
    <form method="get" action="{{ route('admin.usuarios.index') }}" style="display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:12px; align-items:end;">
        <div>
            <label for="user-search" style="display:block; font-size:12px; color:#6b7280; margin-bottom:6px;">Buscar usuario</label>
            <input
                id="user-search"
                type="text"
                name="q"
                value="{{ $search ?? '' }}"
                placeholder="Nome, login, email ou ID"
                list="user-search-suggestions"
            >
            <datalist id="user-search-suggestions">
                @foreach($users as $user)
                    <option value="{{ $user->nome_usu }}">{{ $user->login_usu }}</option>
                    <option value="{{ $user->login_usu }}">{{ $user->nome_usu }}</option>
                    @if(!empty($user->email_usu))
                        <option value="{{ $user->email_usu }}">{{ $user->nome_usu }}</option>
                    @endif
                    <option value="{{ $user->id }}">{{ $user->nome_usu }}</option>
                @endforeach
            </datalist>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit">Buscar</button>
            @if(!empty($search))
                <a class="button link" href="{{ route('admin.usuarios.index') }}">Limpar</a>
            @endif
        </div>
    </form>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Login</th>
                <th>Nivel</th>
                <th>Status</th>
                <th>Setor</th>
                <th>Clientes</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->nome_usu }}</td>
                    <td>{{ $user->login_usu }}</td>
                    <td>{{ $user->nivel_usu }}</td>
                    <td>{{ $user->status_usu }}</td>
                    <td>{{ optional($user->setor)->nome_setor }}</td>
                    <td>{{ $user->client_labels ? implode(', ', $user->client_labels) : '-' }}</td>
                    <td><a href="{{ route('admin.usuarios.edit', $user) }}">Editar</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#6b7280; padding:18px;">
                        Nenhum usuario encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $users->links('vendor.pagination.default') }}
</div>
@endsection
