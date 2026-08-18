<?php

namespace App\Services;

class PeticaoSavedAiReviewService
{
    protected $openAiClient;

    public function __construct(OpenAIResponsesClient $openAiClient)
    {
        $this->openAiClient = $openAiClient;
    }

    public function review($html, $plainText = null)
    {
        $text = $this->normalizeText($plainText !== null ? $plainText : $this->extractPlainText($html));
        if ($text === '') {
            return [
                'mode' => 'ai',
                'summary' => 'O documento esta vazio. Nao ha conteudo para analisar.',
                'findings' => [],
                'warnings' => [],
            ];
        }

        if (!$this->openAiClient->isEnabled()) {
            return [
                'mode' => 'fallback',
                'summary' => 'Nenhum provedor de IA esta configurado para a analise interpretativa.',
                'findings' => [],
                'warnings' => ['Configure a OpenAI para habilitar o Assistente IA opcional.'],
            ];
        }

        $response = $this->openAiClient->createStructuredResponse($this->buildMessages($text), $this->schema(), 'peticao_saved_ai_review');
        if (!$response['ok']) {
            return [
                'mode' => 'openai',
                'summary' => 'Nao foi possivel concluir a analise interpretativa com IA.',
                'findings' => [],
                'warnings' => array_values(array_filter([
                    $response['error'] ?? 'Falha na analise por IA.',
                ])),
            ];
        }

        $data = is_array($response['data']) ? $response['data'] : [];
        $findings = $this->normalizeFindings($data['findings'] ?? []);

        return [
            'mode' => 'openai',
            'summary' => trim((string) ($data['summary'] ?? 'Analise interpretativa concluida.')),
            'findings' => $findings,
            'warnings' => array_values(array_filter($data['warnings'] ?? [])),
        ];
    }

    protected function buildMessages($text)
    {
        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'Voce revisa a peticao final como assistente juridico de qualidade textual e coerencia.',
                    'Nao atue como corretor ortografico local: isso ja e feito separadamente por LanguageTool.',
                    'Foque apenas em problemas interpretativos, duplicidade relevante, contradicao, texto residual, incoerencia de pedido, ausencia material de fechamento, repeticao indevida de trechos e falhas de clareza juridicamente sensiveis.',
                    'So aponte achados que merecam atencao humana.',
                    'Nao invente fatos e nao use conhecimento externo ao texto.',
                    'Se nao houver achados relevantes, retorne findings vazio.',
                    'Retorne no maximo 6 achados.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'task' => 'Analisar a peticao final com IA de forma opcional.',
                    'language' => 'pt-BR',
                    'text' => $text,
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ],
        ];
    }

    protected function schema()
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'summary' => ['type' => 'string'],
                'warnings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'findings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'severity' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'recommendation' => ['type' => 'string'],
                            'snippet' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'severity', 'message', 'recommendation', 'snippet'],
                    ],
                ],
            ],
            'required' => ['summary', 'warnings', 'findings'],
        ];
    }

    protected function normalizeFindings(array $findings)
    {
        $normalized = [];

        foreach ($findings as $finding) {
            if (!is_array($finding)) {
                continue;
            }

            $normalized[] = [
                'title' => trim((string) ($finding['title'] ?? 'Achado interpretativo')),
                'severity' => $this->normalizeSeverity($finding['severity'] ?? ''),
                'message' => trim((string) ($finding['message'] ?? '')),
                'recommendation' => trim((string) ($finding['recommendation'] ?? '')),
                'snippet' => trim((string) ($finding['snippet'] ?? '')),
            ];

            if (count($normalized) >= 6) {
                break;
            }
        }

        return array_values(array_filter($normalized, function ($finding) {
            return $finding['message'] !== '';
        }));
    }

    protected function normalizeSeverity($severity)
    {
        $severity = strtolower(trim((string) $severity));

        if (in_array($severity, ['alta', 'media', 'baixa'], true)) {
            return $severity;
        }

        return 'media';
    }

    protected function extractPlainText($html)
    {
        $html = (string) $html;
        $html = preg_replace('/<br\s*\/?>/i', ' ', $html);
        $html = preg_replace('/<\/p>/i', '</p> ', $html);
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        return $this->normalizeText($html);
    }

    protected function normalizeText($text)
    {
        $text = str_replace("\xc2\xa0", ' ', (string) $text);
        $text = preg_replace("/\r\n|\r|\n/u", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }
}
