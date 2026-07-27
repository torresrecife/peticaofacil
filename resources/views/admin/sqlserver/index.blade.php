@extends('layouts.app')

@section('title', 'Servidores SQL')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Servidores SQL</h2>
    <a class="button link" href="{{ route('admin.servidores-normalizados.create') }}">Novo servidor</a>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Host</th>
                <th>Banco</th>
                <th>Tabela</th>
                <th>Status</th>
                <th>Mirror legado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($configs as $config)
                <tr>
                    <td>{{ $config->id }}</td>
                    <td>{{ $config->nome_db }}</td>
                    <td>{{ $config->ip_db }}</td>
                    <td>{{ $config->data_db }}</td>
                    <td>{{ $config->table_db }}</td>
                    <td>{{ $config->stt === 'Y' ? 'Ativo' : 'Inativo' }}</td>
                    <td>{{ $config->legacy_config_id ?: '-' }}</td>
                    <td><a href="{{ route('admin.servidores-normalizados.edit', $config) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $configs->links('vendor.pagination.default') }}
</div>
@endsection
