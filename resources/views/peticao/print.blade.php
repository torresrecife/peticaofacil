<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle }}</title>
    @if(!empty($editorCss))
        <style>{!! $editorCss !!}</style>
    @endif
    @if(!empty($printCss))
        <style>{!! $printCss !!}</style>
    @endif
</head>
<body>
    <div class="peticao-print-shell">
        <main class="peticao-print-sheet">
            <div class="peticao-print-content">
                @if(!empty($headerHtml))
                    <header class="peticao-print-header">
                        {!! $headerHtml !!}
                    </header>
                @endif

                <div class="peticao-print-body">
                    {!! $documentHtml !!}
                </div>

                @if(!empty($footerHtml))
                    <footer class="peticao-print-footer">
                        {!! $footerHtml !!}
                    </footer>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
