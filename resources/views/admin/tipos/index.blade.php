@extends('layouts.app')

@section('title', 'Modelos')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Modelos de peticao</h2>
    <a class="button link" href="{{ route('admin.modelos-normalizados.create') }}">Novo modelo</a>
</div>

<div class="panel" style="margin-bottom:16px;">
    <form method="get" action="{{ route('admin.modelos-normalizados.index') }}">
        <div class="form-grid" style="grid-template-columns:minmax(0, 1fr) auto;">
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
            <div class="form-group" style="justify-content:end;">
                <label>&nbsp;</label>
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
                    <td><a href="{{ route('admin.modelos-normalizados.edit', $modelo) }}">Editar</a></td>
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
