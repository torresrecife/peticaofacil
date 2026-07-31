<?php

namespace App\Services;

class OpenAIResponsesClient
{
    public function isEnabled()
    {
        return (bool) config('openai.enabled') && trim((string) config('openai.api_key')) !== '';
    }

    public function createStructuredResponse(array $messages, array $schema, $schemaName = 'response_payload')
    {
        if (!$this->isEnabled()) {
            return [
                'ok' => false,
                'error' => 'OPENAI_API_KEY nao configurada.',
                'data' => null,
            ];
        }

        $payload = [
            'model' => config('openai.model', 'gpt-5.6'),
            'input' => $this->normalizeMessages($messages),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $result = $this->post('/responses', $payload);
        if (!$result['ok']) {
            return $result;
        }

        $rawText = $this->extractOutputText($result['body']);
        if ($rawText === '') {
            return [
                'ok' => false,
                'error' => 'Resposta vazia da OpenAI.',
                'data' => null,
            ];
        }

        $decoded = json_decode($rawText, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'JSON invalido retornado pela OpenAI.',
                'data' => null,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'data' => $decoded,
        ];
    }

    protected function normalizeMessages(array $messages)
    {
        return array_values(array_map(function ($message) {
            return [
                'role' => $message['role'],
                'content' => [[
                    'type' => 'input_text',
                    'text' => (string) $message['content'],
                ]],
            ];
        }, $messages));
    }

    protected function post($path, array $payload)
    {
        $ch = curl_init(config('openai.base_url') . $path);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . config('openai.api_key'),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => (int) config('openai.timeout', 60),
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $curlError) {
            return [
                'ok' => false,
                'error' => 'Falha HTTP OpenAI: ' . $curlError,
                'body' => null,
            ];
        }

        $body = json_decode($raw, true);
        if ($status >= 400) {
            return [
                'ok' => false,
                'error' => $body['error']['message'] ?? ('Erro OpenAI HTTP ' . $status),
                'body' => $body,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'body' => is_array($body) ? $body : [],
        ];
    }

    protected function extractOutputText(array $body)
    {
        if (!empty($body['output_text']) && is_string($body['output_text'])) {
            return $body['output_text'];
        }

        if (empty($body['output']) || !is_array($body['output'])) {
            return '';
        }

        foreach ($body['output'] as $item) {
            if (empty($item['content']) || !is_array($item['content'])) {
                continue;
            }

            foreach ($item['content'] as $content) {
                if (!empty($content['text']) && is_string($content['text'])) {
                    return $content['text'];
                }
            }
        }

        return '';
    }
}
