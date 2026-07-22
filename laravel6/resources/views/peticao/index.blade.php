@extends('layouts.app')

@section('title', 'Montagem de peticoes')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Montagem de peticoes</h2>
</div>

<div class="panel" style="padding:0;">
    <div class="panel-muted" style="margin:16px;">
        <strong>Modelos normalizados</strong>
        <div class="editor-note">Fonte principal da montagem. O legado aparece abaixo apenas quando ainda nao possui espelho.</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Modelo</th>
                <th>Setor</th>
                <th>Cliente</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($modelos as $modelo)
                <tr>
                    <td>{{ $modelo->legacy_tipo_id ?: $modelo->id }}</td>
                    <td>{{ $modelo->nome }}</td>
                    <td>{{ optional($modelo->setor)->nome_setor }}</td>
                    <td>{{ optional($modelo->cliente)->cliente_name ?: 'Todos do setor' }}</td>
                    <td><a href="{{ route('peticoes.normalized.show', $modelo) }}">Montar</a></td>
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
            <div class="editor-note">Modelos ainda nao sincronizados com `peticao_modelos`.</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Modelo</th>
                    <th>Setor</th>
                    <th>Cliente</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($legacyFallbacks as $modelo)
                    <tr>
                        <td>{{ $modelo->tipo_id }}</td>
                        <td>{{ $modelo->tipo_nome }}</td>
                        <td>{{ optional($modelo->setor)->nome_setor }}</td>
                        <td>{{ optional($modelo->cliente)->cliente_name ?: 'Todos do setor' }}</td>
                        <td><a href="{{ route('peticoes.show', $modelo) }}">Montar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
