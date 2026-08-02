@extends('layouts.app')

@section('title', 'Configurar peticao avulsa')

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
    <h2 style="margin:0;">Configurar peticao avulsa</h2>
    <div class="actions">
        <a class="button secondary link" href="{{ route('peticoes.avulsas.create') }}">Voltar para criacao avulsa</a>
    </div>
</div>

<div class="panel">
    <div class="panel-muted" style="margin-bottom:16px;">
        <strong>Cabecalho e rodape padrao</strong>
        <div class="editor-note">Esses blocos entram automaticamente quando uma peticao avulsa e criada.</div>
        <div class="editor-note" style="margin-top:8px;">Placeholders disponiveis: <code>@TIPO_PETICAO@</code>, <code>@PARTE_CONTRARIA@</code>, <code>@CODIGO_PROCESSO@</code>, <code>@DATA_ATUAL@</code>.</div>
    </div>

    <form method="post" action="{{ route('admin.peticoes-avulsas.config.update') }}">
        @csrf
        @method('put')
        <div class="form-grid">
            <div class="form-group full">
                <label>Cabecalho padrao</label>
                <textarea class="js-rich-editor" name="cod_cabec">{{ old('cod_cabec', $modelo->cabecalho_html) }}</textarea>
            </div>
            <div class="form-group full">
                <label>Rodape padrao</label>
                <textarea class="js-rich-editor" name="cod_rodap">{{ old('cod_rodap', $modelo->rodape_html) }}</textarea>
            </div>
        </div>

        <div class="actions" style="margin-top:16px;">
            <button type="submit">Salvar configuracao</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.CKEDITOR === 'undefined') {
        return;
    }

    var ckfinderBaseUrl = @json(config('legacy.ckfinder_base_url'));
    Array.prototype.forEach.call(document.querySelectorAll('.js-rich-editor'), function (textarea, index) {
        if (!textarea.id) {
            textarea.id = 'avulsa_editor_' + index;
        }

        if (CKEDITOR.instances[textarea.id]) {
            CKEDITOR.instances[textarea.id].destroy(true);
        }

        var instance = CKEDITOR.replace(textarea.id, {
            height: 220,
            allowedContent: true
        });

        if (window.CKFinder && ckfinderBaseUrl) {
            CKFinder.setupCKEditor(instance, ckfinderBaseUrl);
        }
    });
});
</script>
@endpush
