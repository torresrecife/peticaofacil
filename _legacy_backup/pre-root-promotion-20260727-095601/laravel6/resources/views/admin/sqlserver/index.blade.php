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

@if($legacyFallback->isNotEmpty())
    <div class="panel" style="margin-top:18px; padding:0;">
        <div style="padding:16px 18px; border-bottom:1px solid #e5e7eb;">
            <strong>Fallback legado</strong>
            <div style="margin-top:4px; color:#6b7280; font-size:13px;">Servidores ainda nao espelhados para a trilha normalizada.</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID legado</th>
                    <th>Nome</th>
                    <th>Host</th>
                    <th>Banco</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($legacyFallback as $config)
                <tr>
                    <td>{{ $config->id_db }}</td>
                    <td>{{ $config->nome_db }}</td>
                    <td>{{ $config->ip_db }}</td>
                    <td>{{ $config->data_db }}</td>
                    <td>{{ $config->stt === 'Y' ? 'Ativo' : 'Inativo' }}</td>
                    <td><a href="{{ route('admin.servidores.edit', $config) }}">Editar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
