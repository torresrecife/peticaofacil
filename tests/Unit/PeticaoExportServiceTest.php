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
}
