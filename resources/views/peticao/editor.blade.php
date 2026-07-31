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
        <div class="section-title">
            <h3>{{ $modelo->tipo_nome }}</h3>
            <div class="editor-note">Edicao final, salvamento e exportacao no fluxo novo.</div>
        </div>

        <form method="post" action="{{ $saveRoute }}" id="editor-save-form">
            @csrf
            <input type="hidden" name="codigo_processo" value="{{ old('codigo_processo', $codigoProcesso ?? '') }}">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nome do arquivo / cliente</label>
                    <input name="nome_cli" value="{{ old('nome_cli', $nomeCli) }}" required>
                </div>
                <div class="form-group full">
                    <label>Conteudo da peca</label>
                    <textarea id="editor_content" name="cod_pecas" class="js-rich-editor" style="min-height:480px;">{{ old('cod_pecas', $content) }}</textarea>
                </div>
            </div>
            <div class="actions" style="margin-top:16px;">
                <button type="submit">Salvar peca</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="section-title">
            <h3>Exportacao</h3>
            <div class="editor-note">Usa o conteudo atual do editor.</div>
        </div>
        <div class="actions">
            <form method="post" action="{{ $wordRoute }}" class="js-export-form">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $nomeCli) }}">
                <textarea name="cod_pecas" style="display:none;">{{ old('cod_pecas', $content) }}</textarea>
                <button type="submit">Exportar Word</button>
            </form>
            <form method="post" action="{{ $pdfRoute }}" class="js-export-form">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ old('nome_cli', $nomeCli) }}">
                <textarea name="cod_pecas" style="display:none;">{{ old('cod_pecas', $content) }}</textarea>
                <button type="submit">Exportar PDF</button>
            </form>
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
    var textarea = document.getElementById('editor_content');
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

    var saveForm = document.getElementById('editor-save-form');
    if (saveForm) {
        saveForm.addEventListener('submit', syncEditor);
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-export-form'), function (form) {
        form.addEventListener('submit', function () {
            syncEditor();
            var html = textarea.value;
            form.querySelector('textarea[name="cod_pecas"]').value = html;
            form.querySelector('input[name="nome_cli"]').value = document.querySelector('#editor-save-form input[name="nome_cli"]').value;
        });
    });
});
</script>
@endpush
