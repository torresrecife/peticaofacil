@extends('layouts.app')

@section('title', 'Editor de peticao salva')

@push('head')
<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
<script src="{{ asset('ckfinder/ckfinder.js') }}"></script>
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
    .saved-editor-review-popover {
        position: absolute;
        z-index: 1200;
        min-width: 240px;
        max-width: 320px;
        padding: 12px;
        border: 1px solid #cbd2d9;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        display: none;
        gap: 10px;
    }
    .saved-editor-review-popover.is-visible {
        display: grid;
    }
    .saved-editor-review-popover__title {
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        color: #102a43;
    }
    .saved-editor-review-popover__message,
    .saved-editor-review-popover__snippet {
        font-size: 12px;
        line-height: 1.45;
        color: #243b53;
    }
    .saved-editor-review-popover__snippet {
        padding: 8px 10px;
        border-radius: 6px;
        background: #f5f7fa;
        word-break: break-word;
    }
    .saved-editor-review-popover__actions {
        display: grid;
        gap: 8px;
    }
    .saved-editor-review-popover__action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 10px;
        border: 1px solid #bcccdc;
        border-radius: 6px;
        background: #fff;
        color: #102a43;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .saved-editor-review-popover__action:hover {
        border-color: #486581;
        background: #f0f4f8;
    }
    .saved-editor-ai-review {
        display: grid;
        gap: 12px;
    }
    .saved-editor-ai-review__status {
        font-size: 12px;
        color: #52606d;
        line-height: 1.5;
    }
    .saved-editor-ai-review__findings {
        display: grid;
        gap: 10px;
    }
    .saved-editor-ai-review__finding {
        display: grid;
        gap: 6px;
        padding: 12px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        background: #f8fbfd;
    }
    .saved-editor-ai-review__finding-header {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: flex-start;
    }
    .saved-editor-ai-review__finding-title {
        font-size: 12px;
        font-weight: 700;
        color: #102a43;
    }
    .saved-editor-ai-review__finding-severity {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .saved-editor-ai-review__finding-severity.is-high {
        color: #8a1c1c;
    }
    .saved-editor-ai-review__finding-severity.is-medium {
        color: #8d5c00;
    }
    .saved-editor-ai-review__finding-severity.is-low {
        color: #1d4f91;
    }
    .saved-editor-ai-review__finding-text,
    .saved-editor-ai-review__finding-recommendation,
    .saved-editor-ai-review__empty {
        font-size: 12px;
        line-height: 1.5;
        color: #243b53;
    }
    .saved-editor-ai-review__finding-snippet {
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
    .saved-editor-proofing-bar {
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .saved-editor-proofing-bar__status {
        font-size: 12px;
        color: #52606d;
    }
    .saved-editor-review-hidden {
        display: none;
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
                            <div class="saved-editor-page-note">Revisão ortográfica do texto.</div>
                            <div class="saved-editor-proofing-bar">
                                <button type="button" id="saved-editor-review-button" class="button secondary">Revisar ortografia novamente</button>
                                <span id="saved-editor-review-status" class="saved-editor-proofing-bar__status">O LanguageTool revisa automaticamente o texto ao abrir a pagina.</span>
                            </div>
                        </div>
                    </div>

                    <aside class="saved-editor-side">
                        <div class="saved-editor-card">
                            <h4>Assistente IA</h4>
                            <div class="saved-editor-ai-review">
                                <div id="saved-editor-ai-review-status" class="saved-editor-ai-review__status">A analise interpretativa por IA (opcional).</div>
                                <div id="saved-editor-ai-review-summary" class="saved-editor-review__summary-text"></div>
                                <div id="saved-editor-ai-review-warnings" class="saved-editor-review__warnings"></div>
                                <div id="saved-editor-ai-review-findings" class="saved-editor-ai-review__findings">
                                    <div class="saved-editor-ai-review__empty">Nenhuma analise interpretativa foi executada ainda.</div>
                                </div>
                            </div>
                            <button type="button" id="saved-editor-ai-review-button" class="button secondary">Analisar com IA</button>
                        </div>

                        <div class="saved-editor-card">
                            <h4>Dados da petição</h4>
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
        <div class="saved-editor-review-hidden" aria-hidden="true">
            <div id="saved-editor-review" class="saved-editor-review">
                <div class="saved-editor-review__summary">
                    <div class="saved-editor-review__badge-row">
                        <span id="saved-editor-review-score" class="saved-editor-review__badge">Sem analise</span>
                        <span id="saved-editor-review-count" class="saved-editor-review__badge">0 achados</span>
                    </div>
                    <div id="saved-editor-review-summary" class="saved-editor-review__summary-text"></div>
                </div>
                <div id="saved-editor-review-warnings" class="saved-editor-review__warnings"></div>
                <div id="saved-editor-review-issues" class="saved-editor-review__issues"></div>
            </div>
        </div>
        <div id="saved-editor-review-popover" class="saved-editor-review-popover"></div>
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

    var ckfinderBaseUrl = @json(asset('ckfinder/'));
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
    var reviewPopover = document.getElementById('saved-editor-review-popover');
    var aiReviewButton = document.getElementById('saved-editor-ai-review-button');
    var aiReviewStatus = document.getElementById('saved-editor-ai-review-status');
    var aiReviewSummary = document.getElementById('saved-editor-ai-review-summary');
    var aiReviewWarnings = document.getElementById('saved-editor-ai-review-warnings');
    var aiReviewFindings = document.getElementById('saved-editor-ai-review-findings');
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
    var isAiReviewRunning = false;
    var latestReviewResult = null;
    var reviewStylesInjected = false;
    var activePopoverIssueIndex = null;

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

        editor.document.on('click', function (event) {
            var target = event.data && event.data.getTarget ? event.data.getTarget().$ : null;
            var highlight = target && typeof target.closest === 'function'
                ? target.closest('span[data-review-highlight="1"]')
                : null;

            if (!highlight) {
                hideReviewPopover();
                return;
            }

            var issueIndex = highlight.getAttribute('data-review-issue-index');
            if (issueIndex === null) {
                hideReviewPopover();
                return;
            }

            focusReviewHighlight(issueIndex);
            showReviewPopover(issueIndex, highlight);
            event.data.preventDefault();
        });

        window.setTimeout(function () {
            requestReview({
                statusLabel: 'Analisando automaticamente o texto...'
            }).catch(function () {
            });
        }, 250);

    });

    editor.on('change', function () {
        hideReviewPopover();
        markDirty();
    });
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
            hideReviewPopover();
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

    function hideReviewPopover() {
        activePopoverIssueIndex = null;

        if (!reviewPopover) {
            return;
        }

        reviewPopover.classList.remove('is-visible');
        reviewPopover.innerHTML = '';
    }

    function getIssueByIndex(issueIndex) {
        if (!latestReviewResult || !Array.isArray(latestReviewResult.issues)) {
            return null;
        }

        return latestReviewResult.issues[issueIndex] || null;
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
            + '.review-highlight{padding:0 1px;border-radius:2px;text-decoration-line:underline;text-decoration-thickness:2px;text-underline-offset:2px;text-decoration-skip-ink:none;}'
            + '.review-highlight--alta{background:rgba(255,217,226,.45);color:inherit;text-decoration-style:wavy;text-decoration-color:#d64545;box-shadow:inset 0 -1px 0 rgba(214,69,69,.18);}'
            + '.review-highlight--media{background:rgba(255,247,214,.55);color:inherit;text-decoration-style:wavy;text-decoration-color:#d9a200;box-shadow:inset 0 -1px 0 rgba(217,162,0,.18);}'
            + '.review-highlight--baixa{background:rgba(235,245,255,.7);color:inherit;text-decoration-style:solid;text-decoration-color:#2f80ed;box-shadow:inset 0 -1px 0 rgba(47,128,237,.18);}'
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

    function normalizeReviewText(text) {
        return String(text || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\r\n|\r|\n/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
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

    function extractEditorReviewText() {
        var textIndex = collectEditorTextNodes();

        return normalizeReviewText(textIndex.rawText || '');
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
            if (typeof issue.offset === 'number' && typeof issue.length === 'number' && issue.length > 0) {
                var normalizedStart = issue.offset;
                var normalizedEnd = issue.offset + issue.length - 1;
                var mappedStart = normalizedText.map[normalizedStart] || null;
                var mappedEnd = normalizedText.map[normalizedEnd] || null;

                if (!mappedStart || !mappedEnd) {
                    return;
                }

                var directStartPosition = locateTextPosition(textIndex.nodes, mappedStart.start);
                var directEndPosition = locateTextPosition(textIndex.nodes, mappedEnd.end);

                if (!directStartPosition || !directEndPosition) {
                    return;
                }

                matches.push({
                    issueIndex: issueIndex,
                    severity: String(issue.severity || 'baixa').toLowerCase(),
                    rawStart: mappedStart.start,
                    rawEnd: mappedEnd.end,
                    textNodes: textIndex.nodes,
                    startNodeIndex: directStartPosition.nodeIndex,
                    endNodeIndex: directEndPosition.nodeIndex,
                    startNode: directStartPosition.node,
                    startOffset: directStartPosition.offset,
                    endNode: directEndPosition.node,
                    endOffset: directEndPosition.offset
                });

                return;
            }

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

    function issueSupportsDictionaryAction(issue) {
        return !!(issue && issue.snippet && !/\s/u.test(String(issue.snippet).trim()));
    }

    function saveLanguageToolPreference(payload) {
        return window.fetch(@json(route('peticoes.saved.review.preferences.store', $peticao)), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
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
                throw new Error(result.data && result.data.message ? result.data.message : 'Falha ao salvar a preferencia do LanguageTool.');
            }

            return result.data;
        });
    }

    function showReviewPopover(issueIndex, anchorNode) {
        var issue = getIssueByIndex(issueIndex);
        if (!issue || !reviewPopover || !anchorNode) {
            return;
        }

        activePopoverIssueIndex = issueIndex;

        var suggestionButtons = '';
        var replacements = Array.isArray(issue.replacements) ? issue.replacements.slice(0, 3) : [];
        replacements.forEach(function (replacement, replacementIndex) {
            suggestionButtons += '<button type="button" class="saved-editor-review-popover__action" data-review-popover-action="apply" data-review-popover-index="' + String(issueIndex) + '" data-review-popover-replacement="' + escapeHtml(replacement) + '">' + (replacementIndex === 0 ? 'Aplicar: ' : 'Trocar por: ') + escapeHtml(replacement) + '</button>';
        });

        reviewPopover.innerHTML = ''
            + '<div class="saved-editor-review-popover__title">' + escapeHtml(issue.category || 'Revisao') + '</div>'
            + (issue.snippet ? '<div class="saved-editor-review-popover__snippet">' + escapeHtml(issue.snippet) + '</div>' : '')
            + '<div class="saved-editor-review-popover__message">' + escapeHtml(issue.message || '') + '</div>'
            + '<div class="saved-editor-review-popover__actions">'
            + suggestionButtons
            + '<button type="button" class="saved-editor-review-popover__action" data-review-popover-action="ignore" data-review-popover-index="' + String(issueIndex) + '">Ignorar este apontamento</button>'
            + (issueSupportsDictionaryAction(issue)
                ? '<button type="button" class="saved-editor-review-popover__action" data-review-popover-action="dictionary" data-review-popover-index="' + String(issueIndex) + '">Adicionar "' + escapeHtml(issue.snippet) + '" ao dicionario</button>'
                : '')
            + '<button type="button" class="saved-editor-review-popover__action" data-review-popover-action="close">Fechar</button>'
            + '</div>';

        var iframe = editor.container && editor.container.$
            ? editor.container.$.querySelector('.cke_wysiwyg_frame, iframe')
            : null;
        var frameRect = iframe ? iframe.getBoundingClientRect() : { left: 0, top: 0 };
        var anchorRect = anchorNode.getBoundingClientRect();

        reviewPopover.style.left = Math.max(12, frameRect.left + anchorRect.left + window.pageXOffset) + 'px';
        reviewPopover.style.top = (frameRect.top + anchorRect.bottom + window.pageYOffset + 8) + 'px';
        reviewPopover.classList.add('is-visible');
    }

    function applyIssueSuggestion(issueIndex, forcedReplacement) {
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

        var replacementText = forcedReplacement !== undefined
            ? String(forcedReplacement || '')
            : String(replacementRule.replacement || '');

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
        hideReviewPopover();
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
        var replacements = Array.isArray(issue && issue.replacements) ? issue.replacements.filter(function (value) {
            return String(value || '').trim() !== '';
        }) : [];
        if (replacements.length > 0) {
            return {
                autoApplicable: true,
                replacement: String(replacements[0]),
                action: 'replace'
            };
        }

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
                reviewIssues.innerHTML = '<div class="saved-editor-review__empty">Nenhum ponto ortografico ou gramatical foi encontrado nesta revisao.</div>';
            } else {
                reviewIssues.innerHTML = issues.map(function (issue, issueIndex) {
                    var severityClass = (issue.severity || 'baixa') === 'alta'
                        ? 'is-high'
                        : ((issue.severity || 'baixa') === 'media' ? 'is-medium' : 'is-low');
                    var actionMarkup = '';

                    if ((issue.snippet || issue.length) && canAutoApplyIssue(issue)) {
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
        formData.append('plain_text', extractEditorReviewText());

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

    function setAiReviewRunningState(running, label) {
        isAiReviewRunning = running;

        if (aiReviewButton) {
            aiReviewButton.disabled = running;
            aiReviewButton.textContent = running ? (label || 'Analisando...') : 'Analisar com IA';
        }

        if (aiReviewStatus) {
            aiReviewStatus.textContent = label || (running ? 'Analisando texto com IA...' : aiReviewStatus.textContent);
        }
    }

    function renderAiReview(result) {
        var findings = Array.isArray(result.findings) ? result.findings : [];

        if (aiReviewStatus) {
            aiReviewStatus.textContent = 'Ultima analise IA: ' + new Date().toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        if (aiReviewSummary) {
            aiReviewSummary.textContent = result.summary || '';
        }

        if (aiReviewWarnings) {
            var warnings = Array.isArray(result.warnings) ? result.warnings : [];
            if (!warnings.length) {
                aiReviewWarnings.innerHTML = '';
            } else {
                aiReviewWarnings.innerHTML = warnings.map(function (warning) {
                    return '<div class="saved-editor-review__warning">' + escapeHtml(warning) + '</div>';
                }).join('');
            }
        }

        if (aiReviewFindings) {
            if (!findings.length) {
                aiReviewFindings.innerHTML = '<div class="saved-editor-ai-review__empty">Nenhum achado interpretativo relevante foi encontrado nesta analise.</div>';
            } else {
                aiReviewFindings.innerHTML = findings.map(function (finding) {
                    var severityClass = (finding.severity || 'media') === 'alta'
                        ? 'is-high'
                        : ((finding.severity || 'media') === 'baixa' ? 'is-low' : 'is-medium');

                    return ''
                        + '<div class="saved-editor-ai-review__finding">'
                        + '  <div class="saved-editor-ai-review__finding-header">'
                        + '      <span class="saved-editor-ai-review__finding-title">' + escapeHtml(finding.title || 'Achado interpretativo') + '</span>'
                        + '      <span class="saved-editor-ai-review__finding-severity ' + severityClass + '">' + escapeHtml(finding.severity || 'media') + '</span>'
                        + '  </div>'
                        + (finding.snippet ? '<pre class="saved-editor-ai-review__finding-snippet">' + escapeHtml(finding.snippet) + '</pre>' : '')
                        + '  <div class="saved-editor-ai-review__finding-text">' + escapeHtml(finding.message || '') + '</div>'
                        + (finding.recommendation ? '<div class="saved-editor-ai-review__finding-recommendation"><strong>Recomendacao:</strong> ' + escapeHtml(finding.recommendation) + '</div>' : '')
                        + '</div>';
                }).join('');
            }
        }
    }

    function requestAiReview() {
        syncEditor();

        if (isAiReviewRunning) {
            return Promise.reject(new Error('A analise interpretativa por IA ainda esta em andamento.'));
        }

        setAiReviewRunningState(true, 'Analisando texto com IA...');

        var formData = new FormData();
        formData.append('cod_pecas', textarea.value);
        formData.append('plain_text', extractEditorReviewText());

        return window.fetch(@json(route('peticoes.saved.review.ai', $peticao)), {
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
                throw new Error(result.data && result.data.message ? result.data.message : 'Falha ao executar a analise por IA.');
            }

            renderAiReview(result.data);
            setAiReviewRunningState(false);

            return result.data;
        }).catch(function (error) {
            setAiReviewRunningState(false);
            if (aiReviewStatus) {
                aiReviewStatus.textContent = error && error.message ? error.message : 'Falha na analise interpretativa por IA.';
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
                statusLabel: 'Revisando novamente o texto...'
            }).catch(function (error) {
                window.alert(error && error.message ? error.message : 'Falha ao revisar o texto.');
            });
        });
    }

    if (aiReviewButton) {
        aiReviewButton.addEventListener('click', function () {
            requestAiReview().catch(function (error) {
                window.alert(error && error.message ? error.message : 'Falha ao executar a analise interpretativa por IA.');
            });
        });
    }

    if (reviewPopover) {
        reviewPopover.addEventListener('click', function (event) {
            var button = event.target.closest('[data-review-popover-action]');
            if (!button) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var action = button.getAttribute('data-review-popover-action');
            var issueIndex = button.getAttribute('data-review-popover-index');
            var issue = issueIndex !== null ? getIssueByIndex(issueIndex) : null;

            if (action === 'close') {
                hideReviewPopover();
                return;
            }

            if (!issue) {
                hideReviewPopover();
                return;
            }

            if (action === 'apply') {
                applyIssueSuggestion(issueIndex, button.getAttribute('data-review-popover-replacement') || '');
                return;
            }

            if (action === 'ignore') {
                saveLanguageToolPreference({
                    entry_type: 'ignored_match',
                    token: issue.snippet || '',
                    rule_id: issue.rule_id || ''
                }).then(function () {
                    hideReviewPopover();
                    return requestReview({
                        statusLabel: 'Atualizando revisao automatica...'
                    });
                }).catch(function (error) {
                    window.alert(error && error.message ? error.message : 'Falha ao salvar a preferencia.');
                });
                return;
            }

            if (action === 'dictionary') {
                saveLanguageToolPreference({
                    entry_type: 'dictionary_word',
                    token: issue.snippet || ''
                }).then(function () {
                    hideReviewPopover();
                    return requestReview({
                        statusLabel: 'Atualizando dicionario do usuario...'
                    });
                }).catch(function (error) {
                    window.alert(error && error.message ? error.message : 'Falha ao atualizar o dicionario.');
                });
            }
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

    Array.prototype.forEach.call(document.querySelectorAll('.js-saved-export-form'), function (form) {
        form.addEventListener('submit', function () {
            isSubmitting = true;
            isDirty = false;
            hideReviewPopover();
            syncEditor();
            form.querySelector('textarea[name="cod_pecas"]').value = textarea.value;
            form.querySelector('input[name="nome_cli"]').value = document.getElementById('saved_editor_nome_cli').value;
        });
    });

    document.addEventListener('click', function (event) {
        if (!reviewPopover || !reviewPopover.classList.contains('is-visible')) {
            return;
        }

        if (reviewPopover.contains(event.target)) {
            return;
        }

        hideReviewPopover();
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
