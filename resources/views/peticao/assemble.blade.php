@extends('layouts.app')

@section('title', 'Montagem de peticao')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $modeloFonte->tipo_nome }}</h2>
    <a class="button secondary link" href="{{ route('peticoes.index') }}">Voltar</a>
</div>

@push('head')
<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
<script src="{{ asset('ckfinder/ckfinder.js') }}"></script>
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
    .required-mark {
        color: #b42318;
        font-weight: 700;
    }
    .required-warning {
        background: #fffaeb;
        color: #7a4b00;
        border: 1px solid #f5d48a;
        border-radius: 6px;
        padding: 12px 14px;
        font-size: 14px;
        margin-bottom: 16px;
    }
    .required-warning .actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    .legacy-field-wrap.is-missing-required > label:first-of-type {
        color: #b42318;
    }
    .legacy-field-wrap.is-missing-required input:not([type="radio"]),
    .legacy-field-wrap.is-missing-required select,
    .legacy-field-wrap.is-missing-required .rich-text-card {
        border-color: #b42318;
    }
    /* Tabelas da peticao ocupam uma linha propria no preview. */
    .preview-content table {
        display: table;
        clear: both;
        float: none !important;
    }
    .preview-content table[style*="float: right"],
    .preview-content table[style*="float:right"] {
        float: none !important;
        margin-left: auto !important;
        margin-right: 0 !important;
    }
    .preview-content table[style*="float: left"],
    .preview-content table[style*="float:left"] {
        float: none !important;
        margin-left: 0 !important;
        margin-right: auto !important;
    }
    .preview-content table[align="right"],
    .preview-content table[style*="text-align: right"],
    .preview-content table[style*="text-align:right"] {
        margin-left: auto !important;
        margin-right: 0 !important;
    }
    .preview-content table[align="center"],
    .preview-content table[style*="text-align: center"],
    .preview-content table[style*="text-align:center"] {
        margin-left: auto !important;
        margin-right: auto !important;
    }
    .rich-text-source {
        position: absolute !important;
        left: -10000px !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
    }
    .rich-text-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        background: #f8fafc;
    }
    .rich-text-summary {
        min-width: 0;
        color: #52667a;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rich-editor-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(16, 42, 67, .62);
    }
    .rich-editor-modal.is-open { display: flex; }
    .rich-editor-dialog {
        width: min(980px, 100%);
        max-height: calc(100vh - 48px);
        overflow: auto;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
    }
    .rich-editor-dialog__header,
    .rich-editor-dialog__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #d9e2ec;
    }
    .rich-editor-dialog__footer {
        justify-content: flex-end;
        border-top: 1px solid #d9e2ec;
        border-bottom: 0;
    }
    .rich-editor-dialog__body { padding: 16px 18px; }
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
            $hasNeoMappedFields = $modeloFonte->campos->contains(function ($campo) {
                return trim((string) $campo->input_val) !== '';
            });
            if (!empty($lookupConfig)) {
                $lookupKey = $lookupConfig->lookup_key ?? $lookupConfig->chave_db ?? '';
            }
            $requiredFields = $requiredFields ?? app(\App\Services\PeticaoRequiredFieldsService::class);
            $missingRequired = $missingRequired ?? [];
            $missingRequiredIds = array_map('strval', array_column($missingRequired, 'id'));
            $conditionalRules = $modeloFonte->campos->map(function ($campo) {
                $config = $campo->conditional_config;
                if (!$config || empty($config['value']) || empty($config['target_ids'])) {
                    return null;
                }

                return [
                    'source_id' => (int) $campo->id_input,
                    'value' => (string) $config['value'],
                    'target_ids' => array_values(array_map('intval', (array) $config['target_ids'])),
                    'required' => !empty($config['required']),
                ];
            })->filter()->values()->all();
            $predefinedTexts = collect((array) (($normalizedModel->metadata ?? [])['text_templates'] ?? []))
                ->filter(function ($template) {
                    return !empty($template['id']) && (!array_key_exists('active', $template) || $template['active']);
                })
                ->map(function ($template) {
                    return [
                        'id' => (string) $template['id'],
                        'title' => (string) ($template['title'] ?? 'Texto predefinido'),
                        'content' => (string) ($template['content'] ?? ''),
                    ];
                })->values()->all();
        @endphp

        <form method="post" action="{{ $composeRoute }}" data-assemble-form>
            @csrf
            @if(!empty($lookupConfig) || $hasNeoMappedFields)
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
                                <button type="submit" name="action_type" value="lookup" formnovalidate @if(!$lookupAvailable) disabled @endif>Buscar e preencher</button>
                            </div>
                        </div>
                        @if(!$lookupAvailable)
                            <div class="lookup-error">{{ $lookupErrorMessage ?: 'Configure um servidor NEO no modelo para habilitar a busca automática.' }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="form-grid">
                @foreach($modeloFonte->campos as $campo)
                    @php($fieldType = $campo->input_tipo)
                    @php($fieldRequired = $requiredFields->isRequired($campo))
                    @if($fieldType === 'TITLE')
                        <div class="form-group full legacy-field-wrap dis_campo{{ $campo->id_input }} {{ $campo->add_class }}" data-field-id="{{ $campo->id_input }}" data-field-label="{{ $campo->input_title }}" @if($fieldRequired) data-required="1" @endif @if(!$campo->visivel) style="display:none;" @endif>
                            <div class="panel-muted"><strong>{{ $campo->input_title }}</strong></div>
                        </div>
                    @elseif($fieldType === 'HIDDEN')
                        <input type="hidden" name="campo_{{ $campo->id_input }}" value="{{ $values['campo_'.$campo->id_input] ?? '' }}">
                    @elseif($fieldType === 'SELECT')
                        @php($dependentConfig = $campo->dependent_fill_config)
                        <div class="form-group legacy-field-wrap dis_campo{{ $campo->id_input }} {{ $campo->add_class }} @if((int) $campo->input_cols >= 2) full @endif" data-field-id="{{ $campo->id_input }}" data-field-label="{{ $campo->input_title }}" @if($fieldRequired) data-required="1" @endif @if(!$campo->visivel) style="display:none;" @endif>
                            <label>{{ $campo->input_title }}@if($fieldRequired) <span class="required-mark" title="Campo obrigatório">*</span>@endif</label>
                            <select
                                name="campo_{{ $campo->id_input }}"
                                id="campo{{ $campo->id_input }}"
                                class="@if($dependentConfig) js-dependent-select @endif @if($campo->input_focu || $campo->input_load || $campo->input_blur) js-frontend-event-field @endif"
                                @if($dependentConfig)
                                    data-target-field="{{ $dependentConfig['target_field_id'] }}"
                                    data-return-column="{{ $dependentConfig['return_column'] }}"
                                @endif
                                @if($campo->input_focu || $campo->input_load || $campo->input_blur)
                                    data-event-focus="{{ e($campo->input_focu) }}"
                                    data-event-load="{{ e($campo->input_load) }}"
                                    data-event-blur="{{ e($campo->input_blur) }}"
                                @endif
                            >
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
                    @elseif($fieldType === 'RADIO2')
                        <div class="form-group legacy-field-wrap dis_campo{{ $campo->id_input }} {{ $campo->add_class }} @if((int) $campo->input_cols >= 2) full @endif" data-field-id="{{ $campo->id_input }}" data-field-label="{{ $campo->input_title }}" @if($fieldRequired) data-required="1" @endif @if(!$campo->visivel) style="display:none;" @endif>
                            <label>{{ $campo->input_title }}@if($fieldRequired) <span class="required-mark" title="Campo obrigatório">*</span>@endif</label>
                            @foreach($campo->select_options as $option)
                                <label style="display:flex;gap:8px;align-items:center;font-weight:normal;">
                                    <input type="radio" id="campo{{ $campo->id_input }}_{{ $loop->index }}" name="campo_{{ $campo->id_input }}" value="{{ $option['value'] ?? $option['label'] }}"
                                        @if($campo->input_focu || $campo->input_load || $campo->input_blur)
                                            class="js-frontend-event-field"
                                            data-event-focus="{{ e($campo->input_focu) }}"
                                            data-event-load="{{ e($campo->input_load) }}"
                                            data-event-blur="{{ e($campo->input_blur) }}"
                                        @endif
                                        @if(($values['campo_'.$campo->id_input] ?? '') === ($option['value'] ?? $option['label'])) checked @endif>
                                    {{ $option['label'] }}
                                </label>
                            @endforeach
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @elseif($fieldType === 'TEXTAREA')
                        @php($textareaKey = 'campo_'.$campo->id_input)
                        @php($textareaValue = array_key_exists($textareaKey, $values) ? ($values[$textareaKey] ?? '') : ($campo->texto_padrao ?? ''))
                        <div class="form-group full legacy-field-wrap dis_campo{{ $campo->id_input }} {{ $campo->add_class }}" data-field-id="{{ $campo->id_input }}" data-field-label="{{ $campo->input_title }}" @if($fieldRequired) data-required="1" @endif @if(!$campo->visivel) style="display:none;" @endif>
                            <label>{{ $campo->input_title }}@if($fieldRequired) <span class="required-mark" title="Campo obrigatório">*</span>@endif</label>
                            <textarea
                                id="campo{{ $campo->id_input }}"
                                name="campo_{{ $campo->id_input }}"
                                data-default-content="{{ $campo->texto_padrao ?? '' }}"
                                class="rich-text-source js-rich-text-source @if($campo->input_focu || $campo->input_load || $campo->input_blur) js-frontend-event-field @endif"
                                @if($campo->input_focu || $campo->input_load || $campo->input_blur)
                                    data-event-focus="{{ e($campo->input_focu) }}"
                                    data-event-load="{{ e($campo->input_load) }}"
                                    data-event-blur="{{ e($campo->input_blur) }}"
                                @endif
                                @if($campo->input_behavior)
                                    data-input-behavior="{{ $campo->input_behavior }}"
                                @endif
                            >{{ $textareaValue }}</textarea>
                            <div class="rich-text-card">
                                <div class="rich-text-summary js-rich-text-summary" data-empty-text="Nenhum conteúdo informado.">{{ \Illuminate\Support\Str::limit(trim(strip_tags($textareaValue)), 150) ?: 'Nenhum conteúdo informado.' }}</div>
                                <button type="button" class="secondary js-open-rich-editor" data-source-id="campo{{ $campo->id_input }}" data-editor-title="{{ $campo->input_title }}">Editar conteúdo</button>
                            </div>
                            <div class="editor-note">Token {{ $campo->placeholder }}. O editor abre somente quando necessário.</div>
                        </div>
                    @else
                        <div class="form-group legacy-field-wrap dis_campo{{ $campo->id_input }} {{ $campo->add_class }} @if((int) $campo->input_cols >= 2) full @endif" data-field-id="{{ $campo->id_input }}" data-field-label="{{ $campo->input_title }}" @if($fieldRequired) data-required="1" @endif @if(!$campo->visivel) style="display:none;" @endif>
                            <label>{{ $campo->input_title }}@if($fieldRequired) <span class="required-mark" title="Campo obrigatório">*</span>@endif</label>
                            <input
                                id="campo{{ $campo->id_input }}"
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
                                @if(in_array($campo->input_behavior, ['date', 'cpf', 'cnpj', 'cpf_cnpj', 'cep', 'integer', 'fone', 'processo']))
                                    inputmode="numeric"
                                @elseif($campo->input_behavior === 'decimal')
                                    inputmode="decimal"
                                @endif
                            >
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @endif
                    @if((int) $campo->input_rols === 1)
                        <div class="full legacy-row-break" aria-hidden="true"></div>
                    @endif
                @endforeach
            </div>

            <div id="rich-editor-modal" class="rich-editor-modal" aria-hidden="true">
                <div class="rich-editor-dialog" role="dialog" aria-modal="true" aria-labelledby="rich-editor-title">
                    <div class="rich-editor-dialog__header">
                        <strong id="rich-editor-title">Editar conteúdo</strong>
                        <button type="button" class="secondary" id="rich-editor-close" aria-label="Fechar editor">Fechar</button>
                    </div>
                    <div class="rich-editor-dialog__body">
                        <div class="form-group" style="margin-bottom:14px;">
                            <label for="rich-editor-template">Carregar texto predefinido</label>
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;">
                                <select id="rich-editor-template">
                                    <option value="">Selecione um texto</option>
                                    <option value="__field_default">Texto padrão deste campo</option>
                                    @foreach($predefinedTexts as $template)
                                        <option value="{{ $template['id'] }}">{{ $template['title'] }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="secondary" id="rich-editor-load-template">Carregar</button>
                            </div>
                            <div class="editor-note">O texto escolhido é carregado no editor e ainda pode ser alterado antes de aplicar.</div>
                        </div>
                        <textarea id="rich-editor-workspace"></textarea>
                    </div>
                    <div class="rich-editor-dialog__footer">
                        <button type="button" class="secondary" id="rich-editor-cancel">Cancelar</button>
                        <button type="button" id="rich-editor-apply">Aplicar conteúdo</button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="hidden_fields" id="hidden-fields" value="">
            <div class="required-warning" id="required-check" role="alert" style="display:none; margin-top:20px;">
                <strong>Há campos obrigatórios em branco:</strong> <span id="required-check-list"></span>
                <div class="actions">
                    <button type="button" class="secondary" id="required-check-fill">Preencher agora</button>
                    <button type="button" id="required-check-continue">Gerar mesmo assim</button>
                </div>
            </div>
            <div style="margin-top:20px;">
                <button type="submit" name="action_type" value="preview">Gerar preview</button>
                <span class="editor-note" style="margin-left:12px;"><span class="required-mark">*</span> Campo obrigatório</span>
            </div>
        </form>
    </div>

    @if($preview)
        <div class="panel">
            <div class="section-title">
                <h3>Preview da peticao</h3>
                <div class="editor-note">Nome sugerido: {{ $preview['suggested_filename'] }}</div>
            </div>
            @if(!empty($missingRequired))
                <div class="required-warning" role="alert" data-missing-required="{{ implode(',', $missingRequiredIds) }}">
                    <strong>Prévia gerada com {{ count($missingRequired) }} {{ count($missingRequired) === 1 ? 'campo obrigatório' : 'campos obrigatórios' }} em branco:</strong>
                    {{ implode(', ', array_column($missingRequired, 'label')) }}.
                    Confira antes de salvar ou abrir o editor.
                </div>
            @endif
            <div class="panel-muted preview-content" style="background:#fff;">
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
    var richModal = document.getElementById('rich-editor-modal');
    var richWorkspace = document.getElementById('rich-editor-workspace');
    var richTitle = document.getElementById('rich-editor-title');
    var richEditor = null;
    var activeRichSource = null;
    var predefinedTexts = @json(collect($predefinedTexts)->keyBy('id')->all());
    var richTemplateSelect = document.getElementById('rich-editor-template');

    function summarizeRichText(html) {
        var container = document.createElement('div');
        container.innerHTML = html || '';
        var text = String(container.textContent || container.innerText || '').replace(/\s+/g, ' ').trim();
        return text ? (text.length > 150 ? text.slice(0, 147) + '...' : text) : 'Nenhum conteúdo informado.';
    }

    function ensureRichEditor() {
        if (richEditor || !richWorkspace || typeof window.CKEDITOR === 'undefined') return richEditor;
        richEditor = window.CKEDITOR.replace(richWorkspace.id, {
            height: 360,
            allowedContent: true
        });
        if (window.CKFinder) {
            window.CKFinder.setupCKEditor(richEditor, @json(asset('ckfinder/')));
        }
        return richEditor;
    }

    function setRichWorkspaceData(value) {
        var editor = ensureRichEditor();
        if (editor) {
            editor.setData(value || '');
        } else if (richWorkspace) {
            richWorkspace.value = value || '';
        }
    }

    function getRichWorkspaceData() {
        return richEditor ? richEditor.getData() : (richWorkspace ? richWorkspace.value : '');
    }

    function closeRichEditor() {
        if (!richModal) return;
        richModal.classList.remove('is-open');
        richModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        activeRichSource = null;
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-open-rich-editor'), function (button) {
        button.addEventListener('click', function () {
            activeRichSource = document.getElementById(button.getAttribute('data-source-id'));
            if (!activeRichSource || !richModal) return;
            richTitle.textContent = button.getAttribute('data-editor-title') || 'Editar conteúdo';
            richModal.classList.add('is-open');
            richModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            if (richTemplateSelect) richTemplateSelect.value = '';
            setRichWorkspaceData(activeRichSource.value);
        });
    });

    ['rich-editor-close', 'rich-editor-cancel'].forEach(function (id) {
        var button = document.getElementById(id);
        if (button) button.addEventListener('click', closeRichEditor);
    });

    var applyRichButton = document.getElementById('rich-editor-apply');
    if (applyRichButton) {
        applyRichButton.addEventListener('click', function () {
            if (!activeRichSource) return;
            activeRichSource.value = getRichWorkspaceData();
            var summary = activeRichSource.parentNode.querySelector('.js-rich-text-summary');
            if (summary) summary.textContent = summarizeRichText(activeRichSource.value);
            activeRichSource.dispatchEvent(new Event('input', { bubbles: true }));
            activeRichSource.dispatchEvent(new Event('change', { bubbles: true }));
            closeRichEditor();
        });
    }

    var loadTemplateButton = document.getElementById('rich-editor-load-template');
    if (loadTemplateButton) {
        loadTemplateButton.addEventListener('click', function () {
            if (!activeRichSource || !richTemplateSelect || !richTemplateSelect.value) return;
            var selected = richTemplateSelect.value;
            if (selected === '__field_default') {
                setRichWorkspaceData(activeRichSource.getAttribute('data-default-content') || '');
                return;
            }
            if (predefinedTexts[selected]) {
                setRichWorkspaceData(predefinedTexts[selected].content || '');
            }
        });
    }

    if (richModal) {
        richModal.addEventListener('click', function (event) {
            if (event.target === richModal) closeRichEditor();
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && richModal && richModal.classList.contains('is-open')) closeRichEditor();
    });

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
        var raw = String(field.value || '').replace(/\s*\([^)]*\)\s*$/, '');
        field.value = raw ? raw + ' (' + formatWeekday(date) + ')' : '';
    }

    function executeSupportedEvents(script, field) {
        var raw = (script || '').trim();
        // Event handlers are stored in HTML data attributes. Laravel escapes
        // their quotes, so normalize entities before applying legacy parsers.
        raw = raw.split('&quot;').join(String.fromCharCode(34));
        raw = raw.split('&#039;').join(String.fromCharCode(39)).split('&#39;').join(String.fromCharCode(39));
        raw = raw.split('&amp;').join('&');
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
        if (raw.indexOf('diasemana(this)') !== -1) {
            dia_semana(field);
        }

        if (raw.indexOf('fc_newstring(this)') !== -1) {
            fc_newstring(field);
        }
        if (raw.indexOf('estado_ext(this)') !== -1) {
            estado_ext(field);
        }

        var repeatMatch = raw.match(/repeteValor\(\s*(\d+)\s*,\s*(\d+)\s*\)/i);
        if (repeatMatch) repeatFieldValue(repeatMatch[1], repeatMatch[2]);
        var addDateMatch = raw.match(/addDataCampo\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i);
        if (addDateMatch) addMonthsToField(addDateMatch[1], addDateMatch[2], addDateMatch[3]);
        var visibilityMatch = raw.match(/mcampo\(["']([^"']+)["']\)/i);
        if (visibilityMatch) applyConditionalFields(visibilityMatch[1]);
        var installmentsMatch = raw.match(/nparcelas\(["']([^"']+)["']\)/i);
        if (installmentsMatch) applyCountedFields(installmentsMatch[1], 2);
        // Legacy models use nbens("campo941_|_campo942...", "input").
        // Keep this parser tolerant of whitespace/quote variations introduced
        // when the event is stored and rendered through HTML attributes.
        var assetsMatch = raw.match(/nbens\s*\(\s*["']([^"']+)["']/i);
        if (assetsMatch) applyCountedFields(assetsMatch[1], 1);
        var numberWordsMatch = raw.match(/fc_numextenso\(\s*(\d+)\s*,\s*(\d+)\s*,\s*["']([^"']*)["']\s*\)/i);
        if (numberWordsMatch) fillNumberInWords(numberWordsMatch[1], numberWordsMatch[2], numberWordsMatch[3]);
        var currentDateMatch = raw.match(/data_extenso_cur\(["']campo(\d+)["']\s*,\s*\$\(["']#campo(\d+)["']\)\.val\(\)\s*\)/i);
        if (currentDateMatch) fillCurrentDateInWords(currentDateMatch[1], currentDateMatch[2]);
        var validationMatch = raw.match(/validate_peticao\(\s*(?:["']([^"']*)["'])?\s*\)/i);
        if (validationMatch) validateLegacyPetition(validationMatch[1] || '');
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

    function formatProcess(value) {
        var digits = onlyDigits(value).slice(0, 20);
        var parts = [7, 2, 4, 1, 2, 4];
        var separators = ['-', '.', '.', '.', '.'];
        var output = '';
        var offset = 0;
        for (var index = 0; index < parts.length && offset < digits.length; index++) {
            if (index > 0) output += separators[index - 1];
            output += digits.slice(offset, offset + parts[index]);
            offset += parts[index];
        }
        return output;
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

    function legacyField(fieldId) {
        return document.querySelector('[name="campo_' + fieldId + '"]:checked') || document.querySelector('[name="campo_' + fieldId + '"]');
    }

    function legacyFieldValue(fieldId) {
        var field = legacyField(fieldId);
        return field ? String(field.value || '') : '';
    }

    function setLegacyFieldVisibility(fieldId, visible, required) {
        var wrapper = document.querySelector('.dis_campo' + fieldId);
        if (!wrapper) return;
        wrapper.style.display = visible ? '' : 'none';
        Array.prototype.forEach.call(wrapper.querySelectorAll('input, select, textarea'), function (control) {
            control.required = !!(visible && required);
            if (!visible && control.type !== 'radio' && control.type !== 'checkbox') control.value = '';
            if (!visible && (control.type === 'radio' || control.type === 'checkbox')) control.checked = false;
        });
    }

    function legacyFieldIds(spec) {
        return String(spec || '').split('_|_').map(function (item) {
            return String(item).replace(/\D/g, '');
        }).filter(Boolean);
    }

    function applyConditionalFields(spec) {
        var ids = legacyFieldIds(spec);
        if (ids.length < 2) return;
        var normalized = legacyFieldValue(ids[0]).toUpperCase();
        if (normalized.normalize) normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        normalized = normalized.replace(/[^A-Z]/g, '');
        ids.slice(1).forEach(function (id) {
            setLegacyFieldVisibility(id, normalized !== 'NAO', normalized === 'SIM');
        });
    }

    function normalizeConditionalValue(value) {
        var normalized = String(value || '').trim().toUpperCase();
        if (normalized.normalize) normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return normalized.replace(/\s+/g, ' ');
    }

    function applyStructuredConditionalRule(rule) {
        var matches = normalizeConditionalValue(legacyFieldValue(rule.source_id)) === normalizeConditionalValue(rule.value);
        (rule.target_ids || []).forEach(function (id) {
            setLegacyFieldVisibility(id, matches, matches && !!rule.required);
        });
    }

    function applyCountedFields(spec, multiplier) {
        var ids = legacyFieldIds(spec);
        if (ids.length < 2) return;
        var count = Math.max(0, parseInt(legacyFieldValue(ids[0]), 10) || 0) * multiplier;
        ids.slice(1).forEach(function (id, index) {
            setLegacyFieldVisibility(id, index < count, index < count);
        });
    }

    function repeatFieldValue(sourceId, targetId) {
        var target = legacyField(targetId);
        if (target && target.offsetParent !== null) target.value = legacyFieldValue(sourceId);
    }

    function addMonthsToField(sourceId, targetId, months) {
        var target = legacyField(targetId);
        if (!target || target.offsetParent === null) return;
        var date = parseDateValue(legacyFieldValue(sourceId));
        date.setMonth(date.getMonth() + (parseInt(months, 10) || 1));
        target.value = pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + date.getFullYear();
    }

    function fillNumberInWords(sourceId, targetId, noun) {
        var number = parseInt(onlyDigits(legacyFieldValue(sourceId)), 10);
        var target = legacyField(targetId);
        if (!target || isNaN(number)) return;
        var words = integerToWords(number);
        target.value = number + ' (' + words + ') ' + noun + (number === 1 ? '' : 's');
    }

    function estado_ext(field) {
        var states = {AC:'ACRE',AL:'ALAGOAS',AP:'AMAPÁ',AM:'AMAZONAS',BA:'BAHIA',CE:'CEARÁ',DF:'DISTRITO FEDERAL',ES:'ESPÍRITO SANTO',GO:'GOIÁS',MA:'MARANHÃO',MT:'MATO GROSSO',MS:'MATO GROSSO DO SUL',MG:'MINAS GERAIS',PA:'PARÁ',PB:'PARAÍBA',PR:'PARANÁ',PE:'PERNAMBUCO',PI:'PIAUÍ',RJ:'RIO DE JANEIRO',RN:'RIO GRANDE DO NORTE',RS:'RIO GRANDE DO SUL',RO:'RONDÔNIA',RR:'RORAIMA',SC:'SANTA CATARINA',SP:'SÃO PAULO',SE:'SERGIPE',TO:'TOCANTINS'};
        var value = String(field.value || '').trim().toUpperCase();
        if (states[value]) field.value = states[value];
    }

    function fillCurrentDateInWords(targetId, cityFieldId) {
        var target = legacyField(targetId);
        if (!target) return;
        var city = legacyFieldValue(cityFieldId).trim();
        target.value = (city ? city + ', ' : '') + formatDateExtenso(new Date());
    }

    function validateLegacyPetition(exceptionMode) {
        var blocked = false;
        Array.prototype.forEach.call(document.querySelectorAll('.new_required'), function (item) {
            var control = item.matches('input,select,textarea') ? item : item.querySelector('input:checked, select, textarea, input');
            if (!control) return;
            var value = String(control.value || '').toUpperCase();
            if (value.normalize) value = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            value = value.replace(/[^A-Z]/g, '');
            if (exceptionMode) {
                blocked = blocked || ['AUSENTE', 'MUDOUSE', 'DESCONHECIDO'].indexOf(value) !== -1;
            } else {
                blocked = blocked || value === 'NAO';
            }
        });
        var submit = document.querySelector('button[name="action_type"][value="preview"]');
        if (submit) submit.disabled = blocked;
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
        if (behavior === 'processo') {
            field.value = formatProcess(field.value);
            return;
        }
        if (behavior === 'date') {
            field.value = formatDate(field.value);
            return;
        }
        if (behavior === 'decimal') {
            field.value = formatDecimal(field.value);
            return;
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

    var structuredConditionalRules = @json($conditionalRules);
    structuredConditionalRules.forEach(function (rule) {
        var controls = document.querySelectorAll('[name="campo_' + rule.source_id + '"]');
        Array.prototype.forEach.call(controls, function (control) {
            control.addEventListener(control.type === 'text' || control.tagName === 'TEXTAREA' ? 'input' : 'change', function () {
                applyStructuredConditionalRule(rule);
            });
        });
        applyStructuredConditionalRule(rule);
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
            } else if (['date', 'cpf', 'cnpj', 'cpf_cnpj', 'cep', 'fone', 'integer', 'processo'].indexOf(behavior) !== -1) {
                applyFieldBehavior(field);
            }
        });
        if (field.tagName === 'SELECT' || field.type === 'radio') {
            field.addEventListener('change', function () {
                executeSupportedEvents(field.getAttribute('data-event-blur') || field.getAttribute('data-event-focus'), field);
            });
        }
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
            } else if (['date', 'cpf', 'cnpj', 'cpf_cnpj', 'cep', 'fone', 'integer', 'processo'].indexOf(behavior) !== -1) {
                applyFieldBehavior(field);
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var quantity = Array.prototype.slice.call(document.querySelectorAll('select[data-event-focus]')).filter(function (field) {
        return String(field.getAttribute('data-event-focus') || '').indexOf('nbens') !== -1;
    })[0];
    if (!quantity) return;

    function updateAssetFields() {
        var count = parseInt(quantity.value, 10) || 0;
        var event = String(quantity.getAttribute('data-event-focus') || '')
            .replace(/&quot;/gi, '"').replace(/&#039;|&#39;/gi, "'");
        var match = event.match(/nbens\s*\(\s*["']([^"']+)["']/i);
        var ids = match ? match[1].split('_|_').map(function (item) {
            return item.replace(/\D/g, '');
        }).filter(Boolean).slice(1) : [];
        ids.forEach(function (id, index) {
            var wrapper = document.querySelector('.dis_campo' + id);
            if (wrapper) wrapper.style.display = index < count ? '' : 'none';
        });
    }

    quantity.addEventListener('change', updateAssetFields);
    updateAssetFields();
});
</script>
<script>
// Campos obrigatorios: aviso antes de gerar a previa, sem impedir quem quiser seguir.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[data-assemble-form]');
    if (!form) return;

    var previewButton = form.querySelector('button[name="action_type"][value="preview"]');
    var hiddenInput = document.getElementById('hidden-fields');
    var box = document.getElementById('required-check');
    var list = document.getElementById('required-check-list');
    var lastAction = '';
    var confirmed = false;

    function wrappers() {
        return Array.prototype.slice.call(form.querySelectorAll('.legacy-field-wrap[data-field-id]'));
    }

    function isShown(wrapper) {
        return window.getComputedStyle(wrapper).display !== 'none';
    }

    function isBlank(wrapper) {
        if (wrapper.querySelector('input[type="radio"]')) {
            return !wrapper.querySelector('input[type="radio"]:checked');
        }
        var control = wrapper.querySelector('select, textarea, input:not([type="hidden"])');
        if (!control) return false;
        var value = String(control.value || '');
        if (control.tagName === 'TEXTAREA') {
            if (/<(img|table|hr)\b/i.test(value)) return false;
            // DOMParser nao executa nem carrega nada do conteudo.
            value = new DOMParser().parseFromString(value, 'text/html').body.textContent || '';
        }
        return value.replace(/ /g, ' ').trim() === '';
    }

    function missingRequired() {
        return wrappers().filter(function (wrapper) {
            return wrapper.getAttribute('data-required') === '1' && isShown(wrapper) && isBlank(wrapper);
        });
    }

    function mark(ids) {
        wrappers().forEach(function (wrapper) {
            var missing = ids.indexOf(String(wrapper.getAttribute('data-field-id'))) !== -1;
            wrapper.classList.toggle('is-missing-required', missing);
        });
    }

    function focusField(wrapper) {
        var target = wrapper.querySelector('.js-open-rich-editor')
            || wrapper.querySelector('select, input:not([type="hidden"]), textarea');
        wrapper.scrollIntoView({block: 'center'});
        if (target) target.focus();
    }

    // O servidor precisa saber o que as regras da tela esconderam; ver PeticaoRequiredFieldsService.
    function writeHiddenFields() {
        if (!hiddenInput) return;
        hiddenInput.value = ['0'].concat(wrappers().filter(function (wrapper) {
            return !isShown(wrapper);
        }).map(function (wrapper) {
            return wrapper.getAttribute('data-field-id');
        })).join(',');
    }

    form.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('button[type="submit"]') : null;
        if (button) lastAction = button.value;
    });

    form.addEventListener('submit', function (event) {
        writeHiddenFields();

        var action = event.submitter ? event.submitter.value : lastAction;
        if (action !== 'preview' || confirmed || !box) return;

        var missing = missingRequired();
        if (!missing.length) return;

        event.preventDefault();
        mark(missing.map(function (wrapper) { return String(wrapper.getAttribute('data-field-id')); }));
        list.textContent = missing.map(function (wrapper) {
            return wrapper.getAttribute('data-field-label') || ('Campo ' + wrapper.getAttribute('data-field-id'));
        }).join(', ') + '.';
        box.style.display = '';
        box.scrollIntoView({block: 'center'});
    });

    var fillButton = document.getElementById('required-check-fill');
    if (fillButton) {
        fillButton.addEventListener('click', function () {
            var missing = missingRequired();
            box.style.display = 'none';
            if (missing.length) focusField(missing[0]);
        });
    }

    var continueButton = document.getElementById('required-check-continue');
    if (continueButton) {
        continueButton.addEventListener('click', function () {
            confirmed = true;
            if (previewButton) previewButton.click();
        });
    }

    // O destaque some assim que o campo recebe algo.
    ['input', 'change'].forEach(function (type) {
        form.addEventListener(type, function (event) {
            var wrapper = event.target.closest ? event.target.closest('.legacy-field-wrap') : null;
            if (wrapper && !isBlank(wrapper)) wrapper.classList.remove('is-missing-required');
        });
    });

    // Previa gerada com pendencias: os campos que o servidor apontou voltam destacados.
    var fromServer = @json($missingRequiredIds);
    if (fromServer.length) mark(fromServer);
});
</script>
@endpush
