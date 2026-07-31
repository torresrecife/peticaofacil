<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

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
                'error_code' => 'missing_api_key',
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
                'error_code' => 'empty_response',
                'data' => null,
            ];
        }

        $decoded = json_decode($rawText, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'JSON invalido retornado pela OpenAI.',
                'error_code' => 'invalid_json',
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
                'error_code' => 'http_transport_error',
                'body' => null,
            ];
        }

        $body = json_decode($raw, true);
        if ($status >= 400) {
            Log::warning('openai_response_error', [
                'status' => $status,
                'body' => $body,
            ]);

            return [
                'ok' => false,
                'error' => $this->formatApiError($status, $body),
                'error_code' => $this->resolveApiErrorCode($status, $body),
                'body' => $body,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'error_code' => null,
            'body' => is_array($body) ? $body : [],
        ];
    }

    protected function resolveApiErrorCode($status, array $body)
    {
        $type = (string) Arr::get($body, 'error.type', '');
        $code = (string) Arr::get($body, 'error.code', '');
        $message = mb_strtolower((string) Arr::get($body, 'error.message', ''));

        if ($status === 401) {
            return 'invalid_api_key';
        }

        if ($status === 429) {
            if ($code === 'insufficient_quota' || strpos($message, 'quota') !== false || strpos($message, 'billing') !== false) {
                return 'quota_exceeded';
            }

            return 'rate_limited';
        }

        if ($status >= 500) {
            return 'openai_server_error';
        }

        if ($type !== '') {
            return $type;
        }

        return 'openai_http_' . $status;
    }

    protected function formatApiError($status, array $body)
    {
        $code = $this->resolveApiErrorCode($status, $body);

        if ($code === 'invalid_api_key') {
            return 'A chave da OpenAI foi rejeitada. Revise OPENAI_API_KEY e o projeto vinculado.';
        }

        if ($code === 'quota_exceeded') {
            return 'A cota da OpenAI foi excedida. Revise billing, saldo ou limite de uso do projeto antes de continuar.';
        }

        if ($code === 'rate_limited') {
            return 'A OpenAI recusou a chamada por limite de taxa. Tente novamente em alguns instantes.';
        }

        if ($code === 'openai_server_error') {
            return 'A OpenAI respondeu com erro interno temporario. Tente novamente em alguns instantes.';
        }

        return Arr::get($body, 'error.message', 'Erro OpenAI HTTP ' . $status);
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
