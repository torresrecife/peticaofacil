<?php

namespace Tests\Feature;

use App\Services\OpenAIResponsesClient;
use App\Services\PeticaoAssistantAiService;
use Tests\TestCase;

class PeticaoAssistantOpenAIQuotaWarningTest extends TestCase
{
    public function test_assistant_falls_back_with_friendly_quota_warning()
    {
        $client = new class extends OpenAIResponsesClient
        {
            public function isEnabled()
            {
                return true;
            }

            public function createStructuredResponse(array $messages, array $schema, $schemaName = 'response_payload')
            {
                return [
                    'ok' => false,
                    'error' => 'A cota da OpenAI foi excedida. Revise billing, saldo ou limite de uso do projeto antes de continuar.',
                    'error_code' => 'quota_exceeded',
                    'data' => null,
                ];
            }
        };

        $service = new PeticaoAssistantAiService($client);

        $analysis = $service->enrich([
            'process_code' => '5001234-55.2026.8.26.0100',
            'selected_model_id' => 48,
            'selected_model_name' => 'SUBSTABELECIMENTO',
            'missing_fields' => ['CPF DO AUTOR'],
            'consistency_checks' => [],
            'duplicate_petitions' => [],
            'process_data' => [],
            'model_suggestions' => [],
        ], 'quero substabelecimento');

        $this->assertSame('fallback', $analysis['mode']);
        $this->assertContains('A cota da OpenAI foi excedida. Revise billing, saldo ou limite de uso do projeto antes de continuar.', $analysis['warnings']);
        $this->assertStringContainsString('A camada OpenAI nao respondeu', $analysis['assistant_message']);
    }
}
