@extends('layouts.app')

@section('title', 'Modelos')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Modelos de peticao</h2>
    <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px; margin-left:auto;">
        <form method="post" action="{{ route('admin.modelos-normalizados.import') }}" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">@csrf<input type="file" name="pacote" accept=".json,application/json" required><button type="submit">Importar modelo</button></form>
        <a class="button link" href="{{ route('admin.modelos-normalizados.create') }}">Novo modelo</a>
    </div>
</div>

<div class="panel" style="margin-bottom:16px;">
    <form method="get" action="{{ route('admin.modelos-normalizados.index') }}">
        <div class="form-grid" style="grid-template-columns:minmax(0, 2fr) minmax(160px, 1fr) minmax(160px, 1fr) auto;">
            <div class="form-group">
                <label>Buscar modelo</label>
                <input
                    name="search"
                    value="{{ $search ?? '' }}"
                    list="admin-modelo-suggestions"
                    placeholder="Digite o nome, slug ou ID do modelo">
                <datalist id="admin-modelo-suggestions">
                    @foreach($suggestions as $suggestion)
                        <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>
                <div class="editor-note">Busca por nome, slug ou ID. O autocomplete sugere modelos normalizados cadastrados.</div>
            </div>
            <div class="form-group"><label>Area</label><select name="setor_id"><option value="0">Todas</option>@foreach($setores as $setor)<option value="{{ $setor->id_setor }}" {{ $setorId == $setor->id_setor ? 'selected' : '' }}>{{ $setor->nome_setor }}</option>@endforeach</select></div>
            <div class="form-group"><label>Cliente</label><select name="cliente_id"><option value="0">Todos</option>@foreach($clientes as $cliente)<option value="{{ $cliente->cliente_id }}" {{ $clienteId == $cliente->cliente_id ? 'selected' : '' }}>{{ $cliente->cliente_name }}</option>@endforeach</select></div>
            <div class="form-group" style="justify-content:flex-start; padding-top:26px;">
                <div class="actions">
                    <button type="submit">Buscar</button>
                    <a class="button secondary link" href="{{ route('admin.modelos-normalizados.index') }}">Limpar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="panel" style="padding:0;">
    <div class="panel-muted" style="margin:16px;">
        <strong>Modelos normalizados</strong>
        <div class="editor-note">A edicao principal agora parte de `peticao_modelos`.</div>
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
            @forelse($modelos as $modelo)
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
                    <td><div style="display:inline-flex; align-items:center; gap:12px;"><a href="{{ route('admin.modelos-normalizados.edit', $modelo) }}">Editar</a><a href="{{ route('admin.modelos-normalizados.export', $modelo) }}">Exportar</a></div></td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="panel-muted">Nenhum modelo normalizado encontrado para este filtro.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $modelos->links('vendor.pagination.default') }}
</div>

@endsection
