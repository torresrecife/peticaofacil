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
    .saved-editor-bar {
        position: sticky;
        top: 12px;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
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
    .saved-editor-summary-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 12px;
    }
    .saved-editor-summary-card {
        background: #f8fbfd;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 10px 12px;
        display: grid;
        gap: 4px;
        min-height: 72px;
    }
    .saved-editor-summary-card span {
        font-size: 11px;
        color: #7b8794;
        text-transform: uppercase;
    }
    .saved-editor-summary-card strong {
        font-size: 18px;
        color: #102a43;
        line-height: 1.2;
        word-break: break-word;
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
    .saved-editor-surface {
        background: #f5f7fa;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 12px;
    }
    .saved-editor-toolbar-host {
        position: sticky;
        top: 86px;
        z-index: 15;
        min-height: 42px;
        padding: 10px 10px 0;
        margin: -4px -4px 16px;
        background: #f5f7fa;
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
    .saved-editor-shell .cke_top,
    .saved-editor-shell .cke_bottom {
        border-radius: 8px;
    }
    .saved-editor-shell .cke_chrome {
        border: 1px solid #cbd2d9;
        box-shadow: none;
    }
    .saved-editor-shell .cke_contents {
        background: #eef2f6;
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
        .saved-editor-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 960px) {
        .saved-editor-bar {
            position: static;
            flex-direction: column;
            align-items: stretch;
        }
        .saved-editor-toolbar-host {
            position: static;
            margin: 0 0 12px;
            padding: 0;
        }
        .saved-editor-surface {
            padding: 12px;
        }
        .saved-editor-grid {
            grid-template-columns: 1fr;
        }
        .saved-editor-summary-grid {
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
                <div class="saved-editor-bar">
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

                <div class="saved-editor-layout">
                    <div class="saved-editor-main">
                        <div class="saved-editor-summary-grid">
                            <div class="saved-editor-summary-card">
                                <span>ID normalizado</span>
                                <strong>{{ $peticao->id }}</strong>
                            </div>
                            <div class="saved-editor-summary-card">
                                <span>Codigo</span>
                                <strong style="font-size:18px;">{{ $peticao->codigo_externo ?: '-' }}</strong>
                            </div>
                            <div class="saved-editor-summary-card">
                                <span>Ultimo salvamento</span>
                                <strong style="font-size:18px;">{{ optional($peticao->salvo_em)->format('d/m/Y H:i') ?: '-' }}</strong>
                            </div>
                            <div class="saved-editor-summary-card">
                                <span>Modelo</span>
                                <strong style="font-size:18px;">{{ $peticao->display_title }}</strong>
                            </div>
                        </div>

                        <div class="form-group full">
                            <label for="saved_editor_content">Conteudo da peticao</label>
                            <div class="saved-editor-surface">
                                <div id="saved-editor-toolbar-host" class="saved-editor-toolbar-host"></div>
                                <textarea id="saved_editor_content" name="cod_pecas" class="js-rich-editor" style="min-height:680px;">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
                            </div>
                            <div class="saved-editor-page-note">O documento fica no centro da tela. Exportacao, metadados e historico ficam separados para reduzir ruído visual durante a revisao.</div>
                        </div>
                    </div>

                    <aside class="saved-editor-side">
                        <div class="saved-editor-card">
                            <h4>Dados da peticao</h4>
                            <div class="saved-editor-meta">
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
        height: 760,
        allowedContent: true,
        sharedSpaces: {
            top: 'saved-editor-toolbar-host'
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
            updateSaveStatus('saving', 'Salvando...');
            if (saveButton) {
                saveButton.disabled = true;
            }
            syncEditor();
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-saved-export-form'), function (form) {
        form.addEventListener('submit', function () {
            syncEditor();
            form.querySelector('textarea[name="cod_pecas"]').value = textarea.value;
            form.querySelector('input[name="nome_cli"]').value = document.getElementById('saved_editor_nome_cli').value;
        });
    });

    window.addEventListener('beforeunload', function (event) {
        if (!isDirty) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });
});
</script>
@endpush
