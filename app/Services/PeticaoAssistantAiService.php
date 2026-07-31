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
            return $this->fallback($state, $response['error'], $response['error_code'] ?? null);
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
            'stage_guidance' => trim((string) ($data['stage_guidance'] ?? '')),
        ];
    }

    protected function fallback(array $state, $error = null, $errorCode = null)
    {
        $questions = [];
        $missingFields = $state['missing_fields'] ?? [];
        $checks = $state['consistency_checks'] ?? [];
        $currentPendingField = $state['current_pending_field'] ?? null;

        if (!empty($state['duplicate_petitions'])) {
            $checks[] = 'Ja existe peticao salva com o mesmo codigo de processo. Revise antes de gerar nova minuta.';
        }

        $stage = $state['conversation_stage'] ?? 'process_lookup';

        if ($stage === 'process_lookup') {
            $questions[] = 'Informe o codigo exato do processo.';
        } elseif ($stage === 'model_selection') {
            $questions[] = 'Qual peticao voce quer elaborar para esse processo?';
        } elseif ($stage === 'data_completion' && !empty($missingFields)) {
            $questions[] = $this->buildFieldQuestion($currentPendingField);
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
            'warning_code' => $errorCode,
            'stage_guidance' => $this->fallbackStageGuidance($stage, $missingFields),
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
            'Conduza a conversa por etapa: consulta do processo, escolha do modelo, confirmacao dos dados faltantes e handoff para a montagem.',
            'Quando o modelo ja estiver escolhido, faca perguntas curtas e objetivas sobre os dados faltantes.',
            'Evite perguntar mais de duas coisas por vez.',
            'Se a etapa atual for so escolher o modelo, nao antecipe perguntas detalhadas de preenchimento.',
            'Se houver current_pending_field, pergunte apenas por esse campo.',
            'Se o current_pending_field for SELECT e houver opcoes, apresente as opcoes numeradas de forma curta.',
        ]);
    }

    protected function buildContextPrompt(array $state, $lastUserMessage)
    {
        $payload = [
            'last_user_message' => $lastUserMessage,
            'process_code' => $state['process_code'],
            'selected_model_id' => $state['selected_model_id'],
            'selected_model_name' => $state['selected_model_name'],
            'conversation_stage' => $state['conversation_stage'] ?? 'process_lookup',
            'conversation_stage_label' => $state['conversation_stage_label'] ?? 'Consulta do processo',
            'current_pending_field' => $state['current_pending_field'] ?? null,
            'assistant_field_answers' => $state['assistant_field_answers'] ?? [],
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
                'stage_guidance' => ['type' => 'string'],
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
                'stage_guidance',
                'warnings',
            ],
        ];
    }

    protected function fallbackStageGuidance($stage, array $missingFields)
    {
        if ($stage === 'process_lookup') {
            return 'Primeiro confirme o numero do processo para eu consultar os dados do caso.';
        }

        if ($stage === 'model_selection') {
            return 'Agora escolha ou descreva a peticao que voce quer elaborar.';
        }

        if ($stage === 'data_completion') {
            return !empty($missingFields)
                ? 'Feche o campo pendente atual antes de seguir para a montagem.'
                : 'Os dados principais ja foram fechados. Revise e siga para a montagem.';
        }

        return 'O processo e o modelo ja estao prontos para seguir para a montagem assistida.';
    }

    protected function buildFieldQuestion($field)
    {
        if (empty($field) || empty($field['label'])) {
            return 'Antes do handoff, preciso fechar os campos obrigatorios que ainda estao pendentes.';
        }

        $prefix = !empty($field['group_label'])
            ? 'No bloco `' . $field['group_label'] . '`, '
            : '';

        if (($field['type'] ?? '') !== 'SELECT' || empty($field['options'])) {
            return $prefix . 'informe o valor do campo `' . $field['label'] . '`.';
        }

        $options = collect($field['options'])->map(function ($option) {
            return $option['index'] . '. ' . $option['label'] . (!empty($option['helper']) ? ' - ' . $option['helper'] : '');
        })->implode(' | ');

        return $prefix . 'escolha uma opcao para `' . $field['label'] . '`: ' . $options . '. Voce tambem pode digitar parte do texto para eu tentar selecionar automaticamente.';
    }
}
