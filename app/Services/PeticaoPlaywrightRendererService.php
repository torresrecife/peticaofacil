<?php

namespace App\Services;

use RuntimeException;

class PeticaoPlaywrightRendererService
{
    public function renderPdf(array $layout)
    {
        $nodeBinary = trim((string) config('pdf.playwright.node_binary', 'node'));
        $scriptPath = (string) config('pdf.playwright.script', base_path('scripts/pdf-renderer/render-peticao.js'));
        $timeout = max(10, (int) config('pdf.playwright.timeout', 90));

        if (!file_exists($scriptPath)) {
            throw new RuntimeException('Script Playwright de exportacao PDF nao encontrado.');
        }

        $tempDir = storage_path('app/pdf-playwright');
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio temporario do renderer Playwright.');
        }

        $token = str_replace('.', '', uniqid('peticao_playwright_', true));
        $inputPath = $tempDir . DIRECTORY_SEPARATOR . $token . '.json';
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $token . '.pdf';

        $payload = [
            'title' => (string) ($layout['title'] ?? 'peticao'),
            'body_html' => (string) ($layout['body_html'] ?? ''),
            'header_html' => (string) ($layout['header_html'] ?? ''),
            'footer_html' => (string) ($layout['footer_html'] ?? ''),
            'header_defaults' => (array) ($layout['header_defaults'] ?? []),
            'footer_defaults' => (array) ($layout['footer_defaults'] ?? []),
            'options' => [
                'format' => 'A4',
                'browser_binary' => trim((string) config('pdf.playwright.browser_binary', '')),
                'margin' => [
                    'top' => (string) config('pdf.playwright.margin.top', '16.9mm'),
                    'right' => (string) config('pdf.playwright.margin.right', '16.9mm'),
                    'bottom' => (string) config('pdf.playwright.margin.bottom', '16.9mm'),
                    'left' => (string) config('pdf.playwright.margin.left', '16.9mm'),
                ],
            ],
        ];

        file_put_contents($inputPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            $command = $this->quoteShellArgument($nodeBinary)
                . ' '
                . $this->quoteShellArgument($scriptPath)
                . ' --input '
                . $this->quoteShellArgument($inputPath)
                . ' --output '
                . $this->quoteShellArgument($outputPath);

            $output = [];
            $exitCode = 0;
            $start = microtime(true);
            @exec($command . ' 2>&1', $output, $exitCode);
            $duration = microtime(true) - $start;

            if ($exitCode !== 0) {
                throw new RuntimeException('Falha ao renderizar PDF com Playwright: ' . trim(implode("\n", $output)));
            }

            if ($duration > $timeout) {
                throw new RuntimeException('A renderizacao do PDF com Playwright excedeu o tempo configurado.');
            }

            if (!file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('O renderer Playwright nao gerou o arquivo PDF.');
            }

            return file_get_contents($outputPath);
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    protected function quoteShellArgument($value)
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }
}
