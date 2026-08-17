<?php

namespace App\Services;

class PeticaoSavedReviewService
{
    protected $client;

    public function __construct(OpenAIResponsesClient $client)
    {
        $this->client = $client;
    }

    public function review($html)
    {
        $plainText = $this->extractPlainText($html);
        if (trim($plainText) === '') {
            return [
                'mode' => 'local',
                'summary' => 'O documento esta vazio. Nao ha conteudo para revisar.',
                'score' => 0,
                'issues' => [],
                'warnings' => [],
            ];
        }

        if (!$this->client->isEnabled()) {
            return $this->fallback($plainText, 'OpenAI indisponivel. Revisao basica local aplicada.');
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $this->buildContextPrompt($plainText),
            ],
        ];

        $response = $this->client->createStructuredResponse($messages, $this->schema(), 'peticao_saved_review');
        if (!$response['ok']) {
            return $this->fallback($plainText, $response['error']);
        }

        $data = $response['data'];

        return [
            'mode' => 'openai',
            'summary' => trim((string) ($data['summary'] ?? 'Revisao concluida.')),
            'score' => max(0, min(100, (int) ($data['score'] ?? 0))),
            'issues' => $this->normalizeIssues($data['issues'] ?? []),
            'warnings' => array_values(array_filter($data['warnings'] ?? [])),
        ];
    }

    protected function normalizeIssues(array $issues)
    {
        $normalized = [];

        foreach ($issues as $issue) {
            if (!is_array($issue)) {
                continue;
            }

            $normalized[] = [
                'category' => trim((string) ($issue['category'] ?? 'redacao')),
                'severity' => trim((string) ($issue['severity'] ?? 'media')),
                'snippet' => trim((string) ($issue['snippet'] ?? '')),
                'message' => trim((string) ($issue['message'] ?? '')),
                'suggestion' => trim((string) ($issue['suggestion'] ?? '')),
            ];
        }

        return array_values(array_filter($normalized, function ($issue) {
            return $issue['message'] !== '';
        }));
    }

    protected function fallback($plainText, $warning = null)
    {
        $issues = [];
        $warnings = [];

        if ($warning) {
            $warnings[] = $warning;
        }

        if (preg_match('/\b(\pL{2,})\s+\1\b/iu', $plainText, $matches)) {
            $issues[] = [
                'category' => 'repeticao',
                'severity' => 'media',
                'snippet' => $matches[0],
                'message' => 'Possivel repeticao imediata de palavra.',
                'suggestion' => 'Revise se a duplicacao da palavra e intencional.',
            ];
        }

        if (preg_match('/[!?.,;:]{2,}/u', $plainText, $matches)) {
            $issues[] = [
                'category' => 'pontuacao',
                'severity' => 'media',
                'snippet' => $matches[0],
                'message' => 'Possivel excesso de pontuacao.',
                'suggestion' => 'Revise a pontuacao deste trecho.',
            ];
        }

        if (preg_match('/\s{2,}/u', $plainText, $matches)) {
            $issues[] = [
                'category' => 'formatacao',
                'severity' => 'baixa',
                'snippet' => 'espacos consecutivos',
                'message' => 'Ha espacos consecutivos no texto.',
                'suggestion' => 'Padronize o espacamento do documento.',
            ];
        }

        return [
            'mode' => 'local',
            'summary' => empty($issues)
                ? 'Revisao basica concluida sem achados relevantes.'
                : 'Revisao basica encontrou pontos que merecem conferencia manual.',
            'score' => empty($issues) ? 85 : 62,
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    protected function systemPrompt()
    {
        return implode("\n", [
            'Voce revisa o texto final de peticoes em portugues do Brasil.',
            'Identifique erros de gramatica, concordancia, pontuacao, repeticao desnecessaria, ambiguidade e problemas de redacao juridica.',
            'Nao invente erros. So reporte problemas com base no texto recebido.',
            'Nao reescreva a peticao inteira.',
            'Retorne no maximo 12 achados, priorizando os mais importantes.',
            'Use severidade baixa, media ou alta.',
            'Quando possivel, inclua um trecho curto em snippet e uma sugestao objetiva.',
        ]);
    }

    protected function buildContextPrompt($plainText)
    {
        return json_encode([
            'task' => 'Revisar a peticao final e apontar problemas de escrita.',
            'language' => 'pt-BR',
            'text' => $plainText,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function schema()
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'summary' => ['type' => 'string'],
                'score' => ['type' => 'integer'],
                'warnings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'issues' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'category' => ['type' => 'string'],
                            'severity' => ['type' => 'string'],
                            'snippet' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'suggestion' => ['type' => 'string'],
                        ],
                        'required' => ['category', 'severity', 'snippet', 'message', 'suggestion'],
                    ],
                ],
            ],
            'required' => ['summary', 'score', 'warnings', 'issues'],
        ];
    }

    protected function extractPlainText($html)
    {
        $html = (string) $html;
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "</p>\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/\r\n|\r/u", "\n", $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }
}
