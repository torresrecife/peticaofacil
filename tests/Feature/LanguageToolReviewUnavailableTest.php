<?php

namespace Tests\Feature;

use App\Services\LanguageToolClient;
use App\Services\PeticaoSavedReviewService;
use Tests\TestCase;

class LanguageToolReviewUnavailableTest extends TestCase
{
    private function reviewServiceWith($behavior)
    {
        $client = new class($behavior) extends LanguageToolClient {
            private $behavior;

            public function __construct($behavior)
            {
                $this->behavior = $behavior;
            }

            public function isEnabled()
            {
                return $this->behavior !== 'disabled';
            }

            public function check($text, array $options = [])
            {
                if ($this->behavior === 'disabled') {
                    // Usa o caminho real: desabilitado devolve languagetool_not_enabled,
                    // sem tocar na rede.
                    return parent::check($text, $options);
                }

                if ($this->behavior === 'transport') {
                    return [
                        'ok' => false,
                        'error' => 'Nao foi possivel conectar ao servidor de revisao (LanguageTool).',
                        'error_code' => 'http_transport_error',
                        'data' => null,
                    ];
                }

                return [
                    'ok' => true,
                    'error' => null,
                    'error_code' => null,
                    'data' => [
                        'matches' => [
                            [
                                'offset' => 0,
                                'length' => 4,
                                'message' => 'Erro de teste.',
                                'replacements' => [['value' => 'Teste']],
                                'rule' => [
                                    'id' => 'TESTE_RULE',
                                    'issueType' => 'misspelling',
                                    'category' => ['id' => 'TYPOS', 'name' => 'Ortografia'],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        return new PeticaoSavedReviewService($client);
    }

    public function test_disabled_languagetool_reports_unavailable_without_network()
    {
        $result = $this->reviewServiceWith('disabled')->review('<p>Teste do documento.</p>', null, null);

        $this->assertFalse($result['available']);
        $this->assertSame('languagetool', $result['mode']);
        $this->assertStringContainsStringIgnoringCase('nao esta ativa', $result['summary']);
        $this->assertSame([], $result['issues']);
        $this->assertSame([], $result['warnings']);
        $this->assertNull($result['score']);
    }

    public function test_server_down_reports_unavailable_with_warning()
    {
        $result = $this->reviewServiceWith('transport')->review('<p>Teste do documento.</p>', null, null);

        $this->assertFalse($result['available']);
        $this->assertStringContainsStringIgnoringCase('indisponivel', $result['summary']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertSame([], $result['issues']);
    }

    public function test_successful_review_is_available_with_issues()
    {
        $result = $this->reviewServiceWith('ok')->review('<p>Teste do documento.</p>', null, null);

        $this->assertTrue($result['available']);
        $this->assertCount(1, $result['issues']);
        $this->assertLessThan(100, $result['score']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_empty_document_is_available()
    {
        $result = $this->reviewServiceWith('ok')->review('<p>   </p>', null, null);

        $this->assertTrue($result['available']);
        $this->assertSame(100, $result['score']);
        $this->assertSame([], $result['issues']);
    }
}
