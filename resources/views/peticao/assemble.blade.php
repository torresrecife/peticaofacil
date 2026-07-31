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
                            <textarea
                                name="campo_{{ $campo->id_input }}"
                                @if($campo->input_focu || $campo->input_load || $campo->input_blur)
                                    class="js-frontend-event-field"
                                    data-event-focus="{{ e($campo->input_focu) }}"
                                    data-event-load="{{ e($campo->input_load) }}"
                                    data-event-blur="{{ e($campo->input_blur) }}"
                                @endif
                            >{{ $values['campo_'.$campo->id_input] ?? '' }}</textarea>
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @else
                        <div class="form-group @if((int) $campo->input_cols >= 2) full @endif">
                            <label>{{ $campo->input_title }}</label>
                            <input
                                name="campo_{{ $campo->id_input }}"
                                value="{{ $values['campo_'.$campo->id_input] ?? '' }}"
                                @if($campo->input_focu || $campo->input_load || $campo->input_blur)
                                    class="js-frontend-event-field"
                                    data-event-focus="{{ e($campo->input_focu) }}"
                                    data-event-load="{{ e($campo->input_load) }}"
                                    data-event-blur="{{ e($campo->input_blur) }}"
                                @endif>
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
    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function parseDateValue(rawValue) {
        var value = (rawValue || '').trim();
        if (!value) {
            return new Date();
        }

        var slash = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (slash) {
            return new Date(parseInt(slash[3], 10), parseInt(slash[2], 10) - 1, parseInt(slash[1], 10));
        }

        var iso = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        if (iso) {
            return new Date(parseInt(iso[1], 10), parseInt(iso[2], 10) - 1, parseInt(iso[3], 10));
        }

        var fallback = new Date(value);
        return isNaN(fallback.getTime()) ? new Date() : fallback;
    }

    function formatDateExtenso(date) {
        var months = ['janeiro', 'fevereiro', 'marco', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        return date.getDate() + ' de ' + months[date.getMonth()] + ' de ' + date.getFullYear();
    }

    function formatWeekday(date) {
        var days = ['domingo', 'segunda-feira', 'terca-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sabado'];
        return days[date.getDay()];
    }

    function data_atual(field) {
        var date = new Date();
        field.value = pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + date.getFullYear();
    }

    function data_extenso_out(field) {
        var date = parseDateValue(field.value);
        field.value = formatDateExtenso(date);
    }

    function dia_semana(field) {
        var date = parseDateValue(field.value);
        field.value = formatWeekday(date);
    }

    function executeSupportedEvents(script, field) {
        var raw = (script || '').trim();
        if (!raw) {
            return;
        }

        if (raw.indexOf('data_atual(this)') !== -1) {
            data_atual(field);
        }
        if (raw.indexOf('data_extenso_out(this)') !== -1) {
            data_extenso_out(field);
        }
        if (raw.indexOf('dia_semana(this)') !== -1) {
            dia_semana(field);
        }
    }

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

    Array.prototype.forEach.call(document.querySelectorAll('.js-frontend-event-field'), function (field) {
        executeSupportedEvents(field.getAttribute('data-event-load'), field);
        field.addEventListener('focus', function () {
            executeSupportedEvents(field.getAttribute('data-event-focus'), field);
        });
        field.addEventListener('blur', function () {
            executeSupportedEvents(field.getAttribute('data-event-blur'), field);
        });
    });
});
</script>
@endpush
