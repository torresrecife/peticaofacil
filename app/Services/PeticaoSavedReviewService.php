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
        $issues = $this->normalizeIssues($data['issues'] ?? []);
        $warnings = array_values(array_filter($data['warnings'] ?? []));
        $summary = trim((string) ($data['summary'] ?? 'Revisao concluida.'));

        if (empty($issues)) {
            $summary = 'Nenhum erro grave foi encontrado nesta revisao.';
        }

        return [
            'mode' => 'openai',
            'summary' => $summary,
            'score' => empty($issues) ? 100 : max(0, min(100, (int) ($data['score'] ?? 0))),
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    protected function normalizeIssues(array $issues)
    {
        $normalized = [];

        foreach ($issues as $issue) {
            if (!is_array($issue)) {
                continue;
            }

            $normalizedIssue = [
                'category' => trim((string) ($issue['category'] ?? 'redacao')),
                'severity' => trim((string) ($issue['severity'] ?? 'alta')),
                'snippet' => trim((string) ($issue['snippet'] ?? '')),
                'message' => trim((string) ($issue['message'] ?? '')),
                'suggestion' => trim((string) ($issue['suggestion'] ?? '')),
            ];

            if ($normalizedIssue['severity'] !== 'alta') {
                continue;
            }

            $normalized[] = $normalizedIssue;
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

        if (preg_match('/\b(teste|rascunho|modelo|xxx|placeholder)\b/iu', $plainText, $matches)) {
            $issues[] = [
                'category' => 'texto residual',
                'severity' => 'alta',
                'snippet' => $matches[0],
                'message' => 'Ha indicio de texto residual ou marcador de edicao no documento.',
                'suggestion' => 'Excluir o trecho residual.',
            ];
        }

        return [
            'mode' => 'local',
            'summary' => empty($issues)
                ? 'Nenhum erro grave foi encontrado nesta revisao.'
                : 'A revisao basica encontrou erro grave que merece conferencia manual.',
            'score' => empty($issues) ? 100 : 40,
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    protected function systemPrompt()
    {
        return implode("\n", [
            'Voce revisa o texto final de peticoes em portugues do Brasil.',
            'Aponte somente erros graves e evidentes que comprometam a validade, a compreensao ou a seriedade profissional da peticao.',
            'Ignore ajustes finos de estilo, concordancia leve, pontuacao discutivel, fluidez, elegancia redacional e melhorias opcionais.',
            'So reporte problemas grosseiros, como texto residual, endereco claramente incompleto, contradicao grave, campo faltando, informacao critica ausente ou erro material evidente.',
            'Tambem considere erro grave quando houver ortografia evidentemente incorreta em nome proprio, cidade, comarca, orgao, qualificacao, cabecalho, endereco ou outro dado juridico relevante.',
            'Tambem considere erro grave quando o padrao visual ou formal exigir caixa alta e houver trecho materialmente destoante no mesmo bloco, especialmente em cabecalhos e qualificacoes.',
            'Exemplos que devem ser apontados: ausencia de acento em nome de cidade relevante, grafia materialmente errada de comarca ou orgao, e trecho em minusculas no meio de cabecalho integralmente em maiusculas.',
            'Nao invente erros. So reporte problemas com base no texto recebido.',
            'Nao reescreva a peticao inteira.',
            'Retorne no maximo 8 achados, apenas quando forem realmente graves.',
            'Use apenas severidade alta.',
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
