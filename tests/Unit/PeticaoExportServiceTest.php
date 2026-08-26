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
}
