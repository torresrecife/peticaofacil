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
            $editorRoute = route('peticoes.normalized.editor.create', $normalizedModel);
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
                                @if($campo->input_behavior)
                                    data-input-behavior="{{ $campo->input_behavior }}"
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
                                @endif
                                @if($campo->input_behavior)
                                    data-input-behavior="{{ $campo->input_behavior }}"
                                @endif
                                @if(in_array($campo->input_behavior, ['date', 'cpf', 'cnpj', 'cpf_cnpj', 'cep', 'integer', 'fone']))
                                    inputmode="numeric"
                                @elseif($campo->input_behavior === 'decimal')
                                    inputmode="decimal"
                                @endif
                            >
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
            <form method="post" action="{{ $editorRoute }}" style="margin-top:12px;">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ $preview['suggested_filename'] }}">
                <input type="hidden" name="codigo_processo" value="{{ $codigoProcesso ?? '' }}">
                <textarea name="content" style="display:none;">{{ $preview['html'] }}</textarea>
                <button type="submit" class="button secondary">Abrir editor</button>
            </form>
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

        var behavior = (field.getAttribute('data-input-behavior') || '').toLowerCase();
        if (behavior !== 'date') {
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

        if (raw.indexOf('fc_newstring(this)') !== -1) {
            fc_newstring(field);
        }
    }

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function formatCpf(value) {
        var digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 3) return digits;
        if (digits.length <= 6) return digits.slice(0, 3) + '.' + digits.slice(3);
        if (digits.length <= 9) return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6);
        return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6, 9) + '-' + digits.slice(9);
    }

    function formatCnpj(value) {
        var digits = onlyDigits(value).slice(0, 14);
        if (digits.length <= 2) return digits;
        if (digits.length <= 5) return digits.slice(0, 2) + '.' + digits.slice(2);
        if (digits.length <= 8) return digits.slice(0, 2) + '.' + digits.slice(2, 5) + '.' + digits.slice(5);
        if (digits.length <= 12) return digits.slice(0, 2) + '.' + digits.slice(2, 5) + '.' + digits.slice(5, 8) + '/' + digits.slice(8);
        return digits.slice(0, 2) + '.' + digits.slice(2, 5) + '.' + digits.slice(5, 8) + '/' + digits.slice(8, 12) + '-' + digits.slice(12);
    }

    function formatCep(value) {
        var digits = onlyDigits(value).slice(0, 8);
        if (digits.length <= 5) return digits;
        return digits.slice(0, 5) + '-' + digits.slice(5);
    }

    function formatPhone(value) {
        var digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 2) return digits;
        if (digits.length <= 6) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
        if (digits.length <= 10) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 6) + '-' + digits.slice(6);
        return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
    }

    function formatInteger(value) {
        return onlyDigits(value);
    }

    function formatDate(value) {
        var raw = String(value || '').trim();
        if (!raw || /[a-záàâãéêíóôõúç]/i.test(raw)) {
            return raw;
        }

        var iso = raw.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        if (iso) {
            return pad(parseInt(iso[3], 10)) + '/' + pad(parseInt(iso[2], 10)) + '/' + iso[1];
        }

        var digits = onlyDigits(raw).slice(0, 8);
        if (digits.length <= 2) return digits;
        if (digits.length <= 4) return digits.slice(0, 2) + '/' + digits.slice(2);
        return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
    }

    function formatDecimal(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return '';
        }

        raw = raw.replace(/[^\d,.-]/g, '');
        raw = raw.replace(/\.(?=.*\.)/g, '');
        raw = raw.replace(/,(?=.*,)/g, '');

        var normalized = raw;
        if (normalized.indexOf(',') !== -1 && normalized.indexOf('.') !== -1) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else if (normalized.indexOf(',') !== -1) {
            normalized = normalized.replace(',', '.');
        }

        var number = parseFloat(normalized);
        if (isNaN(number)) {
            return raw;
        }

        return number.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatCurrencyInput(value) {
        var digits = onlyDigits(value);
        if (!digits) {
            return '';
        }

        digits = digits.replace(/^0+(?=\d)/, '');
        var integerPart = digits.length > 2 ? digits.slice(0, -2) : '0';
        var decimalPart = digits.length > 1 ? digits.slice(-2) : '0' + digits;
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return integerPart + ',' + decimalPart;
    }

    function integerGroupToWords(value) {
        var units = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
        var teens = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        var tens = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        var hundreds = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        value = parseInt(value, 10) || 0;
        if (value === 100) return 'cem';

        var parts = [];
        var hundred = Math.floor(value / 100);
        var remainder = value % 100;
        if (hundred) parts.push(hundreds[hundred]);
        if (remainder >= 10 && remainder <= 19) {
            parts.push(teens[remainder - 10]);
        } else {
            var ten = Math.floor(remainder / 10);
            var unit = remainder % 10;
            if (ten) parts.push(tens[ten]);
            if (unit) parts.push(units[unit]);
        }
        return parts.join(' e ');
    }

    function integerToWords(value) {
        value = Math.floor(Math.abs(Number(value) || 0));
        if (value === 0) return 'zero';

        var scales = [
            ['', ''],
            ['mil', 'mil'],
            ['milhão', 'milhões'],
            ['bilhão', 'bilhões'],
            ['trilhão', 'trilhões']
        ];
        var groups = [];
        var scale = 0;
        while (value > 0 && scale < scales.length) {
            var group = value % 1000;
            if (group) {
                var words = integerGroupToWords(group);
                if (scale === 1 && group === 1) {
                    words = 'mil';
                } else if (scale > 0) {
                    words += ' ' + scales[scale][group === 1 ? 0 : 1];
                }
                groups.unshift({ value: group, words: words });
            }
            value = Math.floor(value / 1000);
            scale++;
        }

        var result = groups.length ? groups[0].words : 'zero';
        for (var index = 1; index < groups.length; index++) {
            var connector = (groups[index].value < 100 || groups[index].value % 100 === 0) ? ' e ' : ' ';
            result += connector + groups[index].words;
        }
        return result;
    }

    function currencyToWords(value) {
        var digits = onlyDigits(value);
        if (!digits) return '';

        var centsValue = parseInt(digits, 10);
        if (isNaN(centsValue)) return '';
        var reais = Math.floor(centsValue / 100);
        var centavos = centsValue % 100;
        var parts = [];

        if (reais > 0) {
            var currencyName = reais === 1 ? ' real' : ((reais >= 1000000 && reais % 1000000 === 0) ? ' de reais' : ' reais');
            parts.push(integerToWords(reais) + currencyName);
        }
        if (centavos > 0) {
            parts.push(integerToWords(centavos) + (centavos === 1 ? ' centavo' : ' centavos'));
        }
        return parts.length ? parts.join(' e ') : 'zero reais';
    }

    function fc_newstring(field) {
        var amount = formatDecimal(String(field.value || '').replace(/\s*\([^)]*\)\s*$/, ''));
        if (!amount) {
            field.value = '';
            return;
        }
        field.value = amount + ' (' + currencyToWords(amount) + ')';
    }

    function applyFieldBehavior(field) {
        var behavior = (field.getAttribute('data-input-behavior') || '').toLowerCase();
        if (!behavior) {
            return;
        }

        if (behavior === 'cpf') {
            field.value = formatCpf(field.value);
            return;
        }
        if (behavior === 'cnpj') {
            field.value = formatCnpj(field.value);
            return;
        }
        if (behavior === 'cpf_cnpj') {
            var cpfCnpjDigits = onlyDigits(field.value).slice(0, 14);
            field.value = cpfCnpjDigits.length <= 11 ? formatCpf(cpfCnpjDigits) : formatCnpj(cpfCnpjDigits);
            return;
        }
        if (behavior === 'cep') {
            field.value = formatCep(field.value);
            return;
        }
        if (behavior === 'fone') {
            field.value = formatPhone(field.value);
            return;
        }
        if (behavior === 'integer') {
            field.value = formatInteger(field.value);
            return;
        }
        if (behavior === 'date') {
            field.value = formatDate(field.value);
            return;
        }
        if (behavior === 'decimal') {
            field.value = formatDecimal(field.value);
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
        applyFieldBehavior(field);
        field.addEventListener('focus', function () {
            executeSupportedEvents(field.getAttribute('data-event-focus'), field);
        });
        field.addEventListener('blur', function () {
            applyFieldBehavior(field);
            executeSupportedEvents(field.getAttribute('data-event-blur'), field);
        });
        field.addEventListener('input', function () {
            var behavior = (field.getAttribute('data-input-behavior') || '').toLowerCase();
            if (behavior === 'decimal') {
                field.value = formatCurrencyInput(field.value);
            } else if (['date', 'cpf', 'cnpj', 'cpf_cnpj', 'cep', 'fone', 'integer'].indexOf(behavior) !== -1) {
                applyFieldBehavior(field);
            }
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-input-behavior]'), function (field) {
        if (field.classList.contains('js-frontend-event-field')) {
            return;
        }

        applyFieldBehavior(field);
        field.addEventListener('blur', function () {
            applyFieldBehavior(field);
        });
        field.addEventListener('input', function () {
            var behavior = (field.getAttribute('data-input-behavior') || '').toLowerCase();
            if (behavior === 'decimal') {
                field.value = formatCurrencyInput(field.value);
            } else if (['date', 'cpf', 'cnpj', 'cpf_cnpj', 'cep', 'fone', 'integer'].indexOf(behavior) !== -1) {
                applyFieldBehavior(field);
            }
        });
    });
});
</script>
@endpush
