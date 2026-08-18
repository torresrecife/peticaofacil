<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class LanguageToolClient
{
    public function isEnabled()
    {
        return (bool) config('languagetool.enabled');
    }

    public function check($text, array $options = [])
    {
        if (!$this->isEnabled()) {
            return [
                'ok' => false,
                'error' => 'LanguageTool nao configurado.',
                'error_code' => 'languagetool_not_enabled',
                'data' => null,
            ];
        }

        $payload = array_filter([
            'text' => (string) $text,
            'language' => $options['language'] ?? config('languagetool.language', 'pt-BR'),
            'enabledOnly' => 'false',
            'username' => config('languagetool.username'),
            'apiKey' => config('languagetool.api_key'),
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        return $this->post('/v2/check', $payload);
    }

    protected function post($path, array $payload)
    {
        $ch = curl_init(rtrim((string) config('languagetool.base_url'), '/') . $path);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_TIMEOUT => (int) config('languagetool.timeout', 20),
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $curlError) {
            return [
                'ok' => false,
                'error' => 'Falha HTTP LanguageTool: ' . $curlError,
                'error_code' => 'http_transport_error',
                'data' => null,
            ];
        }

        $body = json_decode($raw, true);
        if ($status >= 400) {
            Log::warning('languagetool_response_error', [
                'status' => $status,
                'body' => $body,
            ]);

            return [
                'ok' => false,
                'error' => $this->formatApiError($status, is_array($body) ? $body : []),
                'error_code' => 'languagetool_http_' . $status,
                'data' => null,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'error_code' => null,
            'data' => is_array($body) ? $body : [],
        ];
    }

    protected function formatApiError($status, array $body)
    {
        $message = (string) Arr::get($body, 'message', '');

        if ($status >= 500) {
            return 'LanguageTool respondeu com erro interno temporario.';
        }

        if ($status === 404) {
            return 'Servidor LanguageTool nao encontrado no endpoint configurado.';
        }

        if ($message !== '') {
            return $message;
        }

        return 'Erro LanguageTool HTTP ' . $status;
    }
}
