<?php

namespace App\Services;

use App\PeticaoModelo;

class PeticaoAssistantAiService
{
    protected $client;

    public function __construct(OpenAIResponsesClient $client)
    {
        $this->client = $client;
    }

    public function enrich(array $state, $lastUserMessage = '')
    {
        if (!$this->client->isEnabled()) {
            return $this->fallback($state);
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $this->buildContextPrompt($state, $lastUserMessage),
            ],
        ];

        $response = $this->client->createStructuredResponse($messages, $this->schema(), 'peticao_assistant_turn');
        if (!$response['ok']) {
            return $this->fallback($state, $response['error']);
        }

        $data = $response['data'];

        return [
            'mode' => 'openai',
            'assistant_message' => trim((string) ($data['assistant_message'] ?? '')),
            'questions' => array_values(array_filter($data['questions'] ?? [])),
            'missing_fields' => array_values(array_filter($data['missing_fields'] ?? [])),
            'consistency_checks' => array_values(array_filter($data['consistency_checks'] ?? [])),
            'model_rationale' => trim((string) ($data['model_rationale'] ?? '')),
            'warnings' => array_values(array_filter($data['warnings'] ?? [])),
        ];
    }

    protected function fallback(array $state, $error = null)
    {
        $questions = [];
        $missingFields = $state['missing_fields'] ?? [];
        $checks = $state['consistency_checks'] ?? [];

        if (!empty($state['duplicate_petitions'])) {
            $checks[] = 'Ja existe peticao salva com o mesmo codigo de processo. Revise antes de gerar nova minuta.';
        }

        if (!empty($state['selected_model_id']) && !empty($missingFields)) {
            $questions[] = 'Antes de seguir, preciso confirmar os campos faltantes do modelo selecionado.';
        } elseif (!empty($state['process_code']) && empty($state['selected_model_id'])) {
            $questions[] = 'Qual peticao voce quer elaborar para esse processo?';
        }

        $message = 'Analise preliminar pronta.';
        if (!empty($state['selected_model_name'])) {
            $message .= ' Modelo atual: ' . $state['selected_model_name'] . '.';
        }
        if (!empty($missingFields)) {
            $message .= ' Ainda faltam dados obrigatorios para fechar a minuta.';
        }
        if ($error) {
            $message .= ' A camada OpenAI nao respondeu; mantive a orientacao local do sistema.';
        }

        return [
            'mode' => 'fallback',
            'assistant_message' => $message,
            'questions' => $questions,
            'missing_fields' => array_values(array_unique($missingFields)),
            'consistency_checks' => array_values(array_unique($checks)),
            'model_rationale' => !empty($state['selected_model_name'])
                ? 'Modelo escolhido manualmente pelo usuario dentro das sugestoes disponiveis.'
                : '',
            'warnings' => $error ? [$error] : [],
        ];
    }

    protected function systemPrompt()
    {
        return implode("\n", [
            'Voce e um assistente juridico interno para montagem de peticoes.',
            'Seu papel e orientar a conversa, reduzir erro de digitacao e identificar faltantes antes da geracao da minuta.',
            'Nao invente fatos e nao use informacao fora do contexto recebido.',
            'Se houver risco de duplicidade ou inconsistencias, destaque isso explicitamente.',
            'Nao gere a peticao final aqui. Apenas conduza a coleta de dados e a escolha do modelo.',
            'Quando o modelo ja estiver escolhido, faca perguntas curtas e objetivas sobre os dados faltantes.',
        ]);
    }

    protected function buildContextPrompt(array $state, $lastUserMessage)
    {
        $payload = [
            'last_user_message' => $lastUserMessage,
            'process_code' => $state['process_code'],
            'selected_model_id' => $state['selected_model_id'],
            'selected_model_name' => $state['selected_model_name'],
            'process_data' => $state['process_data'],
            'model_suggestions' => $state['model_suggestions'],
            'duplicate_petitions' => $state['duplicate_petitions'],
            'missing_fields' => $state['missing_fields'] ?? [],
            'consistency_checks' => $state['consistency_checks'] ?? [],
            'required_model_fields' => $this->requiredFields($state),
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function requiredFields(array $state)
    {
        if (empty($state['selected_model_id'])) {
            return [];
        }

        $modelo = PeticaoModelo::with('campos')->find($state['selected_model_id']);
        if (!$modelo) {
            return [];
        }

        return $modelo->campos
            ->where('obrigatorio', true)
            ->map(function ($campo) {
                return [
                    'id' => $campo->id,
                    'legacy_input_id' => $campo->id_input,
                    'label' => $campo->rotulo,
                    'tipo' => $campo->tipo,
                    'origem_coluna' => $campo->origem_coluna,
                ];
            })
            ->values()
            ->all();
    }

    protected function schema()
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'assistant_message' => ['type' => 'string'],
                'questions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'missing_fields' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'consistency_checks' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'model_rationale' => ['type' => 'string'],
                'warnings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'assistant_message',
                'questions',
                'missing_fields',
                'consistency_checks',
                'model_rationale',
                'warnings',
            ],
        ];
    }
}
