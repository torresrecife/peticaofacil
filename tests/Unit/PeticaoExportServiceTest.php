<?php

namespace Tests\Unit;

use App\Services\PeticaoExportService;
use RuntimeException;
use Tests\TestCase;

class PeticaoExportServiceTest extends TestCase
{
    public function test_pdf_binary_normalization_removes_utf8_bom_before_signature()
    {
        $service = new class extends PeticaoExportService {
            public function normalizeForTest($content)
            {
                return $this->normalizePdfBinary($content);
            }
        };

        $content = $service->normalizeForTest("\xEF\xBB\xBF%PDF-1.4\n%%EOF");

        $this->assertSame("%PDF-1.4\n%%EOF", $content);
    }

    public function test_pdf_binary_normalization_rejects_non_pdf_content()
    {
        $service = new class extends PeticaoExportService {
            public function normalizeForTest($content)
            {
                return $this->normalizePdfBinary($content);
            }
        };

        $this->expectException(RuntimeException::class);
        $service->normalizeForTest('conteudo de texto');
    }

    public function test_document_export_appends_legacy_user_identification_once_and_escapes_login()
    {
        $service = new class extends PeticaoExportService {
            public function appendExporterForTest($html, $login)
            {
                return $this->appendExporterIdentification($html, $login);
            }
        };

        $content = $service->appendExporterForTest('<p>Peticao</p>', 'fabio<script>');
        $content = $service->appendExporterForTest($content, 'outro');

        $this->assertStringContainsString('class="peticao-exporter-identification"', $content);
        $this->assertStringContainsString('align="right"', $content);
        $this->assertStringContainsString('color:#ccc', $content);
        $this->assertStringContainsString('font-style:italic', $content);
        $this->assertStringContainsString('fabio&lt;script&gt;', $content);
        $this->assertSame(1, substr_count($content, 'peticao-exporter-identification'));
        $this->assertStringNotContainsString('outro', $content);
    }

    public function test_docx_native_styles_preserve_exporter_gray_italic_formatting()
    {
        $service = new class extends PeticaoExportService {
            public function docxTextStyleForTest($html)
            {
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                return $this->extractDocxTextStyle($dom->documentElement);
            }
        };

        $style = $service->docxTextStyleForTest(
            '<div style="text-align:right;color:#ccc;font-style:italic;">fabio</div>'
        );

        $this->assertTrue($style['font']['italic']);
        $this->assertSame('cccccc', $style['font']['color']);
    }

    public function test_logo_header_embedded_in_the_body_is_removed_once()
    {
        $service = new class extends PeticaoExportService {
            public function stripForTest($body, $header)
            {
                return $this->stripEmbeddedHeaderFooter($body, $header);
            }
        };
        // Cabecalho dos modelos VOLKSWAGEN: so o logotipo. O corpo chega saneado.
        $header = '<div style="text-align:right"><img alt="" src="/peticaofacil/img/cabecalho.jpg" style="height:87px; width:196px" /></div>';
        $body = '<div style="text-align:right"><img alt="" src="/peticaofacil/img/cabecalho.jpg" style="height:87px; width:196px"></div><p>EXCELENTISSIMO</p>';

        $this->assertSame('<p>EXCELENTISSIMO</p>', $service->stripForTest($body, $header));
        // Imagem diferente no inicio do corpo continua.
        $other = '<p><img src="/outra.jpg"></p><p>Texto</p>';
        $this->assertSame($other, $service->stripForTest($other, $header));
    }

    public function test_inline_images_saved_with_another_install_prefix_are_found_in_public()
    {
        $service = new class extends PeticaoExportService {
            public function normalizeForTest($html)
            {
                return $this->normalizeAssetImageSrc($html, 'inline');
            }
        };

        foreach (['http://peticaofacil.ddev.site:8080', 'http://10.81.11.202/peticaofacil2'] as $appUrl) {
            config(['app.url' => $appUrl]);
            foreach (['/peticaofacil/img/fundo.jpg', '/peticaofacil2/img/fundo.jpg', '/peticaofacil2/public/img/fundo.jpg', '/img/fundo.jpg'] as $src) {
                $html = $service->normalizeForTest('<img src="' . $src . '">');
                $this->assertStringStartsWith('<img src="data:image/jpeg;base64,', $html, $appUrl . ' ' . $src);
            }
        }

        // Arquivo que nao existe continua com o endereco original.
        $this->assertSame('<img src="/peticaofacil/img/nao-existe.jpg">', $service->normalizeForTest('<img src="/peticaofacil/img/nao-existe.jpg">'));
    }

    public function test_word_export_keeps_numbered_lists_and_blocks_inside_divs()
    {
        $service = new class extends PeticaoExportService {
            public function documentXmlForTest($html)
            {
                $docx = $this->renderWordDocumentFromLayout(['title' => 't', 'body_html' => $html, 'header_html' => '', 'footer_html' => '']);
                $path = tempnam(sys_get_temp_dir(), 'docx');
                file_put_contents($path, $docx);
                $zip = new \ZipArchive();
                $zip->open($path);
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                @unlink($path);

                return $xml;
            }
        };

        $xml = $service->documentXmlForTest(
            '<p>Antes</p><ol start="3"><li><p>Item tres</p></li><li>Item quatro</li></ol><ol type="i"><li>romano</li></ol>'
            . '<ul><li>bala</li></ul><div class="peticao-notas"><hr><p>Nota um</p><p>Nota dois</p></div><p>Depois</p>'
        );
        preg_match_all('/<w:p[ >].*?<\/w:p>/s', $xml, $paragraphs);
        $texts = array_values(array_filter(array_map(function ($paragraph) {
            preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $paragraph, $runs);

            return trim(preg_replace('/\s+/', ' ', implode('', $runs[1])));
        }, $paragraphs[0])));

        $this->assertSame(['Antes', '3. Item tres', '4. Item quatro', 'i. romano', '• bala', 'Nota um', 'Nota dois', 'Depois'], $texts);
        $this->assertStringContainsString('w:hanging="360"', $xml);
        $this->assertStringContainsString('<w:bottom', $xml); // linha (hr) antes das notas
    }
}
