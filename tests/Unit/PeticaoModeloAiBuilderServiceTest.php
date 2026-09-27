<?php

namespace Tests\Unit;

use App\Services\OpenAIResponsesClient;
use App\Services\PeticaoModeloAiBuilderService;
use App\Services\WordImportService;
use PHPUnit\Framework\TestCase;

class PeticaoModeloAiBuilderServiceTest extends TestCase
{
    public function test_analysis_is_normalized_to_runtime_tokens_and_ordered_fields()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function normalizeForTest(array $data) { return $this->normalize($data); }
        };
        $result = $service->normalizeForTest([
            'nome' => 'Cobranca', 'descricao' => 'Acao', 'cabecalho_html' => '', 'rodape_html' => '',
            'campos' => [
                ['rotulo' => 'Autor', 'token' => '@VAR_AUTOR@', 'tipo' => 'TEXT', 'obrigatorio' => true, 'opcoes' => []],
                ['rotulo' => 'Tipo', 'token' => '@VAR_TIPO@', 'tipo' => 'SELECT', 'obrigatorio' => false, 'opcoes' => ['A']],
                ['rotulo' => 'Telefone profissional do advogado', 'token' => '@VAR_FONE@', 'tipo' => 'TEXT', 'obrigatorio' => false, 'opcoes' => []],
            ],
            'paragrafos' => [['titulo' => 'Fatos', 'conteudo_html' => '<p>@VAR_AUTOR@ escolheu @VAR_TIPO@.</p>']],
        ]);
        $this->assertSame('@campo1@', $result['campos'][0]['token']);
        $this->assertSame('@campo2@', $result['campos'][1]['token']);
        $this->assertStringContainsString('@campo1@', $result['paragrafos'][0]['conteudo_html']);
        $this->assertStringContainsString('@campo2@', $result['paragrafos'][0]['conteudo_html']);
        $this->assertCount(2, $result['campos']);
    }
}
