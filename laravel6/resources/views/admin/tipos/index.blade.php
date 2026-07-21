@extends('layouts.app')

@section('title', 'Modelos')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Modelos de peticao</h2>
    <a class="button link" href="{{ route('admin.modelos.create') }}">Novo modelo</a>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Modelo</th>
                <th>Descricao</th>
                <th>Setor</th>
                <th>Cliente</th>
                <th>Servidor</th>
                <th>Arquivo</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($tipos as $tipo)
                <tr>
                    <td>{{ $tipo->tipo_id }}</td>
                    <td>{{ $tipo->tipo_nome }}</td>
                    <td>{{ $tipo->nome_pre }}</td>
                    <td>{{ optional($tipo->setor)->nome_setor }}</td>
                    <td>{{ optional($tipo->cliente)->cliente_name ?: 'Todos do setor' }}</td>
                    <td>{{ optional($tipo->servidor)->nome_db }}</td>
                    <td>{{ $tipo->tipo_arq }}</td>
                    <td>{{ $tipo->tipo_stt === 'Y' ? 'Ativo' : 'Inativo' }}</td>
                    <td><a href="{{ route('admin.modelos.edit', $tipo) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $tipos->links('vendor.pagination.default') }}
</div>
@endsection
