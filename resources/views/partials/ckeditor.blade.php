{{--
    CKEditor com carimbo de versao que acompanha os nossos arquivos dele
    (contents.css e config.js). O CKEditor pede esses arquivos com ?t=carimbo;
    sem mudar o carimbo, o navegador continua usando a versao antiga em cache.
--}}
@php
    $ckeditorVersion = max(
        @filemtime(public_path('ckeditor/contents.css')) ?: 0,
        @filemtime(public_path('ckeditor/config.js')) ?: 0
    );
@endphp
<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
<script>
    CKEDITOR.timestamp = 'v{{ $ckeditorVersion }}';
    // O endereco do contents.css e montado quando o ckeditor.js carrega, antes
    // do carimbo acima: define de novo, ja com a versao.
    CKEDITOR.config.contentsCss = CKEDITOR.basePath + 'contents.css?v={{ $ckeditorVersion }}';
</script>
