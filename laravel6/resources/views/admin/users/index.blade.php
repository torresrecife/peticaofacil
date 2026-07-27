@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Usuarios</h2>
    <a class="button link" href="{{ route('admin.usuarios.create') }}">Novo usuario</a>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ID legado</th>
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
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->legacy_usuario_id ?: '-' }}</td>
                    <td>{{ $user->nome_usu }}</td>
                    <td>{{ $user->login_usu }}</td>
                    <td>{{ $user->nivel_usu }}</td>
                    <td>{{ $user->status_usu }}</td>
                    <td>{{ optional($user->setor)->nome_setor }}</td>
                    <td>{{ $user->client_labels ? implode(', ', $user->client_labels) : '-' }}</td>
                    <td><a href="{{ route('admin.usuarios.edit', $user) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $users->links('vendor.pagination.default') }}
</div>
@endsection
