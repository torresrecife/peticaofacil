@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Clientes</h2>
    <a class="button link" href="{{ route('admin.clientes.create') }}">Novo cliente</a>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Codigo</th>
                <th>Setor</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->cliente_id }}</td>
                    <td>{{ $cliente->cliente_name }}</td>
                    <td>{{ $cliente->cliente_cod }}</td>
                    <td>{{ optional($cliente->setor)->nome_setor }}</td>
                    <td>{{ $cliente->cliente_status === 'Y' ? 'Ativo' : 'Inativo' }}</td>
                    <td><a href="{{ route('admin.clientes.edit', $cliente) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $clientes->links('vendor.pagination.default') }}
</div>
@endsection
