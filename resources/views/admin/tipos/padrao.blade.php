@extends('layouts.app')

@section('title', 'Cabecalho padrao dos modelos')

@push('head')
@include('partials.ckeditor')
<script src="{{ asset('ckfinder/ckfinder.js') }}"></script>
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Cabeçalho e rodapé padrão dos modelos</h2>
    <div class="actions">
        <a class="button secondary link" href="{{ route('admin.modelos-normalizados.index') }}">Voltar para modelos</a>
    </div>
</div>

<div class="panel">
    <div class="panel-muted" style="margin-bottom:16px;">
        <strong>Aplicados automaticamente em todo modelo novo</strong>
        <div class="editor-note">Valem para o modelo criado em "Novo modelo" (já vem preenchido e pode ser trocado lá) e para o importado do Word com IA. Modelos que já existem não mudam.</div>
    </div>

    <form method="post" action="{{ route('admin.modelos-normalizados.padrao.update') }}">
        @csrf
        @method('put')
        <div class="form-grid">
            <div class="form-group full">
                <label>Cabeçalho padrão</label>
                <textarea class="js-rich-editor" name="cod_cabec">{{ old('cod_cabec', $cabecalho) }}</textarea>
            </div>
            <div class="form-group full">
                <label>Rodapé padrão (opcional)</label>
                <textarea class="js-rich-editor" name="cod_rodap">{{ old('cod_rodap', $rodape) }}</textarea>
            </div>
        </div>

        <div class="actions" style="margin-top:16px;">
            <button type="submit">Salvar</button>
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

    var ckfinderBaseUrl = @json(asset('ckfinder/'));
    Array.prototype.forEach.call(document.querySelectorAll('.js-rich-editor'), function (textarea, index) {
        if (!textarea.id) {
            textarea.id = 'modelo_padrao_editor_' + index;
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
