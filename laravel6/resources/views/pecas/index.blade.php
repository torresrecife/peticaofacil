@extends('layouts.app')

@section('title', 'Pecas salvas')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Pecas salvas</h2>
</div>

<div class="panel" style="margin-bottom:20px;">
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
                <th>Modelo</th>
                <th>Cliente / arquivo</th>
                <th>Data</th>
                <th>Usuario</th>
                <th>Origem</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($pecas as $peca)
                <tr>
                    <td>{{ $peca->id }}</td>
                    <td>{{ optional($peca->modelo)->nome }}</td>
                    <td>{{ $peca->cliente_referencia }}</td>
                    <td>{{ optional($peca->gerado_em)->format('d/m/Y H:i') ?: optional($peca->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($peca->user)->login_usu ?: '-' }}</td>
                    <td><span class="editor-note">Normalizada</span></td>
                    <td>
                        <a href="{{ route('peticoes.saved.edit', $peca) }}">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhuma peca normalizada encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $pecas->links('vendor.pagination.default') }}
</div>
@endsection
