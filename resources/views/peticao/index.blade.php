@extends('layouts.app')

@section('title', 'Montagem de peticoes')

@push('head')
<style>
    .model-section {
        display: grid;
        gap: 16px;
    }
    .model-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    }
    .model-card {
        display: grid;
        gap: 14px;
        min-height: 180px;
        padding: 18px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .model-card:hover {
        border-color: #1f5f8b;
        box-shadow: 0 8px 18px rgba(16, 42, 67, .08);
        transform: translateY(-1px);
    }
    .model-card__icon {
        width: 52px;
        height: 52px;
        display: grid;
        place-items: center;
        border-radius: 6px;
        background: #eaf2f8;
        color: #1f5f8b;
        font-weight: bold;
        font-size: 18px;
    }
    .model-card__title {
        font-size: 15px;
        font-weight: bold;
        color: #102a43;
        line-height: 1.35;
        min-height: 40px;
    }
    .model-card__meta {
        display: grid;
        gap: 6px;
        font-size: 12px;
        color: #627d98;
    }
    .model-card__meta span {
        display: block;
    }
    .model-card__actions {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .model-card__toolbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .favorite-button {
        min-width: 0;
        padding: 6px 8px;
        font-size: 16px;
        line-height: 1;
        background: #fff;
        color: #627d98;
        border: 1px solid #d9e2ec;
    }
    .favorite-button.is-active {
        color: #b7791f;
        border-color: #f6ad55;
        background: #fffaf0;
    }
    .model-card__badge {
        font-size: 11px;
        color: #486581;
        background: #f0f4f8;
        border: 1px solid #d9e2ec;
        border-radius: 999px;
        padding: 5px 8px;
    }
    .model-card__link {
        font-size: 13px;
        font-weight: bold;
    }
    .model-empty {
        padding: 28px;
        border: 1px dashed #bcccdc;
        border-radius: 6px;
        background: #fff;
        color: #627d98;
    }
</style>
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Montagem de peticoes</h2>
</div>

<div class="model-section">
    <div class="panel">
        <form method="get" action="{{ route('peticoes.index') }}">
            <div class="form-grid" style="grid-template-columns:minmax(0, 1fr) auto;">
                <div class="form-group">
                    <label>Buscar modelo</label>
                    <input
                        name="search"
                        value="{{ $search ?? '' }}"
                        list="peticao-modelo-suggestions"
                        placeholder="Digite o nome ou ID da peticao">
                    <datalist id="peticao-modelo-suggestions">
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
                        <a class="button secondary link" href="{{ route('peticoes.index') }}">Limpar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="section-title" style="margin-bottom:10px;">
            <h3>Modelos normalizados</h3>
            <div class="editor-note">Selecione um modelo para iniciar a montagem. A trilha principal usa os modelos normalizados.</div>
        </div>

        @if($modelos->isEmpty())
            <div class="model-empty">Nenhum modelo normalizado encontrado para este filtro.</div>
        @else
            <div class="model-grid">
                @foreach($modelos as $modelo)
                    <div class="model-card">
                        <div class="model-card__toolbar">
                            <div class="model-card__icon">PJ</div>
                            @php($favoriteKey = 'normalized:' . $modelo->id)
                            <form method="post" action="{{ !empty($favoriteRows[$favoriteKey]) ? route('peticoes.normalized.favorite.destroy', $modelo) : route('peticoes.normalized.favorite.store', $modelo) }}">
                                @csrf
                                @if(!empty($favoriteRows[$favoriteKey]))
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="favorite-button @if(!empty($favoriteRows[$favoriteKey])) is-active @endif" title="Favorito">
                                    @if(!empty($favoriteRows[$favoriteKey])) ★ @else ☆ @endif
                                </button>
                            </form>
                        </div>
                        <div class="model-card__title">{{ $modelo->nome }}</div>
                        <div class="model-card__meta">
                            <span><strong>ID:</strong> {{ $modelo->legacy_tipo_id ?: $modelo->id }}</span>
                            <span><strong>Setor:</strong> {{ optional($modelo->setor)->nome_setor ?: 'Nao informado' }}</span>
                            <span><strong>Cliente:</strong> {{ optional($modelo->cliente)->cliente_name ?: 'Todos do setor' }}</span>
                        </div>
                        <div class="model-card__actions">
                            <span class="model-card__badge">Normalizado</span>
                            <a class="model-card__link" href="{{ route('peticoes.normalized.show', $modelo) }}">Montar</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="pagination-wrap">
        {{ $modelos->links('vendor.pagination.default') }}
    </div>

</div>
@endsection
