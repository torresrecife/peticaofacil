@extends('layouts.app')

@section('title', 'Historico de peticoes salvas')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Historico de peticoes salvas</h2>
    <div class="actions">
        <a class="button" href="{{ route('peticoes.avulsas.create') }}">Nova peticao avulsa</a>
    </div>
</div>

<style>
    .pecas-origin-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
    }
    .pecas-origin-badge.is-avulsa {
        background: #fff7d6;
        color: #8d5c00;
        border: 1px solid #f7c948;
    }
    .pecas-origin-badge.is-modelo {
        background: #ebf5ff;
        color: #1d4f91;
        border: 1px solid #9fd0ff;
    }
</style>

<div class="panel" style="margin-bottom:20px;">
    <div class="panel-muted" style="margin-bottom:16px;">
        <strong>Consulta historica</strong>
        <div class="editor-note">Esta tela consulta apenas `peticoes`. O runtime principal de edicao e salvamento segue pela trilha normalizada.</div>
    </div>
    <form method="get" action="{{ route('pecas.index') }}">
        <div class="form-grid">
            <div class="form-group">
                <label>Modelo</label>
                <select name="modelo_id">
                    <option value="">Todos</option>
                    @foreach($modelos as $modelo)
                        <option value="{{ $modelo->id }}" @if((string) $selectedModelo === (string) $modelo->id) selected @endif>{{ $modelo->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Pesquisa</label>
                <input name="search" value="{{ $search }}" placeholder="Cliente, nome ou codigo">
            </div>
        </div>
        <div class="actions" style="margin-top:16px;">
            <button type="submit">Filtrar</button>
            <a class="button secondary link" href="{{ route('pecas.index') }}">Limpar</a>
        </div>
    </form>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Origem</th>
                <th>Peticao</th>
                <th>Cliente / arquivo</th>
                <th>Codigo</th>
                <th>Data</th>
                <th>Usuario</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($pecas as $peca)
                <tr>
                    <td>{{ $peca->id }}</td>
                    <td>
                        <span class="pecas-origin-badge {{ $peca->origin_label === 'Avulsa' ? 'is-avulsa' : 'is-modelo' }}">
                            {{ $peca->origin_label }}
                        </span>
                    </td>
                    <td>{{ $peca->display_title }}</td>
                    <td>{{ $peca->display_reference }}</td>
                    <td>{{ $peca->codigo_externo ?: '-' }}</td>
                    <td>{{ optional($peca->gerado_em)->format('d/m/Y H:i') ?: optional($peca->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($peca->user)->login_usu ?: '-' }}</td>
                    <td>
                        <a href="{{ route('peticoes.saved.edit', $peca) }}">Abrir historico</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhuma peca encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $pecas->links('vendor.pagination.default') }}
</div>
@endsection
