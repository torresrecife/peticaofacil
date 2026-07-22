@extends('layouts.app')

@section('title', 'Modelos')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Modelos de peticao</h2>
    <a class="button link" href="{{ route('admin.modelos.create') }}">Novo modelo</a>
</div>

<div class="panel" style="padding:0;">
    <div class="panel-muted" style="margin:16px;">
        <strong>Modelos normalizados</strong>
        <div class="editor-note">A edicao principal agora parte de `peticao_modelos`. O legado abaixo fica como fallback.</div>
    </div>
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
                <th>Mirror</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($modelos as $modelo)
                <tr>
                    <td>{{ $modelo->legacy_tipo_id ?: $modelo->id }}</td>
                    <td>{{ $modelo->nome }}</td>
                    <td>{{ $modelo->nome_pre }}</td>
                    <td>{{ optional($modelo->setor)->nome_setor }}</td>
                    <td>{{ optional($modelo->cliente)->cliente_name ?: 'Todos do setor' }}</td>
                    <td>{{ optional($modelo->servidor)->nome_db }}</td>
                    <td>{{ $modelo->arquivo_padrao }}</td>
                    <td>{{ $modelo->status === 'ativo' ? 'Ativo' : 'Inativo' }}</td>
                    <td>
                        <div><strong>#{{ $modelo->id }}</strong> {{ $modelo->slug }}</div>
                        <div class="editor-note">{{ $modelo->paragrafos_count }} paragrafos, {{ $modelo->campos_count }} campos</div>
                    </td>
                    <td><a href="{{ route('admin.modelos-normalizados.edit', $modelo) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $modelos->links('vendor.pagination.default') }}
</div>

@if($legacyFallbacks->isNotEmpty())
    <div class="panel" style="padding:0;margin-top:20px;">
        <div class="panel-muted" style="margin:16px;">
            <strong>Fallback legado</strong>
            <div class="editor-note">Modelos ainda nao espelhados em `peticao_modelos`.</div>
        </div>
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
                @foreach($legacyFallbacks as $tipo)
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
@endif
@endsection
