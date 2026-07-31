@extends('layouts.app')

@section('title', 'Montagem de peticao')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $modeloFonte->tipo_nome }}</h2>
    <a class="button secondary link" href="{{ route('peticoes.index') }}">Voltar</a>
</div>

@push('head')
<style>
    .lookup-box {
        display: grid;
        gap: 10px;
        max-width: 560px;
        align-items: start;
    }
    .lookup-inline {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: start;
    }
    .lookup-inline .form-group {
        margin: 0;
    }
    .lookup-inline .actions {
        justify-content: flex-start;
        padding-top: 24px;
    }
    .lookup-error {
        background: #fdecea;
        color: #8a1f17;
        border: 1px solid #f5c6cb;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 600;
    }
</style>
@endpush

<div class="stack">
    <div class="panel">
        <div class="section-title">
            <h3>Dados da montagem</h3>
            <div class="editor-note">Campos dinamicos do modelo, com retorno aplicado para `SELECT`.</div>
        </div>

        @if($lookupStatus)
            <div class="flash">{{ $lookupStatus }}</div>
        @endif

        @php
            $normalizedModel = $modeloFonte->source;
            $composeRoute = route('peticoes.normalized.compose', $normalizedModel);
            $normalizedStoreRoute = route('peticoes.normalized.saved.store', $normalizedModel);
            $legacyEditorRoute = route('peticoes.normalized.editor.create', $normalizedModel);
            $lookupKey = '';
            $lookupAvailable = (bool) ($lookupConnectionStatus['available'] ?? false);
            $lookupErrorMessage = $lookupConnectionStatus['message'] ?? null;
            if (!empty($lookupConfig)) {
                $lookupKey = $lookupConfig->lookup_key ?? $lookupConfig->chave_db ?? '';
            }
        @endphp

        <form method="post" action="{{ $composeRoute }}">
            @csrf
            @if(!empty($lookupConfig))
                <div class="panel-muted" style="margin-bottom:20px;">
                    <div class="lookup-box">
                        <div class="lookup-inline">
                            <div class="form-group">
                                <label>Pesquisa por processo</label>
                                <input
                                    name="codigo_processo"
                                    value="{{ $codigoProcesso ?? '' }}"
                                    placeholder="{{ $lookupKey ?: 'Codigo do processo' }}"
                                    @if(!$lookupAvailable) disabled @endif>
                                <div class="editor-note">Busca no SQL Server configurado para este modelo e preenche automaticamente os campos mapeados.</div>
                            </div>
                            <div class="actions">
                                <button type="submit" name="action_type" value="lookup" @if(!$lookupAvailable) disabled @endif>Buscar e preencher</button>
                            </div>
                        </div>
                        @if(!$lookupAvailable && $lookupErrorMessage)
                            <div class="lookup-error">{{ $lookupErrorMessage }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="form-grid">
                @foreach($modeloFonte->campos as $campo)
                    @if($campo->input_tipo === 'TITLE')
                        <div class="form-group full">
                            <div class="panel-muted"><strong>{{ $campo->input_title }}</strong></div>
                        </div>
                    @elseif($campo->input_tipo === 'HIDDEN')
                        <input type="hidden" name="campo_{{ $campo->id_input }}" value="{{ $values['campo_'.$campo->id_input] ?? '' }}">
                    @elseif($campo->input_tipo === 'SELECT')
                        @php($dependentConfig = $campo->dependent_fill_config)
                        <div class="form-group @if((int) $campo->input_cols >= 2) full @endif">
                            <label>{{ $campo->input_title }}</label>
                            <select
                                name="campo_{{ $campo->id_input }}"
                                @if($dependentConfig)
                                    class="js-dependent-select"
                                    data-target-field="{{ $dependentConfig['target_field_id'] }}"
                                    data-return-column="{{ $dependentConfig['return_column'] }}"
                                @endif>
                                <option value=""></option>
                                @foreach($campo->select_options as $option)
                                    <option
                                        value="{{ $option['value'] ?? $option['label'] }}"
                                        @foreach(($option['extras'] ?? []) as $extraKey => $extraValue)
                                            data-{{ str_replace('_', '-', $extraKey) }}="{{ $extraValue }}"
                                        @endforeach
                                        @if(($values['campo_'.$campo->id_input] ?? '') === ($option['value'] ?? $option['label'])) selected @endif>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="editor-note">
                                Token {{ $campo->placeholder }}.
                                @if($campo->hasAssociatedListSource())
                                    Select abastecido pela lista associada.
                                @else
                                    O retorno usa a segunda coluna cadastrada em cada opcao.
                                @endif
                            </div>
                        </div>
                    @elseif($campo->input_tipo === 'TEXTAREA')
                        <div class="form-group full">
                            <label>{{ $campo->input_title }}</label>
                            <textarea name="campo_{{ $campo->id_input }}">{{ $values['campo_'.$campo->id_input] ?? '' }}</textarea>
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @else
                        <div class="form-group @if((int) $campo->input_cols >= 2) full @endif">
                            <label>{{ $campo->input_title }}</label>
                            <input name="campo_{{ $campo->id_input }}" value="{{ $values['campo_'.$campo->id_input] ?? '' }}">
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            <div style="margin-top:20px;">
                <button type="submit" name="action_type" value="preview">Gerar preview</button>
            </div>
        </form>
    </div>

    @if($preview)
        <div class="panel">
            <div class="section-title">
                <h3>Preview da peticao</h3>
                <div class="editor-note">Nome sugerido: {{ $preview['suggested_filename'] }}</div>
            </div>
            <div class="panel-muted" style="background:#fff;">
                {!! $preview['html'] !!}
            </div>
            <form method="post" action="{{ $normalizedStoreRoute }}" style="margin-top:16px;">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ $preview['suggested_filename'] }}">
                <input type="hidden" name="codigo_processo" value="{{ $codigoProcesso ?? '' }}">
                <input type="hidden" name="resolved_fields" value="{{ e(json_encode($preview['resolved_fields'])) }}">
                <textarea name="content" style="display:none;">{{ $preview['html'] }}</textarea>
                <button type="submit">Abrir peticao normalizada</button>
            </form>
            @if($modeloFonte->legacy_tipo_id)
                <form method="post" action="{{ $legacyEditorRoute }}" style="margin-top:12px;">
                    @csrf
                    <input type="hidden" name="nome_cli" value="{{ $preview['suggested_filename'] }}">
                    <input type="hidden" name="codigo_processo" value="{{ $codigoProcesso ?? '' }}">
                    <textarea name="content" style="display:none;">{{ $preview['html'] }}</textarea>
                    <button type="submit" class="button secondary">Abrir editor legado</button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function applyDependentSelect(select) {
        var targetFieldId = select.getAttribute('data-target-field');
        var returnColumn = select.getAttribute('data-return-column');
        if (!targetFieldId || !returnColumn) {
            return;
        }

        var target = document.querySelector('[name=\"campo_' + targetFieldId + '\"]');
        if (!target) {
            return;
        }

        var selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            target.value = '';
            return;
        }

        var datasetKey = returnColumn.replace('_', '-');
        var returnValue = selectedOption.getAttribute('data-' + datasetKey) || '';
        target.value = returnValue;
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-dependent-select'), function (select) {
        applyDependentSelect(select);
        select.addEventListener('change', function () {
            applyDependentSelect(select);
        });
    });
});
</script>
@endpush
