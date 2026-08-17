@extends('layouts.app')

@section('title', 'Editor de peticao salva')

@push('head')
@php
    $legacyAppUrl = rtrim((string) config('legacy.app_url'), '/');
@endphp
@if($legacyAppUrl !== '')
    <script src="{{ $legacyAppUrl }}/ckeditor/ckeditor.js"></script>
    <script src="{{ $legacyAppUrl }}/ckfinder/ckfinder.js"></script>
@endif
<style>
    .saved-editor-shell {
        display: grid;
        gap: 18px;
    }
    .saved-editor-sticky-stack {
        position: sticky;
        top: 12px;
        z-index: 20;
        display: grid;
        gap: 0;
    }
    .saved-editor-bar {
        display: grid;
        gap: 12px;
        padding: 14px 16px;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .saved-editor-toolbar-host {
        width: 100%;
    }
    .saved-editor-toolbar-host:empty {
        display: none;
    }
    .saved-editor-toolbar-host.is-mounted {
        padding-top: 12px;
        border-top: 1px solid #d9e2ec;
    }
    .saved-editor-toolbar-host .cke_top {
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    .saved-editor-bar__title {
        display: grid;
        gap: 4px;
    }
    .saved-editor-bar__title strong {
        font-size: 16px;
        color: #102a43;
    }
    .saved-editor-bar__title span {
        font-size: 12px;
        color: #627d98;
    }
    .saved-editor-bar__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        font-size: 13px;
        color: #52606d;
    }
    .saved-editor-bar__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .saved-editor-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid #bcccdc;
        background: #f0f4f8;
        color: #243b53;
        font-weight: 600;
    }
    .saved-editor-status.is-dirty {
        border-color: #f7c948;
        background: #fff7d6;
        color: #8d5c00;
    }
    .saved-editor-status.is-saving {
        border-color: #2f80ed;
        background: #ebf5ff;
        color: #1d4f91;
    }
    .saved-editor-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 18px;
        align-items: start;
    }
    .saved-editor-main {
        min-width: 0;
    }
    .saved-editor-side {
        position: sticky;
        top: 92px;
        display: grid;
        gap: 16px;
    }
    .saved-editor-card {
        background: #fff;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 16px;
        display: grid;
        gap: 14px;
    }
    .saved-editor-card h4 {
        margin: 0;
        font-size: 14px;
        color: #102a43;
    }
    .saved-editor-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .saved-editor-meta {
        display: grid;
        gap: 12px;
    }
    .saved-editor-meta__item {
        display: grid;
        gap: 4px;
    }
    .saved-editor-meta__item span {
        font-size: 12px;
        color: #7b8794;
    }
    .saved-editor-meta__item strong {
        font-size: 14px;
        color: #243b53;
        word-break: break-word;
    }
    .saved-editor-actions {
        display: grid;
        gap: 10px;
    }
    .saved-editor-actions .button,
    .saved-editor-actions button {
        display: inline-flex;
        align-items: center;
        min-height: 40px;
        width: 100%;
        justify-content: center;
        box-sizing: border-box;
    }
    .saved-editor-review {
        display: grid;
        gap: 12px;
    }
    .saved-editor-review__summary {
        display: grid;
        gap: 8px;
    }
    .saved-editor-review__badge-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .saved-editor-review__badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f0f4f8;
        border: 1px solid #bcccdc;
        font-size: 12px;
        font-weight: 600;
        color: #243b53;
    }
    .saved-editor-review__badge.is-high {
        background: #fde8e8;
        border-color: #f5a3a3;
        color: #8a1c1c;
    }
    .saved-editor-review__badge.is-medium {
        background: #fff7d6;
        border-color: #f7c948;
        color: #8d5c00;
    }
    .saved-editor-review__badge.is-low {
        background: #ebf5ff;
        border-color: #9ac9ff;
        color: #1d4f91;
    }
    .saved-editor-review__status {
        font-size: 12px;
        color: #52606d;
    }
    .saved-editor-review__summary-text {
        font-size: 13px;
        color: #243b53;
        line-height: 1.5;
    }
    .saved-editor-review__warnings {
        display: grid;
        gap: 8px;
    }
    .saved-editor-review__warning {
        padding: 10px 12px;
        border-radius: 6px;
        background: #fff7d6;
        border: 1px solid #f7c948;
        color: #8d5c00;
        font-size: 12px;
        line-height: 1.45;
    }
    .saved-editor-review__issues {
        display: grid;
        gap: 10px;
    }
    .saved-editor-review__issue {
        display: grid;
        gap: 6px;
        padding: 12px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        background: #f8fbfd;
        cursor: pointer;
    }
    .saved-editor-review__issue:hover {
        border-color: #9fb3c8;
        background: #f0f4f8;
    }
    .saved-editor-review__issue.is-active {
        border-color: #486581;
        box-shadow: inset 0 0 0 1px #486581;
    }
    .saved-editor-review__issue.is-applied {
        border-color: #9fb3c8;
        background: #f0fdf4;
    }
    .saved-editor-review__issue-header {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: flex-start;
        font-size: 12px;
    }
    .saved-editor-review__issue-title {
        font-weight: 700;
        color: #102a43;
        text-transform: capitalize;
    }
    .saved-editor-review__issue-severity {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
    }
    .saved-editor-review__issue-severity.is-high {
        color: #8a1c1c;
    }
    .saved-editor-review__issue-severity.is-medium {
        color: #8d5c00;
    }
    .saved-editor-review__issue-severity.is-low {
        color: #1d4f91;
    }
    .saved-editor-review__issue-snippet {
        margin: 0;
        padding: 8px 10px;
        border-radius: 4px;
        background: #eef2f6;
        color: #243b53;
        font-size: 12px;
        line-height: 1.45;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .saved-editor-review__issue-message,
    .saved-editor-review__issue-suggestion {
        font-size: 12px;
        line-height: 1.5;
        color: #243b53;
    }
    .saved-editor-review__issue-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }
    .saved-editor-review__apply-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 0 12px;
        border: 1px solid #9fb3c8;
        border-radius: 6px;
        background: #fff;
        color: #102a43;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .saved-editor-review__apply-button:hover {
        border-color: #486581;
        background: #f0f4f8;
    }
    .saved-editor-review__apply-button[disabled] {
        opacity: 0.65;
        cursor: default;
    }
    .saved-editor-review__empty {
        font-size: 12px;
        color: #52606d;
        line-height: 1.5;
    }
    .saved-editor-review__hint {
        font-size: 12px;
        color: #7b8794;
        line-height: 1.5;
    }
    .saved-editor-surface {
        background: #f5f7fa;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 12px;
    }
    .saved-editor-page-note {
        margin-top: 8px;
        font-size: 12px;
        color: #7b8794;
    }
    .saved-editor-history-table .actions {
        flex-wrap: wrap;
    }
    .saved-editor-history-table th:last-child,
    .saved-editor-history-table td:last-child {
        width: 320px;
    }
    .saved-editor-shell .cke_bottom {
        border-radius: 8px;
    }
    .saved-editor-shell .cke_chrome {
        border: 1px solid #cbd2d9;
        box-shadow: none;
    }
    .saved-editor-shell .cke_contents {
        background-color: #eef2f6;
        background-image:
            repeating-linear-gradient(
                to bottom,
                transparent 0,
                transparent 1123px,
                #d0d7de 1123px,
                #c0c8d2 1125px,
                #d0d7de 1127px,
                #eef2f6 1127px,
                #eef2f6 1171px
            ),
            linear-gradient(
                to right,
                transparent calc(50% - 397px),
                #ffffff calc(50% - 397px),
                #ffffff calc(50% + 397px),
                transparent calc(50% + 397px)
            );
        background-repeat: repeat-y, no-repeat;
        background-size: 100% 1171px, 100% 100%;
    }
    .saved-editor-shell .cke_wysiwyg_frame,
    .saved-editor-shell .cke_contents iframe {
        background: transparent;
    }
    .saved-editor-shell .cke_toolgroup {
        border-radius: 6px;
    }
    @media (max-width: 1100px) {
        .saved-editor-layout {
            grid-template-columns: 1fr;
        }
        .saved-editor-side {
            position: static;
        }
    }
    @media (max-width: 960px) {
        .saved-editor-sticky-stack {
            position: static;
        }
        .saved-editor-bar__row {
            flex-direction: column;
            align-items: stretch;
        }
        .saved-editor-surface {
            padding: 12px;
        }
        .saved-editor-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Editor de peticao salva</h2>
    <div class="actions">
        <a class="button secondary link" href="{{ route('pecas.index') }}">Voltar para pecas salvas</a>
    </div>
</div>

<div class="stack">
    <div class="panel">
        <form method="post" action="{{ route('peticoes.saved.update', $peticao) }}" id="saved-editor-form">
            @csrf
            @method('put')

            <div class="saved-editor-shell">
                <div class="saved-editor-sticky-stack">
                    <div class="saved-editor-bar">
                        <div class="saved-editor-bar__row">
                            <div class="saved-editor-bar__meta">
                                <div class="saved-editor-bar__title">
                                    <strong>{{ $peticao->display_title }}</strong>
                                    <span>Revise a minuta, ajuste o texto final e salve antes de exportar.</span>
                                </div>
                                <span id="saved-editor-status" class="saved-editor-status">Sem alteracoes pendentes</span>
                                <span>Atalho: <strong>Ctrl+S</strong></span>
                                <span>Documento: <strong>A4</strong></span>
                            </div>
                            <div class="actions">
                                <button type="submit" id="saved-editor-save-button">Salvar peticao</button>
                            </div>
                        </div>
                        <div id="saved-editor-toolbar-host" class="saved-editor-toolbar-host"></div>
                    </div>
                </div>

                <div class="saved-editor-layout">
                    <div class="saved-editor-main">
                        <div class="form-group full">
                            <div class="saved-editor-surface">
                                <textarea id="saved_editor_content" name="cod_pecas" class="js-rich-editor" style="min-height:680px;">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
                            </div>
                            <div class="saved-editor-page-note">O documento fica no centro da tela. Exportacao, metadados e historico ficam separados para reduzir ruído visual durante a revisao.</div>
                        </div>
                    </div>

                    <aside class="saved-editor-side">
                        <div class="saved-editor-card">
                            <h4>Revisao IA</h4>
                            <div id="saved-editor-review" class="saved-editor-review">
                                <div class="saved-editor-review__summary">
                                    <div class="saved-editor-review__badge-row">
                                        <span id="saved-editor-review-score" class="saved-editor-review__badge">Sem analise</span>
                                        <span id="saved-editor-review-count" class="saved-editor-review__badge">0 achados</span>
                                    </div>
                                    <div id="saved-editor-review-status" class="saved-editor-review__status">Clique em "Revisar agora" para analisar apenas erros graves.</div>
                                    <div id="saved-editor-review-summary" class="saved-editor-review__summary-text"></div>
                                </div>
                                <div id="saved-editor-review-warnings" class="saved-editor-review__warnings"></div>
                                <div id="saved-editor-review-issues" class="saved-editor-review__issues">
                                    <div class="saved-editor-review__empty">Nenhum achado carregado ainda.</div>
                                </div>
                            </div>
                            <button type="button" id="saved-editor-review-button" class="button secondary">Revisar agora</button>
                        </div>

                        <div class="saved-editor-card">
                            <h4>Dados da peticao</h4>
                            <div class="saved-editor-meta">
                                <div class="saved-editor-meta__item">
                                    <span>ID normalizado</span>
                                    <strong>{{ $peticao->id }}</strong>
                                </div>
                                <div class="saved-editor-meta__item">
                                    <span>Codigo</span>
                                    <strong>{{ $peticao->codigo_externo ?: '-' }}</strong>
                                </div>
                                <div class="saved-editor-meta__item">
                                    <span>Modelo</span>
                                    <strong>{{ $peticao->display_title }}</strong>
                                </div>
                                <div class="saved-editor-meta__item">
                                    <span>Nome do arquivo / cliente</span>
                                    <input id="saved_editor_nome_cli" name="nome_cli" value="{{ old('nome_cli', $peticao->display_reference) }}" required>
                                </div>
                                <div class="saved-editor-meta__item">
                                    <span>Gerada em</span>
                                    <strong>{{ optional($peticao->gerado_em)->format('d/m/Y H:i') ?: '-' }}</strong>
                                </div>
                                <div class="saved-editor-meta__item">
                                    <span>Salva em</span>
                                    <strong>{{ optional($peticao->salvo_em)->format('d/m/Y H:i') ?: '-' }}</strong>
                                </div>
                                <div class="saved-editor-meta__item">
                                    <span>Usuario</span>
                                    <strong>{{ optional($peticao->user)->login_usu ?: '-' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="saved-editor-card">
                            <h4>Acoes</h4>
                            <div class="saved-editor-actions">
                                <button type="submit">Salvar peticao</button>

                                <a href="{{ route('peticoes.saved.print', $peticao) }}" class="button secondary" target="_blank" rel="noopener">Visualizar impressao</a>

                                <button type="submit" class="button secondary" form="saved-export-word-form">Exportar Word</button>

                                <button type="submit" class="button secondary" form="saved-export-pdf-form">Exportar PDF</button>
                            </div>
                            <div class="editor-note">A exportacao usa o texto atual do editor. O sistema sincroniza o conteudo antes do envio.</div>
                        </div>
                    </aside>
                </div>
            </div>
        </form>

        <form method="post" action="{{ route('peticoes.saved.export.word', $peticao) }}" id="saved-export-word-form" class="js-saved-export-form" style="display:none;">
            @csrf
            <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $peticao->cliente_referencia) }}">
            <textarea name="cod_pecas">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
        </form>

        <form method="post" action="{{ route('peticoes.saved.export.pdf', $peticao) }}" id="saved-export-pdf-form" class="js-saved-export-form" style="display:none;">
            @csrf
            <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $peticao->cliente_referencia) }}">
            <textarea name="cod_pecas">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
        </form>

        <input
            type="file"
            id="saved-editor-word-import-input"
            accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            style="display:none;"
        >
    </div>

    <div class="panel">
        <div class="section-title">
            <h3>Historico de versoes</h3>
            <div class="editor-note">Compare, restaure e exporte versoes anteriores sem misturar isso com a area principal de redacao.</div>
        </div>

        <div class="panel-muted" style="margin-bottom:16px;">
            <form method="get" action="{{ route('peticoes.saved.edit', $peticao) }}">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Origem</label>
                        <select name="origin">
                            <option value="">Todas</option>
                            @foreach(['draft', 'save', 'restore', 'manual'] as $origin)
                                <option value="{{ $origin }}" @if(($selectedOrigin ?? '') === $origin) selected @endif>{{ $origin }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Usuario</label>
                        <select name="user_id">
                            <option value="">Todos</option>
                            @foreach($usuariosHistorico as $usuarioHistorico)
                                <option value="{{ $usuarioHistorico->id }}" @if((string) ($selectedUserId ?? '') === (string) $usuarioHistorico->id) selected @endif>{{ $usuarioHistorico->login_usu }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Data inicial</label>
                        <input type="date" name="date_from" value="{{ $selectedDateFrom ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Data final</label>
                        <input type="date" name="date_to" value="{{ $selectedDateTo ?? '' }}">
                    </div>
                </div>
                <div class="actions" style="margin-top:12px;">
                    <button type="submit">Filtrar historico</button>
                    <a class="button secondary link" href="{{ route('peticoes.saved.edit', $peticao) }}">Limpar</a>
                </div>
            </form>
        </div>

        <table class="saved-editor-history-table">
            <thead>
                <tr>
                    <th>Versao</th>
                    <th>Origem</th>
                    <th>Cliente</th>
                    <th>Usuario</th>
                    <th>Data</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($versoes as $versao)
                    <tr>
                        <td>{{ $versao->versao_numero }}</td>
                        <td>{{ $versao->origem_snapshot }}</td>
                        <td>{{ $versao->cliente_referencia_snapshot }}</td>
                        <td>{{ optional($versao->user)->login_usu ?: '-' }}</td>
                        <td>{{ optional($versao->criado_em)->format('d/m/Y H:i') ?: optional($versao->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('peticoes.saved.versions.compare', [$peticao, $versao]) }}">Comparar com atual</a>
                                @if(isset($versoes[$loop->index + 1]))
                                    <a href="{{ route('peticoes.saved.versions.compare', [$peticao, $versao]) }}?target_version={{ $versoes[$loop->index + 1]->id }}">Comparar anterior</a>
                                @endif
                                <form method="post" action="{{ route('peticoes.saved.versions.restore', [$peticao, $versao]) }}">
                                    @csrf
                                    <button type="submit" class="button secondary">Restaurar</button>
                                </form>
                                <form method="post" action="{{ route('peticoes.saved.versions.export.word', [$peticao, $versao]) }}">
                                    @csrf
                                    <button type="submit" class="button secondary">Word</button>
                                </form>
                                <form method="post" action="{{ route('peticoes.saved.versions.export.pdf', [$peticao, $versao]) }}">
                                    @csrf
                                    <button type="submit" class="button secondary">PDF</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Sem versoes registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $versoes->links('vendor.pagination.default') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.CKEDITOR === 'undefined') {
        return;
    }

    var ckfinderBaseUrl = @json(config('legacy.ckfinder_base_url'));
    var textarea = document.getElementById('saved_editor_content');
    var toolbarHost = document.getElementById('saved-editor-toolbar-host');
    var wordImportInput = document.getElementById('saved-editor-word-import-input');
    var reviewButton = document.getElementById('saved-editor-review-button');
    var reviewScore = document.getElementById('saved-editor-review-score');
    var reviewCount = document.getElementById('saved-editor-review-count');
    var reviewStatus = document.getElementById('saved-editor-review-status');
    var reviewSummary = document.getElementById('saved-editor-review-summary');
    var reviewWarnings = document.getElementById('saved-editor-review-warnings');
    var reviewIssues = document.getElementById('saved-editor-review-issues');
    if (!textarea) {
        return;
    }

    if (CKEDITOR.instances[textarea.id]) {
        CKEDITOR.instances[textarea.id].destroy(true);
    }

    var saveForm = document.getElementById('saved-editor-form');
    var saveStatus = document.getElementById('saved-editor-status');
    var saveButton = document.getElementById('saved-editor-save-button');
    var isDirty = false;
    var isSubmitting = false;
    var toolbarElement = null;
    var isReviewRunning = false;
    var bypassReviewOnSubmit = false;
    var latestReviewResult = null;
    var reviewStylesInjected = false;

    function updateSaveStatus(state, text) {
        if (!saveStatus) {
            return;
        }

        saveStatus.classList.remove('is-dirty', 'is-saving');

        if (state === 'dirty') {
            saveStatus.classList.add('is-dirty');
        } else if (state === 'saving') {
            saveStatus.classList.add('is-saving');
        }

        saveStatus.textContent = text;
    }

    function markDirty() {
        if (isDirty) {
            return;
        }

        isDirty = true;
        updateSaveStatus('dirty', 'Alteracoes nao salvas');
    }

    function markSaved() {
        isDirty = false;
        updateSaveStatus('clean', 'Sem alteracoes pendentes');
    }

    function normalizeContentFontSize(html) {
        return (html || '').replace(/font-size\s*:\s*11px\b/ig, 'font-size:12px');
    }

    textarea.value = normalizeContentFontSize(textarea.value);

    var editor = CKEDITOR.replace(textarea.id, {
        height: 760,
        allowedContent: true,
        extraPlugins: 'importword',
        toolbar: [
            { name: 'clipboard', items: [ 'Undo', 'Redo', '-', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', 'ImportWord', '-', 'SelectAll' ] },
            { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat', 'CopyFormatting' ] },
            { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
            '/',
            { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
            { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
            { name: 'insert', items: [ 'Table', 'HorizontalRule', 'PageBreak', 'SpecialChar', 'Smiley', 'Image', 'Link', 'Unlink' ] },
            { name: 'document', items: [ 'ShowBlocks', 'Maximize', 'Source', 'Preview', 'Print' ] }
        ]
    });

    function mountToolbarInHost() {
        if (!toolbarHost || !toolbarElement) {
            return;
        }

        toolbarHost.appendChild(toolbarElement);
        toolbarHost.classList.add('is-mounted');
    }

    function mountToolbarInEditor() {
        if (!toolbarElement || !editor.container || !editor.container.$) {
            return;
        }

        var inner = editor.container.$.querySelector('.cke_inner');
        var contents = editor.container.$.querySelector('.cke_contents');

        if (!inner || !contents) {
            return;
        }

        inner.insertBefore(toolbarElement, contents);
        if (toolbarHost) {
            toolbarHost.classList.remove('is-mounted');
        }
    }

    if (window.CKFinder && ckfinderBaseUrl) {
        CKFinder.setupCKEditor(editor, ckfinderBaseUrl);
    }

    editor.addCommand('appSaveDocument', {
        exec: function () {
            if (saveForm) {
                saveForm.requestSubmit();
            }
        }
    });
    editor.setKeystroke(CKEDITOR.CTRL + 83, 'appSaveDocument');

    editor.on('instanceReady', function () {
        if (toolbarHost && editor.container && editor.container.$) {
            toolbarElement = editor.container.$.querySelector('.cke_top');
            if (toolbarElement) {
                mountToolbarInHost();
            }
        }

        markSaved();

        editor.document.on('keydown', function (event) {
            var data = event.data;
            if (data.$.ctrlKey && (data.$.keyCode === 83 || data.$.key === 's' || data.$.key === 'S')) {
                data.$.preventDefault();
                if (saveForm) {
                    saveForm.requestSubmit();
                }
            }
        });

    });

    editor.on('change', markDirty);
    editor.on('afterCommandExec', function (event) {
        var dirtyCommands = ['bold', 'italic', 'underline', 'justifyleft', 'justifycenter', 'justifyright', 'justifyblock', 'numberedlist', 'bulletedlist', 'indent', 'outdent', 'pagebreak', 'removeformat'];
        if (event.data && dirtyCommands.indexOf((event.data.name || '').toLowerCase()) !== -1) {
            markDirty();
        }

        if (event.data && (event.data.name || '').toLowerCase() === 'maximize') {
            window.setTimeout(function () {
                var maximizeCommand = editor.getCommand('maximize');
                if (maximizeCommand && maximizeCommand.state === CKEDITOR.TRISTATE_ON) {
                    mountToolbarInEditor();
                    return;
                }

                mountToolbarInHost();
            }, 0);
        }
    });

    editor.on('wordImportRequested', function () {
        if (!wordImportInput) {
            window.alert('A importacao de arquivo Word nao esta disponivel nesta tela.');
            return;
        }

        wordImportInput.value = '';
        wordImportInput.click();
    });

    function syncEditor() {
        if (CKEDITOR.instances[textarea.id]) {
            clearReviewHighlights();
            CKEDITOR.instances[textarea.id].updateElement();
            textarea.value = normalizeContentFontSize(textarea.value);
            if (latestReviewResult && Array.isArray(latestReviewResult.issues) && latestReviewResult.issues.length) {
                applyReviewHighlights(latestReviewResult.issues);
            }
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function injectReviewStyles() {
        if (reviewStylesInjected || !editor.document || !editor.document.getHead) {
            return;
        }

        var head = editor.document.getHead().$;
        if (!head) {
            return;
        }

        var existingStyle = head.querySelector('#saved-editor-review-highlight-style');
        if (existingStyle) {
            reviewStylesInjected = true;
            return;
        }

        var style = head.ownerDocument.createElement('style');
        style.id = 'saved-editor-review-highlight-style';
        style.type = 'text/css';
        style.textContent = ''
            + '.review-highlight{padding:0 1px;border-radius:2px;box-shadow:inset 0 -2px 0 rgba(0,0,0,.08);}'
            + '.review-highlight--alta{background:#ffd9e2;color:inherit;outline:1px solid #f29bb0;}'
            + '.review-highlight--media{background:#fff7d6;color:inherit;outline:1px solid #f7c948;}'
            + '.review-highlight--baixa{background:#ebf5ff;color:inherit;outline:1px solid #9ac9ff;}'
            + '.review-highlight.is-active{outline:2px solid #486581;box-shadow:0 0 0 1px #486581 inset;}';
        head.appendChild(style);
        reviewStylesInjected = true;
    }

    function unwrapNode(node) {
        if (!node || !node.parentNode) {
            return;
        }

        while (node.firstChild) {
            node.parentNode.insertBefore(node.firstChild, node);
        }

        node.parentNode.removeChild(node);
    }

    function clearReviewHighlights() {
        if (!editor.document || !editor.document.$ || !editor.document.$.body) {
            return;
        }

        Array.prototype.slice.call(
            editor.document.$.body.querySelectorAll('span[data-review-highlight="1"]')
        ).forEach(unwrapNode);
    }

    function normalizeWhitespaceWithMap(text) {
        var normalized = '';
        var map = [];
        var inWhitespace = false;
        var whitespaceStart = 0;

        for (var index = 0; index < text.length; index += 1) {
            var char = text.charAt(index);

            if (/\s/.test(char)) {
                if (!inWhitespace) {
                    inWhitespace = true;
                    whitespaceStart = index;
                }
                continue;
            }

            if (inWhitespace) {
                normalized += ' ';
                map.push({ start: whitespaceStart, end: index });
                inWhitespace = false;
            }

            normalized += char;
            map.push({ start: index, end: index + 1 });
        }

        if (inWhitespace) {
            normalized += ' ';
            map.push({ start: whitespaceStart, end: text.length });
        }

        while (normalized.charAt(0) === ' ') {
            normalized = normalized.slice(1);
            map.shift();
        }

        while (normalized.charAt(normalized.length - 1) === ' ') {
            normalized = normalized.slice(0, -1);
            map.pop();
        }

        return {
            text: normalized,
            map: map
        };
    }

    function collectEditorTextNodes() {
        var body = editor.document && editor.document.$ ? editor.document.$.body : null;
        if (!body) {
            return {
                nodes: [],
                rawText: ''
            };
        }

        var walker = body.ownerDocument.createTreeWalker(body, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                if (!node.nodeValue) {
                    return NodeFilter.FILTER_REJECT;
                }

                var tagName = node.parentNode && node.parentNode.tagName ? node.parentNode.tagName.toUpperCase() : '';
                if (tagName === 'SCRIPT' || tagName === 'STYLE') {
                    return NodeFilter.FILTER_REJECT;
                }

                return NodeFilter.FILTER_ACCEPT;
            }
        });

        var nodes = [];
        var rawText = '';
        var currentNode;

        while ((currentNode = walker.nextNode())) {
            var value = currentNode.nodeValue.replace(/\u00a0/g, ' ');
            nodes.push({
                node: currentNode,
                start: rawText.length,
                end: rawText.length + value.length
            });
            rawText += value;
        }

        return {
            nodes: nodes,
            rawText: rawText
        };
    }

    function locateTextPosition(nodes, rawIndex) {
        for (var index = 0; index < nodes.length; index += 1) {
            var item = nodes[index];
            if (rawIndex >= item.start && rawIndex <= item.end) {
                return {
                    node: item.node,
                    offset: Math.max(0, Math.min(rawIndex - item.start, item.node.nodeValue.length)),
                    nodeIndex: index
                };
            }
        }

        return null;
    }

    function buildReviewRanges(issues) {
        var textIndex = collectEditorTextNodes();
        if (!textIndex.rawText) {
            return [];
        }

        var normalizedText = normalizeWhitespaceWithMap(textIndex.rawText);
        var usedRanges = [];
        var matches = [];

        issues.forEach(function (issue, issueIndex) {
            var snippetData = normalizeWhitespaceWithMap(String(issue.snippet || '').replace(/\u00a0/g, ' '));
            if (!snippetData.text) {
                return;
            }

            var searchIndex = 0;
            while (searchIndex < normalizedText.text.length) {
                var foundIndex = normalizedText.text.indexOf(snippetData.text, searchIndex);
                if (foundIndex === -1) {
                    break;
                }

                var foundEndIndex = foundIndex + snippetData.text.length - 1;
                var rawStart = normalizedText.map[foundIndex] ? normalizedText.map[foundIndex].start : null;
                var rawEnd = normalizedText.map[foundEndIndex] ? normalizedText.map[foundEndIndex].end : null;

                if (rawStart === null || rawEnd === null) {
                    break;
                }

                var overlaps = usedRanges.some(function (range) {
                    return rawStart < range.end && rawEnd > range.start;
                });

                if (!overlaps) {
                    var startPosition = locateTextPosition(textIndex.nodes, rawStart);
                    var endPosition = locateTextPosition(textIndex.nodes, rawEnd);

                    if (startPosition && endPosition) {
                        usedRanges.push({ start: rawStart, end: rawEnd });
                        matches.push({
                            issueIndex: issueIndex,
                            severity: String(issue.severity || 'baixa').toLowerCase(),
                            rawStart: rawStart,
                            rawEnd: rawEnd,
                            textNodes: textIndex.nodes,
                            startNodeIndex: startPosition.nodeIndex,
                            endNodeIndex: endPosition.nodeIndex,
                            startNode: startPosition.node,
                            startOffset: startPosition.offset,
                            endNode: endPosition.node,
                            endOffset: endPosition.offset
                        });
                    }

                    break;
                }

                searchIndex = foundIndex + 1;
            }
        });

        return matches.sort(function (left, right) {
            return right.rawStart - left.rawStart;
        });
    }

    function createReviewHighlightWrapper(issueIndex, severity) {
        var wrapper = editor.document.$.createElement('span');
        wrapper.setAttribute('data-review-highlight', '1');
        wrapper.setAttribute('data-review-issue-index', String(issueIndex));
        wrapper.className = 'review-highlight review-highlight--' + (
            severity === 'alta' ? 'alta' : (severity === 'media' ? 'media' : 'baixa')
        );

        return wrapper;
    }

    function wrapTextNodeSegment(node, startOffset, endOffset, issueIndex, severity) {
        if (!node || startOffset >= endOffset) {
            return;
        }

        var targetNode = node;
        if (startOffset > 0) {
            targetNode = targetNode.splitText(startOffset);
        }

        if ((endOffset - startOffset) < targetNode.nodeValue.length) {
            targetNode.splitText(endOffset - startOffset);
        }

        var wrapper = createReviewHighlightWrapper(issueIndex, severity);
        targetNode.parentNode.insertBefore(wrapper, targetNode);
        wrapper.appendChild(targetNode);
    }

    function wrapReviewRange(range) {
        var textNodes = range.textNodes || [];
        if (!textNodes.length) {
            return false;
        }

        var segments = [];
        for (var index = range.startNodeIndex; index <= range.endNodeIndex; index += 1) {
            var item = textNodes[index];
            if (!item || !item.node || !item.node.nodeValue) {
                continue;
            }

            var segmentStart = index === range.startNodeIndex ? range.startOffset : 0;
            var segmentEnd = index === range.endNodeIndex ? range.endOffset : item.node.nodeValue.length;

            if (segmentStart < segmentEnd) {
                segments.push({
                    node: item.node,
                    startOffset: segmentStart,
                    endOffset: segmentEnd
                });
            }
        }

        for (var reverseIndex = segments.length - 1; reverseIndex >= 0; reverseIndex -= 1) {
            var segment = segments[reverseIndex];
            wrapTextNodeSegment(segment.node, segment.startOffset, segment.endOffset, range.issueIndex, range.severity);
        }

        return segments.length > 0;
    }

    function getIssueHighlights(issueIndex) {
        if (!editor.document || !editor.document.$ || !editor.document.$.body) {
            return [];
        }

        return Array.prototype.slice.call(
            editor.document.$.body.querySelectorAll('span[data-review-issue-index="' + issueIndex + '"]')
        );
    }

    function applyReviewHighlights(issues) {
        clearReviewHighlights();

        if (!issues.length || !editor.document || !editor.document.$ || !editor.document.$.body) {
            return;
        }

        injectReviewStyles();

        buildReviewRanges(issues).forEach(function (range) {
            var nativeRange = editor.document.$.createRange();
            nativeRange.setStart(range.startNode, range.startOffset);
            nativeRange.setEnd(range.endNode, range.endOffset);

            try {
                nativeRange.surroundContents(createReviewHighlightWrapper(range.issueIndex, range.severity));
            } catch (error) {
                wrapReviewRange(range);
            }
        });
    }

    function focusReviewHighlight(issueIndex) {
        if (!editor.document || !editor.document.$ || !editor.document.$.body) {
            return;
        }

        Array.prototype.forEach.call(
            editor.document.$.body.querySelectorAll('span[data-review-highlight="1"].is-active'),
            function (node) {
                node.classList.remove('is-active');
            }
        );

        Array.prototype.forEach.call(
            document.querySelectorAll('.saved-editor-review__issue.is-active'),
            function (node) {
                node.classList.remove('is-active');
            }
        );

        var highlights = getIssueHighlights(issueIndex);
        if (!highlights.length) {
            return;
        }

        highlights.forEach(function (node) {
            node.classList.add('is-active');
        });

        var firstHighlight = highlights[0];

        if (typeof firstHighlight.scrollIntoView === 'function') {
            firstHighlight.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest'
            });
        }

        var reviewCard = reviewIssues
            ? reviewIssues.querySelector('.saved-editor-review__issue[data-review-issue-index="' + issueIndex + '"]')
            : null;
        if (reviewCard) {
            reviewCard.classList.add('is-active');
        }

        editor.focus();
    }

    function applyIssueSuggestion(issueIndex) {
        if (!latestReviewResult || !Array.isArray(latestReviewResult.issues)) {
            return;
        }

        var issue = latestReviewResult.issues[issueIndex];
        if (!issue || !issue.suggestion) {
            window.alert('Nao ha sugestao disponivel para este achado.');
            return;
        }

        var replacementRule = deriveIssueReplacement(issue);
        if (!replacementRule.autoApplicable) {
            window.alert(replacementRule.reason || 'Esta sugestao precisa de ajuste manual no editor.');
            return;
        }

        var highlights = getIssueHighlights(issueIndex);
        if (!highlights.length) {
            window.alert('Nao foi possivel localizar o trecho destacado no editor para aplicar esta sugestao.');
            return;
        }

        var replacementText = String(replacementRule.replacement || '');
        var shouldApply = replacementRule.action === 'delete'
            ? window.confirm('Deseja excluir o trecho destacado no editor?')
            : window.confirm(
                'Deseja substituir o trecho destacado pelo texto abaixo?\n\n' + replacementText
            );

        if (!shouldApply) {
            return;
        }

        var replacementRange = editor.document.$.createRange();
        replacementRange.setStartBefore(highlights[0]);
        replacementRange.setEndAfter(highlights[highlights.length - 1]);
        replacementRange.deleteContents();
        if (replacementRule.action !== 'delete') {
            replacementRange.insertNode(editor.document.$.createTextNode(replacementText));
        }

        markDirty();

        var issueCard = reviewIssues
            ? reviewIssues.querySelector('.saved-editor-review__issue[data-review-issue-index="' + issueIndex + '"]')
            : null;
        if (issueCard) {
            issueCard.classList.remove('is-active');
            issueCard.classList.add('is-applied');
        }

        var applyButton = issueCard
            ? issueCard.querySelector('.saved-editor-review__apply-button')
            : null;
        if (applyButton) {
            applyButton.disabled = true;
            applyButton.textContent = 'Sugestao aplicada';
        }

        issue.applied = true;
        updateSaveStatus('dirty', 'Alteracoes nao salvas');
        editor.focus();
    }

    function extractQuotedSuggestionCandidates(text) {
        var candidates = [];
        var pattern = /["“”«»]([^"“”«»]+)["“”«»]/g;
        var match;

        while ((match = pattern.exec(String(text || ''))) !== null) {
            var candidate = String(match[1] || '').trim();
            if (candidate !== '') {
                candidates.push(candidate);
            }
        }

        return candidates;
    }

    function deriveIssueReplacement(issue) {
        var suggestionText = String(issue && issue.suggestion ? issue.suggestion : '').trim();
        if (suggestionText === '') {
            return {
                autoApplicable: false,
                reason: 'A sugestao deste achado esta vazia.'
            };
        }

        var quotedCandidates = extractQuotedSuggestionCandidates(suggestionText);
        if (quotedCandidates.length > 0) {
            return {
                autoApplicable: true,
                replacement: quotedCandidates[0],
                action: 'replace'
            };
        }

        if (/\b(excluir|remover|suprimir|apagar|eliminar)\b/i.test(suggestionText)) {
            return {
                autoApplicable: true,
                replacement: '',
                action: 'delete'
            };
        }

        return {
            autoApplicable: false,
            reason: 'Esta sugestao e orientativa e precisa de ajuste manual no texto.'
        };
    }

    function canAutoApplyIssue(issue) {
        return deriveIssueReplacement(issue).autoApplicable === true;
    }

    function severityWeight(severity) {
        if (severity === 'alta') {
            return 3;
        }

        if (severity === 'media') {
            return 2;
        }

        return 1;
    }

    function countIssuesBySeverity(issues, severity) {
        return issues.filter(function (issue) {
            return (issue.severity || '') === severity;
        }).length;
    }

    function setReviewRunningState(running, label) {
        isReviewRunning = running;

        if (reviewButton) {
            reviewButton.disabled = running;
            reviewButton.textContent = running ? (label || 'Revisando...') : 'Revisar agora';
        }

        if (reviewStatus) {
            reviewStatus.textContent = label || (running ? 'Revisando texto...' : reviewStatus.textContent);
        }
    }

    function renderReview(result) {
        latestReviewResult = result;

        var issues = Array.isArray(result.issues) ? result.issues : [];
        var highCount = countIssuesBySeverity(issues, 'alta');
        var mediumCount = countIssuesBySeverity(issues, 'media');
        var lowCount = countIssuesBySeverity(issues, 'baixa');
        var dominantSeverity = highCount > 0 ? 'high' : (mediumCount > 0 ? 'medium' : 'low');

        if (reviewScore) {
            reviewScore.className = 'saved-editor-review__badge' + (issues.length ? ' is-' + dominantSeverity : '');
            reviewScore.textContent = 'Pontuacao: ' + String(result.score || 0);
        }

        if (reviewCount) {
            reviewCount.className = 'saved-editor-review__badge' + (issues.length ? ' is-' + dominantSeverity : '');
            reviewCount.textContent = issues.length + ' achado' + (issues.length === 1 ? '' : 's');
        }

        if (reviewStatus) {
            reviewStatus.textContent = 'Ultima revisao: ' + new Date().toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        if (reviewSummary) {
            reviewSummary.textContent = result.summary || '';
        }

        if (reviewWarnings) {
            var warnings = Array.isArray(result.warnings) ? result.warnings : [];
            if (!warnings.length) {
                reviewWarnings.innerHTML = '';
            } else {
                reviewWarnings.innerHTML = warnings.map(function (warning) {
                    return '<div class="saved-editor-review__warning">' + escapeHtml(warning) + '</div>';
                }).join('');
            }
        }

        if (reviewIssues) {
            if (!issues.length) {
                reviewIssues.innerHTML = '<div class="saved-editor-review__empty">Nenhum ponto relevante foi encontrado nesta revisao.</div>';
            } else {
                reviewIssues.innerHTML = issues.map(function (issue, issueIndex) {
                    var severityClass = (issue.severity || 'baixa') === 'alta'
                        ? 'is-high'
                        : ((issue.severity || 'baixa') === 'media' ? 'is-medium' : 'is-low');
                    var actionMarkup = '';

                    if (issue.snippet && canAutoApplyIssue(issue)) {
                        actionMarkup = ''
                            + '<div class="saved-editor-review__issue-actions">'
                            + '  <button type="button" class="saved-editor-review__apply-button" data-review-apply-index="' + String(issueIndex) + '">Aplicar sugestao</button>'
                            + '</div>';
                    }

                    return ''
                        + '<div class="saved-editor-review__issue" data-review-issue-index="' + String(issueIndex) + '">'
                        + '  <div class="saved-editor-review__issue-header">'
                        + '      <span class="saved-editor-review__issue-title">' + escapeHtml(issue.category || 'redacao') + '</span>'
                        + '      <span class="saved-editor-review__issue-severity ' + severityClass + '">' + escapeHtml(issue.severity || 'baixa') + '</span>'
                        + '  </div>'
                        + (issue.snippet ? '<pre class="saved-editor-review__issue-snippet">' + escapeHtml(issue.snippet) + '</pre>' : '')
                        + '  <div class="saved-editor-review__issue-message">' + escapeHtml(issue.message || '') + '</div>'
                        + (issue.suggestion ? '<div class="saved-editor-review__issue-suggestion"><strong>Sugestao:</strong> ' + escapeHtml(issue.suggestion) + '</div>' : '')
                        + actionMarkup
                        + '</div>';
                }).join('');
                reviewIssues.innerHTML += '<div class="saved-editor-review__hint">Clique em um achado para localizar o trecho destacado no editor.</div>';
            }
        }

        applyReviewHighlights(issues);
    }

    function requestReview(options) {
        options = options || {};
        syncEditor();

        if (isReviewRunning) {
            return latestReviewResult
                ? Promise.resolve(latestReviewResult)
                : Promise.reject(new Error('A revisao ainda esta em andamento. Aguarde alguns instantes e tente novamente.'));
        }

        setReviewRunningState(true, options.statusLabel || 'Revisando texto...');

        var formData = new FormData();
        formData.append('cod_pecas', textarea.value);

        return window.fetch(@json(route('peticoes.saved.review', $peticao)), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        }).then(function (result) {
            if (!result.ok) {
                throw new Error(result.data && result.data.message ? result.data.message : 'Falha ao revisar o texto.');
            }

            renderReview(result.data);
            setReviewRunningState(false);

            return result.data;
        }).catch(function (error) {
            setReviewRunningState(false);
            if (reviewStatus) {
                reviewStatus.textContent = error && error.message ? error.message : 'Falha ao revisar o texto.';
            }

            return Promise.reject(error);
        });
    }

    function importWordFile(file) {
        clearReviewHighlights();

        var currentText = editor.getData().replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();
        if (currentText !== '' && !window.confirm('O conteudo atual do editor sera substituido pelo arquivo Word importado. Deseja continuar?')) {
            return;
        }

        var formData = new FormData();
        formData.append('word_file', file);

        isSubmitting = true;
        updateSaveStatus('saving', 'Importando arquivo Word...');

        window.fetch(@json(route('peticoes.saved.import.word', $peticao)), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (data) {
                return {
                    ok: response.ok,
                    status: response.status,
                    data: data
                };
            });
        }).then(function (result) {
            if (!result.ok) {
                throw new Error(result.data && result.data.message ? result.data.message : 'Falha ao importar o arquivo Word.');
            }

            editor.setData(normalizeContentFontSize(result.data.html || ''), {
                callback: function () {
                    isSubmitting = false;
                    markDirty();
                }
            });
        }).catch(function (error) {
            isSubmitting = false;
            updateSaveStatus('dirty', 'Falha na importacao do Word');
            window.alert(error && error.message ? error.message : 'Falha ao importar o arquivo Word.');
        });
    }

    if (wordImportInput) {
        wordImportInput.addEventListener('change', function () {
            if (!wordImportInput.files || !wordImportInput.files.length) {
                return;
            }

            importWordFile(wordImportInput.files[0]);
        });
    }

    if (reviewButton) {
        reviewButton.addEventListener('click', function () {
            requestReview({
                statusLabel: 'Revisando texto...'
            }).catch(function (error) {
                window.alert(error && error.message ? error.message : 'Falha ao revisar o texto.');
            });
        });
    }

    if (reviewIssues) {
        reviewIssues.addEventListener('click', function (event) {
            var applyButton = event.target.closest('.saved-editor-review__apply-button');
            if (applyButton) {
                event.preventDefault();
                event.stopPropagation();

                var applyIndex = applyButton.getAttribute('data-review-apply-index');
                if (applyIndex !== null) {
                    applyIssueSuggestion(applyIndex);
                }

                return;
            }

            var issueCard = event.target.closest('.saved-editor-review__issue');
            if (!issueCard) {
                return;
            }

            var issueIndex = issueCard.getAttribute('data-review-issue-index');
            if (issueIndex === null) {
                return;
            }

            focusReviewHighlight(issueIndex);
        });
    }


    if (saveForm) {
        saveForm.addEventListener('submit', function (event) {
            if (!bypassReviewOnSubmit) {
                event.preventDefault();
                syncEditor();

                requestReview({
                    statusLabel: 'Revisando antes de salvar...'
                }).then(function (result) {
                    if (!result) {
                        throw new Error('Nao foi possivel obter o resultado da revisao.');
                    }

                    var issues = Array.isArray(result.issues) ? result.issues : [];
                    var weightedIssues = issues.filter(function (issue) {
                        return severityWeight(issue.severity || 'baixa') >= 2;
                    });

                    if (weightedIssues.length > 0) {
                        var shouldContinue = window.confirm(
                            'A revisao IA encontrou ' + weightedIssues.length + ' ponto(s) medio(s) ou alto(s). Deseja salvar mesmo assim?'
                        );

                        if (!shouldContinue) {
                            return;
                        }
                    }

                    bypassReviewOnSubmit = true;
                    saveForm.requestSubmit();
                }).catch(function (error) {
                    var shouldContinueOnError = window.confirm(
                        (error && error.message ? error.message : 'Falha ao revisar o texto antes do save.') + ' Deseja salvar mesmo assim?'
                    );

                    if (!shouldContinueOnError) {
                        return;
                    }

                    bypassReviewOnSubmit = true;
                    saveForm.requestSubmit();
                });

                return;
            }

            bypassReviewOnSubmit = false;
            isSubmitting = true;
            isDirty = false;
            updateSaveStatus('saving', 'Salvando...');
            if (saveButton) {
                saveButton.disabled = true;
            }
            syncEditor();
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-saved-export-form'), function (form) {
        form.addEventListener('submit', function () {
            isSubmitting = true;
            isDirty = false;
            syncEditor();
            form.querySelector('textarea[name="cod_pecas"]').value = textarea.value;
            form.querySelector('input[name="nome_cli"]').value = document.getElementById('saved_editor_nome_cli').value;
        });
    });

    window.addEventListener('beforeunload', function (event) {
        if (!isDirty || isSubmitting) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

});
</script>
@endpush
