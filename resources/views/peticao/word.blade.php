<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40"
      lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="ProgId" content="Word.Document">
    <meta name="Generator" content="Microsoft Word 15">
    <meta name="Originator" content="Microsoft Word 15">
    <title>{{ $documentTitle }}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    @if(!empty($editorCss))
        <style>{!! $editorCss !!}</style>
    @endif
    @if(!empty($wordCss))
        <style>{!! $wordCss !!}</style>
    @endif
</head>
<body>
    @if(!empty($headerHtml))
        <div style="mso-element:header" id="h1" class="WordSectionHeader">
            {!! $headerHtml !!}
        </div>
    @endif

    @if(!empty($footerHtml))
        <div style="mso-element:footer" id="f1" class="WordSectionFooter">
            {!! $footerHtml !!}
        </div>
    @endif

    <div class="Section1">
        <div class="peticao-word-sheet">
            {!! $documentHtml !!}
        </div>
    </div>
</body>
</html>
