@extends('layouts.app')

@section('title', 'Editor de peca')

@push('head')
@php
    $legacyAppUrl = rtrim((string) config('legacy.app_url'), '/');
@endphp
@if($legacyAppUrl !== '')
    <script src="{{ $legacyAppUrl }}/ckeditor/ckeditor.js"></script>
    <script src="{{ $legacyAppUrl }}/ckfinder/ckfinder.js"></script>
@endif
<style>
    .editor-shell {
        display: grid;
        gap: 18px;
    }
    .editor-command-bar {
        position: sticky;
        top: 12px;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .editor-command-bar__title {
        display: grid;
        gap: 4px;
    }
    .editor-command-bar__title strong {
        font-size: 16px;
        color: #102a43;
    }
    .editor-command-bar__title span {
        font-size: 12px;
        color: #627d98;
    }
    .editor-command-bar__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        font-size: 13px;
        color: #52606d;
    }
    .editor-status-badge {
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
    .editor-status-badge.is-dirty {
        border-color: #f7c948;
        background: #fff7d6;
        color: #8d5c00;
    }
    .editor-status-badge.is-saving {
        border-color: #2f80ed;
        background: #ebf5ff;
        color: #1d4f91;
    }
    .editor-surface {
        background: #f5f7fa;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 16px;
    }
    .editor-toolbar-host {
        position: sticky;
        top: 82px;
        z-index: 15;
        min-height: 42px;
        padding: 10px 10px 0;
        margin: -4px -4px 16px;
        background: #f5f7fa;
    }
    .editor-workspace {
        padding: 0 8px 12px;
    }
    .editor-page-note {
        margin-top: 8px;
        font-size: 12px;
        color: #7b8794;
    }
    .editor-shell .cke_top,
    .editor-shell .cke_bottom {
        border-radius: 8px;
    }
    .editor-shell .cke_chrome {
        border: 1px solid #cbd2d9;
        box-shadow: none;
    }
    .editor-shell .cke_contents {
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
    .editor-shell .cke_wysiwyg_frame,
    .editor-shell .cke_contents iframe {
        background: transparent;
    }
    .editor-shell .cke_toolgroup {
        border-radius: 6px;
    }
    .editor-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 18px;
        align-items: start;
    }
    .editor-document-panel {
        min-width: 0;
    }
    .editor-side-panel {
        display: grid;
        gap: 16px;
        position: sticky;
        top: 92px;
    }
    .editor-side-card {
        background: #fff;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 16px;
        display: grid;
        gap: 14px;
    }
    .editor-side-card h4 {
        margin: 0;
        font-size: 14px;
        color: #102a43;
    }
    .editor-side-grid {
        display: grid;
        gap: 12px;
    }
    .editor-side-item {
        display: grid;
        gap: 4px;
    }
    .editor-side-item span {
        font-size: 12px;
        color: #7b8794;
    }
    .editor-side-item strong {
        font-size: 14px;
        color: #243b53;
        font-weight: 600;
        word-break: break-word;
    }
    .editor-side-actions {
        display: grid;
        gap: 10px;
    }
    .editor-side-actions .button,
    .editor-side-actions button {
        width: 100%;
        justify-content: center;
    }
    .editor-side-note {
        font-size: 12px;
        color: #627d98;
        line-height: 1.5;
    }
    .editor-name-field input {
        background: #fff;
    }
    .editor-export-form {
        margin: 0;
    }
    @media (max-width: 960px) {
        .editor-command-bar {
            position: static;
            flex-direction: column;
            align-items: stretch;
        }
        .editor-toolbar-host {
            position: static;
            margin: 0 0 12px;
            padding: 0;
        }
        .editor-surface {
            padding: 12px;
        }
        .editor-layout {
            grid-template-columns: 1fr;
        }
        .editor-side-panel {
            position: static;
        }
    }
</style>
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Editor final da peca</h2>
    <div class="actions">
        @php
            $backRoute = route('peticoes.normalized.show', $modelo);
            $saveRoute = route('peticoes.normalized.editor.save', $modelo);
            $wordRoute = route('peticoes.normalized.editor.export.word', $modelo);
            $pdfRoute = route('peticoes.normalized.editor.export.pdf', $modelo);
        @endphp
        <a class="button secondary link" href="{{ $backRoute }}">Voltar para montagem</a>
    </div>
</div>

<div class="stack">
    <div class="panel">
        <form method="post" action="{{ $saveRoute }}" id="editor-save-form">
            @csrf
            <input type="hidden" name="codigo_processo" value="{{ old('codigo_processo', $codigoProcesso ?? '') }}">
            <div class="editor-shell">
                <div class="editor-command-bar">
                    <div class="editor-command-bar__meta">
                        <div class="editor-command-bar__title">
                            <strong>{{ $modelo->tipo_nome }}</strong>
                            <span>Revise, ajuste e salve a peticao antes da exportacao.</span>
                        </div>
                        <span id="editor-save-status" class="editor-status-badge">Sem alteracoes pendentes</span>
                        <span>Atalho: <strong>Ctrl+S</strong></span>
                        <span>Documento: <strong>A4</strong></span>
                    </div>
                    <div class="actions">
                        <a class="button secondary link" href="{{ $backRoute }}">Voltar para montagem</a>
                        <button type="submit" id="editor-save-button">Salvar peca</button>
                    </div>
                </div>

                <div class="editor-layout">
                    <div class="editor-document-panel">
                        <div class="editor-surface">
                            <div id="editor-toolbar-host" class="editor-toolbar-host"></div>
                            <div class="editor-workspace">
                                <textarea id="editor_content" name="cod_pecas" class="js-rich-editor" style="min-height:480px;">{{ old('cod_pecas', $content) }}</textarea>
                            </div>
                        </div>
                        <div class="editor-page-note">A edicao acontece em uma pagina visual centralizada, com estilos juridicos e barra de ferramentas fixa.</div>
                    </div>

                    <aside class="editor-side-panel">
                        <div class="editor-side-card">
                            <h4>Dados da peticao</h4>
                            <div class="editor-side-grid">
                                <div class="editor-side-item">
                                    <span>Modelo</span>
                                    <strong>{{ $modelo->tipo_nome }}</strong>
                                </div>
                                <div class="editor-side-item">
                                    <span>Processo</span>
                                    <strong>{{ old('codigo_processo', $codigoProcesso ?? 'Nao informado') }}</strong>
                                </div>
                                <div class="editor-side-item editor-name-field">
                                    <label for="editor_nome_cli">Nome do arquivo / cliente</label>
                                    <input id="editor_nome_cli" name="nome_cli" value="{{ old('nome_cli', $nomeCli) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="editor-side-card">
                            <h4>Acoes</h4>
                            <div class="editor-side-actions">
                                <button type="submit">Salvar peca</button>

                                <form method="post" action="{{ $wordRoute }}" class="js-export-form editor-export-form">
                                    @csrf
                                    <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $nomeCli) }}">
                                    <textarea name="cod_pecas" style="display:none;">{{ old('cod_pecas', $content) }}</textarea>
                                    <button type="submit" class="button secondary">Exportar Word</button>
                                </form>

                                <form method="post" action="{{ $pdfRoute }}" class="js-export-form editor-export-form">
                                    @csrf
                                    <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $nomeCli) }}">
                                    <textarea name="cod_pecas" style="display:none;">{{ old('cod_pecas', $content) }}</textarea>
                                    <button type="submit" class="button secondary">Exportar PDF</button>
                                </form>
                            </div>
                            <div class="editor-side-note">
                                Exporte sempre a versao atual do documento. O sistema sincroniza o conteudo do editor antes do envio.
                            </div>
                        </div>

                        <div class="editor-side-card">
                            <h4>Orientacao</h4>
                            <div class="editor-side-note">
                                Faça os ajustes finais diretamente no documento. Use a barra superior para formatacao, o status para acompanhar alteracoes pendentes e salve antes de sair da tela.
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </form>
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
    var textarea = document.getElementById('editor_content');
    if (!textarea) {
        return;
    }

    if (CKEDITOR.instances[textarea.id]) {
        CKEDITOR.instances[textarea.id].destroy(true);
    }

    var saveForm = document.getElementById('editor-save-form');
    var saveStatus = document.getElementById('editor-save-status');
    var saveButton = document.getElementById('editor-save-button');
    var isDirty = false;
    var isSubmitting = false;

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

    var editor = CKEDITOR.replace(textarea.id, {
        height: 540,
        allowedContent: true,
        sharedSpaces: {
            top: 'editor-toolbar-host'
        }
    });

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
    });

    function syncEditor() {
        if (CKEDITOR.instances[textarea.id]) {
            CKEDITOR.instances[textarea.id].updateElement();
        }
    }


    if (saveForm) {
        saveForm.addEventListener('submit', function () {
            isSubmitting = true;
            isDirty = false;
            updateSaveStatus('saving', 'Salvando...');
            if (saveButton) {
                saveButton.disabled = true;
            }
            syncEditor();
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-export-form'), function (form) {
        form.addEventListener('submit', function () {
            isSubmitting = true;
            isDirty = false;
            syncEditor();
            var html = textarea.value;
            form.querySelector('textarea[name="cod_pecas"]').value = html;
            form.querySelector('input[name="nome_cli"]').value = document.getElementById('editor_nome_cli').value;
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
