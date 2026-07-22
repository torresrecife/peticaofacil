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
        <div class="section-title">
            <h3>{{ optional($peticao->modelo)->nome ?: 'Peticao normalizada' }}</h3>
            <div class="editor-note">Entidade principal: `peticoes`.</div>
        </div>

        <div class="grid" style="margin-bottom:16px;">
            <div class="stat">
                <span>ID normalizado</span>
                <strong>{{ $peticao->id }}</strong>
            </div>
            <div class="stat">
                <span>ID legado</span>
                <strong>{{ $peticao->legacy_peca_id ?: '-' }}</strong>
            </div>
            <div class="stat">
                <span>Codigo</span>
                <strong style="font-size:18px;">{{ $peticao->codigo_externo ?: '-' }}</strong>
            </div>
            <div class="stat">
                <span>Ultimo salvamento</span>
                <strong style="font-size:18px;">{{ optional($peticao->salvo_em)->format('d/m/Y H:i') ?: '-' }}</strong>
            </div>
        </div>

        <form method="post" action="{{ route('peticoes.saved.update', $peticao) }}" id="saved-editor-form">
            @csrf
            @method('put')
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nome do arquivo / cliente</label>
                    <input name="nome_cli" value="{{ old('nome_cli', $peticao->cliente_referencia) }}" required>
                </div>
                <div class="form-group full">
                    <label>Conteudo da peticao</label>
                    <textarea id="saved_editor_content" name="cod_pecas" class="js-rich-editor" style="min-height:480px;">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
                </div>
            </div>
            <div class="actions" style="margin-top:16px;">
                <button type="submit">Salvar peticao</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="section-title">
            <h3>Exportacao e historico</h3>
            <div class="editor-note">Operando sobre a entidade normalizada.</div>
        </div>
        <div class="actions">
            <form method="post" action="{{ route('peticoes.saved.export.word', $peticao) }}" class="js-saved-export-form">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $peticao->cliente_referencia) }}">
                <textarea name="cod_pecas" style="display:none;">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
                <button type="submit">Exportar Word</button>
            </form>
            <form method="post" action="{{ route('peticoes.saved.export.pdf', $peticao) }}" class="js-saved-export-form">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $peticao->cliente_referencia) }}">
                <textarea name="cod_pecas" style="display:none;">{{ old('cod_pecas', $peticao->conteudo_html) }}</textarea>
                <button type="submit">Exportar PDF</button>
            </form>
            @if($peticao->legacyPeca)
                <a class="button secondary link" href="{{ route('peticoes.editor.edit', $peticao->legacyPeca) }}">Abrir versao legado</a>
            @endif
        </div>
        <div class="panel-muted" style="margin-top:16px;">
            <div><strong>Gerada em:</strong> {{ optional($peticao->gerado_em)->format('d/m/Y H:i') ?: '-' }}</div>
            <div><strong>Salva em:</strong> {{ optional($peticao->salvo_em)->format('d/m/Y H:i') ?: '-' }}</div>
            <div><strong>Usuario legado:</strong> {{ optional($peticao->legacyUsuario)->login_usu ?: '-' }}</div>
        </div>
        <div class="panel-muted" style="margin-top:16px;">
            <strong>Historico de versoes</strong>
            <form method="get" action="{{ route('peticoes.saved.edit', $peticao) }}" style="margin-top:12px;">
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
                                <option value="{{ $usuarioHistorico->id_usu }}" @if((string) ($selectedUserId ?? '') === (string) $usuarioHistorico->id_usu) selected @endif>{{ $usuarioHistorico->login_usu }}</option>
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
            <table style="margin-top:12px;">
                <thead>
                    <tr>
                        <th>Versao</th>
                        <th>Origem</th>
                        <th>Cliente</th>
                        <th>Usuario</th>
                        <th>Data</th>
                        <th>Legado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($versoes as $versao)
                        <tr>
                            <td>{{ $versao->versao_numero }}</td>
                            <td>{{ $versao->origem_snapshot }}</td>
                            <td>{{ $versao->cliente_referencia_snapshot }}</td>
                            <td>{{ optional($versao->legacyUsuario)->login_usu ?: '-' }}</td>
                            <td>{{ optional($versao->criado_em)->format('d/m/Y H:i') ?: optional($versao->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $versao->legacy_peca_id_snapshot ?: '-' }}</td>
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
                            <td colspan="7">Sem versoes registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination-wrap">
                {{ $versoes->links('vendor.pagination.default') }}
            </div>
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

    var editor = CKEDITOR.replace(textarea.id, {
        height: 540,
        allowedContent: true
    });

    if (window.CKFinder && ckfinderBaseUrl) {
        CKFinder.setupCKEditor(editor, ckfinderBaseUrl);
    }

    function syncEditor() {
        if (CKEDITOR.instances[textarea.id]) {
            CKEDITOR.instances[textarea.id].updateElement();
        }
    }

    var saveForm = document.getElementById('saved-editor-form');
    if (saveForm) {
        saveForm.addEventListener('submit', syncEditor);
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-saved-export-form'), function (form) {
        form.addEventListener('submit', function () {
            syncEditor();
            form.querySelector('textarea[name="cod_pecas"]').value = textarea.value;
            form.querySelector('input[name="nome_cli"]').value = document.querySelector('#saved-editor-form input[name="nome_cli"]').value;
        });
    });
});
</script>
@endpush
