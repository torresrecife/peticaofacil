<?php

namespace App\Services;

use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class PeticaoExportService
{
    public function exportWordFromLayout(array $layout)
    {
        $filename = $this->sanitizeFileName($layout['title'] ?? 'peticao') . '.doc';
        $content = $this->renderWordDocument(
            $layout['title'] ?? 'peticao',
            $layout['body_html'] ?? '',
            $layout['header_html'] ?? null,
            $layout['footer_html'] ?? null
        );

        return response($content, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportWord($nomeArquivo, $conteudoHtml, $cabecalhoHtml = null, $rodapeHtml = null)
    {
        return $this->exportWordFromLayout([
            'title' => $nomeArquivo,
            'body_html' => $conteudoHtml,
            'header_html' => $cabecalhoHtml,
            'footer_html' => $rodapeHtml,
        ]);
    }

    public function exportPdfFromLayout(Request $request, array $layout)
    {
        $engine = strtolower((string) config('pdf.engine', 'browser'));

        if ($engine === 'playwright') {
            $content = app(PeticaoPlaywrightRendererService::class)->renderPdf(
                $this->preparePlaywrightLayout($layout)
            );

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFileName($layout['title'] ?? 'peticao') . '.pdf"',
                'X-Peticao-Pdf-Engine' => 'playwright',
            ]);
        }

        return $this->exportPdf(
            $request,
            $layout['title'] ?? 'peticao',
            $layout['body_html'] ?? '',
            $layout['header_html'] ?? null,
            $layout['footer_html'] ?? null
        );
    }

    public function renderPrintViewFromLayout(array $layout, $assetMode = 'browser')
    {
        return $this->renderPrintView(
            $layout['title'] ?? 'peticao',
            $layout['body_html'] ?? '',
            $layout['meta'] ?? [],
            $assetMode,
            $layout['header_html'] ?? null,
            $layout['footer_html'] ?? null
        );
    }

    public function exportPdf(Request $request, $nomeArquivo, $conteudoHtml, $cabecalhoHtml = null, $rodapeHtml = null)
    {
        if ($this->shouldUseBrowserPdf()) {
            try {
                return $this->exportBrowserPdf($nomeArquivo, $conteudoHtml, $cabecalhoHtml, $rodapeHtml);
            } catch (Throwable $exception) {
                report($exception);

                if (!$this->shouldUseHtml2PdfFallback()) {
                    throw $exception;
                }
            }
        }

        return $this->exportLegacyPdf($request, $nomeArquivo, $conteudoHtml, $cabecalhoHtml, $rodapeHtml);
    }

    public function renderPrintView($nomeArquivo, $conteudoHtml, array $meta = [], $assetMode = 'browser', $cabecalhoHtml = null, $rodapeHtml = null)
    {
        $editorCss = file_exists(public_path('ckeditor/contents.css'))
            ? $this->scopeEditorPrintCss(file_get_contents(public_path('ckeditor/contents.css')))
            : '';

        $conteudoSemMoldura = $this->stripEmbeddedHeaderFooter($conteudoHtml, $cabecalhoHtml, $rodapeHtml);
        $bodyHtml = $this->preserveBlankEditorBlocks(
            $this->normalizePrintMarkup(
                $this->preserveAlignedSpacingMarkup(
                    $this->normalizeAssetImageSrc($conteudoSemMoldura, $assetMode)
                )
            )
        );
        $headerHtml = $this->prepareExportFragment($cabecalhoHtml, $assetMode, 'print');
        $footerHtml = $this->prepareExportFragment($rodapeHtml, $assetMode, 'print');
        $documentHtml = $this->composePrintDocumentHtml($headerHtml, $bodyHtml, $footerHtml);

        return view('peticao.print', [
            'documentTitle' => $nomeArquivo,
            'documentHtml' => $documentHtml,
            'headerHtml' => null,
            'footerHtml' => null,
            'meta' => $meta,
            'editorCss' => $editorCss,
            'printCss' => $this->buildBrowserPrintStyles(),
        ]);
    }

    public function renderWordDocument($nomeArquivo, $conteudoHtml, $cabecalhoHtml = null, $rodapeHtml = null)
    {
        $editorCss = file_exists(public_path('ckeditor/contents.css'))
            ? $this->scopeEditorPrintCss(file_get_contents(public_path('ckeditor/contents.css')))
            : '';

        $conteudoSemMoldura = $this->stripEmbeddedHeaderFooter($conteudoHtml, $cabecalhoHtml, $rodapeHtml);
        $documentHtml = $this->preserveBlankEditorBlocks(
            $this->normalizeWordMarkup(
                $this->preserveAlignedSpacingMarkup(
                    $this->normalizeAssetImageSrc($conteudoSemMoldura, 'browser')
                )
            )
        );
        $headerHtml = $this->prepareExportFragment($cabecalhoHtml, 'browser', 'word');
        $footerHtml = $this->prepareExportFragment($rodapeHtml, 'browser', 'word');

        return view('peticao.word', [
            'documentTitle' => $nomeArquivo,
            'documentHtml' => $documentHtml,
            'headerHtml' => $headerHtml,
            'footerHtml' => $footerHtml,
            'editorCss' => $editorCss,
            'wordCss' => $this->buildWordStyles(),
        ])->render();
    }

    public function sanitizeFileName($value)
    {
        $value = preg_replace('/[^\pL\pN_\-]+/u', '_', $value);
        $value = trim($value, '_');

        return $value !== '' ? $value : 'peticao';
    }

    protected function exportBrowserPdf($nomeArquivo, $conteudoHtml, $cabecalhoHtml = null, $rodapeHtml = null)
    {
        $browserBinary = $this->resolveBrowserBinary();
        if ($browserBinary === null) {
            throw new RuntimeException('Navegador compativel para exportacao PDF nao encontrado.');
        }

        $baseName = $this->sanitizeFileName($nomeArquivo);
        $tempDir = storage_path('app/pdf-browser');
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio temporario de PDF.');
        }

        $token = $baseName . '_' . str_replace('.', '', uniqid('', true));
        $htmlPath = $tempDir . DIRECTORY_SEPARATOR . $token . '.html';
        $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $token . '.pdf';

        try {
            $html = $this->renderPrintView($nomeArquivo, $conteudoHtml, [], 'filesystem', $cabecalhoHtml, $rodapeHtml)->render();
            file_put_contents($htmlPath, $html);

            $this->runBrowserPrintCommand($browserBinary, $htmlPath, $pdfPath);

            if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
                throw new RuntimeException('O navegador nao gerou o arquivo PDF.');
            }

            return response(file_get_contents($pdfPath), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $baseName . '.pdf"',
                'X-Peticao-Pdf-Engine' => 'browser',
            ]);
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
        }
    }

    protected function exportLegacyPdf(Request $request, $nomeArquivo, $conteudoHtml, $cabecalhoHtml = null, $rodapeHtml = null)
    {
        $this->prepareLegacyPdfEnvironment($request);

        $library = $this->resolvePdfLibraryPath();
        if (!file_exists($library)) {
            abort(500, 'Biblioteca de PDF nao encontrada.');
        }

        require_once $library;

        $conteudoSemMoldura = $this->stripEmbeddedHeaderFooter($conteudoHtml, $cabecalhoHtml, $rodapeHtml);

        $pageMargins = config('pdf.page');
        $content = '<style>' . $this->buildPdfStyles() . '</style>'
            . '<page backtop="' . (int) ($pageMargins['top_mm'] ?? 19) . 'mm"'
            . ' backbottom="' . (int) ($pageMargins['bottom_mm'] ?? 15) . 'mm"'
            . ' backleft="' . (int) ($pageMargins['left_mm'] ?? 17) . 'mm"'
            . ' backright="' . (int) ($pageMargins['right_mm'] ?? 17) . 'mm">'
            . $this->prepareExportFragment($cabecalhoHtml, 'filesystem', 'pdf')
            . $this->normalizePdfMarkup(
                $this->preserveAlignedSpacingMarkup(
                    $this->normalizeAssetImageSrc($conteudoSemMoldura)
                )
            )
            . $this->prepareExportFragment($rodapeHtml, 'filesystem', 'pdf')
            . '</page>';

        if (ob_get_length()) {
            ob_end_clean();
        }

        $pdf = new \HTML2PDF('P', 'A4', 'pt');
        $pdf->setDefaultFont('arial');
        $pdf->writeHTML($content);

        return response($pdf->Output($this->sanitizeFileName($nomeArquivo) . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFileName($nomeArquivo) . '.pdf"',
            'X-Peticao-Pdf-Engine' => 'html2pdf',
        ]);
    }

    protected function shouldUseBrowserPdf()
    {
        return strtolower((string) config('pdf.engine', 'browser')) === 'browser';
    }

    protected function shouldUseHtml2PdfFallback()
    {
        return strtolower((string) config('pdf.fallback_engine', 'html2pdf')) === 'html2pdf';
    }

    protected function resolveBrowserBinary()
    {
        $configured = trim((string) config('pdf.browser_binary', ''));
        if ($configured !== '' && file_exists($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/microsoft-edge',
            '/snap/bin/chromium',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        foreach (['google-chrome', 'google-chrome-stable', 'chromium-browser', 'chromium', 'microsoft-edge'] as $binary) {
            $resolved = trim((string) @shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null'));
            if ($resolved !== '' && file_exists($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    protected function runBrowserPrintCommand($browserBinary, $htmlPath, $pdfPath)
    {
        $timeoutSeconds = max(10, (int) config('pdf.browser_timeout', 60));
        $virtualTimeBudget = max(1000, (int) config('pdf.browser_virtual_time_budget', 4000));

        $commandParts = [
            $this->quoteShellArgument($browserBinary),
            '--headless',
            '--disable-gpu',
            '--hide-scrollbars',
            '--no-first-run',
            '--no-default-browser-check',
            '--allow-file-access-from-files',
            '--enable-local-file-accesses',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=' . $virtualTimeBudget,
            '--print-to-pdf-no-header',
            '--no-pdf-header-footer',
            '--print-to-pdf=' . $this->quoteShellArgument($pdfPath),
            $this->quoteShellArgument($this->pathToFileUrl($htmlPath)),
        ];

        $command = implode(' ', $commandParts);

        $output = [];
        $exitCode = 0;
        $start = microtime(true);
        @exec($command . ' 2>&1', $output, $exitCode);
        $duration = microtime(true) - $start;

        if ($exitCode !== 0) {
            throw new RuntimeException('Falha ao gerar PDF no navegador: ' . trim(implode("\n", $output)));
        }

        if ($duration > $timeoutSeconds) {
            throw new RuntimeException('A geracao do PDF excedeu o tempo configurado.');
        }
    }

    protected function quoteShellArgument($value)
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    protected function pathToFileUrl($path)
    {
        $normalized = str_replace('\\', '/', realpath($path) ?: $path);

        if (preg_match('/^[A-Za-z]:\//', $normalized)) {
            return 'file:///' . $normalized;
        }

        return 'file://' . $normalized;
    }

    protected function prepareLegacyPdfEnvironment(Request $request)
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $parts = parse_url($appUrl);

        $host = $parts['host'] ?? ($request->getHost() ?: 'localhost');
        $scheme = $parts['scheme'] ?? ($request->getScheme() ?: 'http');
        $basePath = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        $scriptPath = ($basePath !== '' ? $basePath : '') . '/index.php';

        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? $host;
        $_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? ($scheme === 'https' ? 'on' : 'off');
        $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? $scriptPath;
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? $scriptPath;
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? $scriptPath;
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'] ?? public_path('index.php');
        $_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? realpath(public_path());
    }

    protected function resolvePdfLibraryPath()
    {
        $candidates = [
            base_path('html2pdf/html2pdf.class.php'),
            dirname(base_path()) . '/html2pdf/html2pdf.class.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    protected function normalizeAssetImageSrc($html, $mode = 'filesystem')
    {
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $projectRoot = realpath(base_path());
        $publicRoot = realpath(public_path());
        $appPath = trim((string) parse_url((string) config('app.url', ''), PHP_URL_PATH), '/');
        $appUrl = rtrim((string) config('app.url', ''), '/');

        return preg_replace_callback('/\bsrc=(["\'])(.*?)\1/i', function ($matches) use ($docRoot, $projectRoot, $publicRoot, $appPath, $appUrl, $mode) {
            $quote = $matches[1];
            $src = html_entity_decode(trim($matches[2]), ENT_QUOTES, 'UTF-8');

            if ($src === '' || strpos($src, 'data:') === 0 || preg_match('#^https?://#i', $src)) {
                return 'src=' . $quote . $src . $quote;
            }

            $clean = preg_replace('/[#?].*$/', '', $src);
            $clean = rawurldecode($clean);
            $clean = str_replace('\\', '/', $clean);

            $candidates = [];

            if (preg_match('#^file://#i', $clean)) {
                $localPath = preg_replace('#^file:(//)?#i', '', $clean);
                $localPath = preg_replace('#^/([A-Za-z]:/)#', '$1', $localPath);
                $candidates[] = $localPath;
            } elseif (isset($clean[0]) && $clean[0] === '/') {
                if ($docRoot !== '') {
                    $candidates[] = $docRoot . $clean;
                    $candidates[] = $docRoot . '/public' . $clean;
                }
                if ($publicRoot !== false) {
                    $candidates[] = $publicRoot . $clean;
                }
                if ($projectRoot !== false) {
                    $candidates[] = $projectRoot . $clean;
                    $candidates[] = $projectRoot . '/public' . $clean;
                }
            } else {
                $candidates[] = $clean;
                if ($docRoot !== '') {
                    $candidates[] = $docRoot . '/' . $clean;
                    $candidates[] = $docRoot . '/public/' . ltrim($clean, '/');
                }
                if ($publicRoot !== false) {
                    $candidates[] = $publicRoot . '/' . ltrim($clean, '/');
                }
                if ($projectRoot !== false) {
                    $candidates[] = $projectRoot . '/' . ltrim($clean, '/');
                    $candidates[] = $projectRoot . '/public/' . ltrim($clean, '/');
                }
            }

            if ($appPath !== '') {
                $normalizedClean = ltrim($clean, '/');
                if (stripos($normalizedClean, strtolower($appPath) . '/') === 0) {
                    $suffix = ltrim(substr($normalizedClean, strlen($appPath)), '/');
                    if ($publicRoot !== false) {
                        $candidates[] = $publicRoot . '/' . $suffix;
                    }
                    if ($projectRoot !== false) {
                        $candidates[] = $projectRoot . '/' . $suffix;
                        $candidates[] = $projectRoot . '/public/' . $suffix;
                    }
                }
            }

            foreach ($candidates as $candidate) {
                $candidate = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
                $real = realpath($candidate);
                if ($real === false) {
                    continue;
                }

                $real = str_replace('\\', '/', $real);

                if ($mode === 'browser' && $publicRoot !== false) {
                    $normalizedPublicRoot = str_replace('\\', '/', $publicRoot);
                    if (stripos($real, $normalizedPublicRoot) === 0) {
                        $relative = ltrim(substr($real, strlen($normalizedPublicRoot)), '/');
                        return 'src=' . $quote . $this->buildBrowserAssetUrl($relative, $appUrl, $appPath) . $quote;
                    }
                }

                if ($mode === 'browser') {
                    $relative = $this->extractRelativePathFromPublicRoot($real);
                    if ($relative !== null) {
                        return 'src=' . $quote . $this->buildBrowserAssetUrl($relative, $appUrl, $appPath) . $quote;
                    }
                }

                if ($mode === 'inline') {
                    $inline = $this->pathToInlineDataUrl($real);
                    if ($inline !== null) {
                        return 'src=' . $quote . $inline . $quote;
                    }
                }

                if (preg_match('/^[A-Za-z]:\//', $real)) {
                    return 'src=' . $quote . 'file:///' . $real . $quote;
                }

                return 'src=' . $quote . 'file://' . $real . $quote;
            }

            return 'src=' . $quote . $src . $quote;
        }, $html);
    }

    protected function extractRelativePathFromPublicRoot($absolutePath)
    {
        $normalized = str_replace('\\', '/', $absolutePath);
        $marker = '/public/';
        $position = stripos($normalized, $marker);

        if ($position === false) {
            return null;
        }

        return ltrim(substr($normalized, $position + strlen($marker)), '/');
    }

    protected function buildBrowserAssetUrl($relativePath, $appUrl, $appPath)
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($appUrl !== '') {
            return rtrim($appUrl, '/') . '/' . $relativePath;
        }

        return ($appPath !== '' ? '/' . trim($appPath, '/') : '') . '/' . $relativePath;
    }

    protected function pathToInlineDataUrl($absolutePath)
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $content = @file_get_contents($absolutePath);
        if ($content === false) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? @mime_content_type($absolutePath) : null;
        if (!$mime) {
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
            ];
            $mime = $mimeMap[$extension] ?? 'application/octet-stream';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    protected function buildBrowserPrintStyles()
    {
        return implode("\n", [
            '@page { size: A4; margin: 0; }',
            'html, body { margin: 0 !important; padding: 0 !important; max-width: none !important; background: #ffffff; }',
            'body { color: #1f2933; font-family: Arial, Helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }',
            '.peticao-print-shell { padding: 24px 0 40px; background: #eff2f6; }',
            '.peticao-print-sheet { width: 794px; min-height: 1123px; margin: 0 auto; box-sizing: border-box; background-color: #ffffff; background-image: repeating-linear-gradient(to bottom, #ffffff 0, #ffffff 1118px, #cbd2d9 1118px, #cbd2d9 1122px, #ffffff 1122px, #ffffff 1162px); background-repeat: repeat-y; background-size: 100% 1162px; box-shadow: none; overflow: hidden; }',
            '.peticao-print-content { min-height: 1123px; padding: 64px !important; box-sizing: border-box; }',
            '.peticao-print-header, .peticao-print-footer { width: 100%; }',
            '.peticao-print-header { margin-bottom: 24px; }',
            '.peticao-print-footer { margin-top: 24px; }',
            '.peticao-print-header-inline { margin-bottom: 24px; }',
            '.peticao-print-footer-inline { margin-top: 24px; }',
            '.peticao-print-header img, .peticao-print-footer img { max-width: 100%; height: auto; }',
            '.peticao-print-body { min-height: 0; }',
            '.peticao-print-sheet img { max-width: 100%; height: auto; display: inline-block; }',
            '.peticao-print-sheet table { max-width: 100%; table-layout: auto; }',
            '.peticao-print-sheet p, .peticao-print-sheet div, .peticao-print-sheet td, .peticao-print-sheet th, .peticao-print-sheet li, .peticao-print-sheet span, .peticao-print-sheet strong, .peticao-print-sheet u { line-height: 1.6; }',
            '.peticao-print-sheet p { margin: 0 0 12px; }',
            '.peticao-print-sheet .print-header-table { width: 100% !important; table-layout: fixed; border-collapse: collapse; }',
            '.peticao-print-sheet .print-header-table td { vertical-align: top; }',
            '.peticao-print-sheet .print-header-table td:first-child { width: 34%; text-align: left; }',
            '.peticao-print-sheet .print-header-table td:last-child { width: 66%; text-align: right; }',
            '.peticao-print-sheet .print-header-table img { display: block; max-width: 100%; height: auto; }',
            '.peticao-print-sheet .print-header-contact { width: 100%; margin: 0; font-size: 9pt; line-height: 1.2; text-align: right !important; white-space: normal; }',
            '.peticao-print-sheet .peticao-empty-line { min-height: 1.6em; display: block; }',
            '@media print { @page { size: A4; margin: 16.9mm; } html, body { background: #fff !important; } .peticao-print-shell { padding: 0; background: #fff; } .peticao-print-sheet { width: auto; min-height: auto; margin: 0; box-shadow: none; background-image: none; overflow: visible; } .peticao-print-content { min-height: auto; padding: 0 !important; box-sizing: border-box; } .peticao-print-header, .peticao-print-footer { display: none !important; } .peticao-print-header-inline { margin-bottom: 24px; } .peticao-print-footer-inline { margin-top: 24px; } }',
        ]);
    }

    protected function buildWordStyles()
    {
        return implode("\n", [
            '@page Section1 { size: 595.3pt 841.9pt; margin: 72pt 47.9pt 72pt 47.9pt; mso-header-margin: 18pt; mso-footer-margin: 18pt; mso-page-orientation: portrait; mso-header: h1; mso-footer: f1; }',
            'html, body { margin: 0; padding: 0; background: #ffffff; }',
            'body { color: #1f2933; font-family: Arial, Helvetica, sans-serif; }',
            'div.Section1 { page: Section1; }',
            'div.WordSectionHeader, div.WordSectionFooter { width: 100%; mso-hide: all; }',
            'div.WordSectionHeader p, div.WordSectionFooter p { margin: 0 0 9pt; }',
            '.peticao-word-sheet { width: auto; margin: 0; padding: 0; box-sizing: border-box; background: #fff; }',
            '.peticao-word-sheet img { max-width: 100%; height: auto; display: inline-block; }',
            '.peticao-word-sheet table { width: auto; max-width: 100%; table-layout: auto; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }',
            '.peticao-word-sheet p, .peticao-word-sheet div, .peticao-word-sheet td, .peticao-word-sheet th, .peticao-word-sheet li, .peticao-word-sheet span, .peticao-word-sheet strong, .peticao-word-sheet u { line-height: 1.6; }',
            '.peticao-word-sheet p { margin: 0 0 9pt; mso-margin-top-alt: 0pt; mso-margin-bottom-alt: 9pt; line-height: 160%; mso-line-height-rule: at-least; text-align: justify; }',
            '.peticao-word-sheet td p { margin: 0; mso-margin-top-alt: 0pt; mso-margin-bottom-alt: 0pt; }',
            '.word-header-table { width: 100%; table-layout: fixed; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }',
            '.word-header-table td { vertical-align: middle; padding: 0; }',
            '.word-header-table td:first-child { width: 34%; }',
            '.word-header-table td:last-child { width: 66%; text-align: right; }',
            '.word-header-contact { font-size: 9pt; line-height: 1.3; text-align: right; white-space: normal; mso-line-height-rule: at-least; margin: 0; }',
            '.word-header-contact span { white-space: inherit !important; }',
            '.peticao-word-sheet h1, .peticao-word-sheet h2, .peticao-word-sheet h3, .peticao-word-sheet h4, .peticao-word-sheet h5, .peticao-word-sheet h6 { font-weight: bold; line-height: 1.35; margin: 0 0 9pt; mso-margin-top-alt: 0pt; mso-margin-bottom-alt: 9pt; }',
            '.peticao-word-sheet .peticao-titulo-principal { text-align: center; text-transform: uppercase; font-size: 15pt; font-weight: bold; letter-spacing: 0; margin: 0 0 18pt; mso-margin-top-alt: 0pt; mso-margin-bottom-alt: 18pt; }',
            '.peticao-word-sheet .peticao-subtitulo { text-align: left; text-transform: uppercase; font-size: 12pt; font-weight: bold; margin: 18pt 0 9pt; mso-margin-top-alt: 18pt; mso-margin-bottom-alt: 9pt; }',
            '.peticao-word-sheet .peticao-corpo, .peticao-word-sheet .peticao-fundamentacao, .peticao-word-sheet .peticao-pedido { text-align: justify; text-indent: 2cm; }',
            '.peticao-word-sheet .peticao-fundamentacao { margin-top: 7.5pt; mso-margin-top-alt: 7.5pt; }',
            '.peticao-word-sheet .peticao-pedido { font-weight: bold; }',
            '.peticao-word-sheet .peticao-assinatura { margin-top: 27pt; mso-margin-top-alt: 27pt; text-align: center; text-indent: 0; }',
            '.peticao-word-sheet .peticao-observacao { margin: 9pt 0; padding: 7.5pt 9pt; border-left: 3pt solid #d9b95b; background: #fff7d6; color: #694f00; text-indent: 0; }',
            '.peticao-word-sheet .peticao-tabela-compacta { width: 100%; border-collapse: collapse; font-size: 11pt; }',
            '.peticao-word-sheet .peticao-tabela-compacta th, .peticao-word-sheet .peticao-tabela-compacta td { border: 1px solid #cbd2d9; padding: 4.5pt 6pt; vertical-align: top; }',
            '.peticao-word-sheet .peticao-lista { padding-left: 18pt; }',
            '.peticao-word-sheet .peticao-empty-line { min-height: 1.6em; display: block; }',
        ]);
    }

    protected function scopeEditorPrintCss($css)
    {
        if (!is_string($css) || trim($css) === '') {
            return '';
        }

        $replacements = [
            '/\bbody\b/' => '.peticao-print-content',
            '/\.cke_editable\b/' => '.peticao-print-content',
            '/\.cke_contents_ltr blockquote\b/' => '.peticao-print-content blockquote',
            '/\.cke_contents_rtl blockquote\b/' => '.peticao-print-content blockquote',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $css = preg_replace($pattern, $replacement, $css);
        }

        $css = preg_replace('/box-shadow\s*:\s*[^;]+;?/i', '', $css);
        $css = preg_replace('/background(?:-color|-image|-repeat|-size|-position)?\s*:\s*[^;]+;?/i', '', $css);
        $css = preg_replace('/margin\s*:\s*24px\s+auto(?:\s+40px)?;?/i', 'margin: 0;', $css);
        $css = preg_replace('/padding\s*:\s*64px;?/i', 'padding: 0;', $css);
        $css = preg_replace('/min-height\s*:\s*1123px;?/i', '', $css);
        $css = preg_replace('/max-width\s*:\s*794px;?/i', 'max-width: none;', $css);

        return $css;
    }

    protected function buildPdfStyles()
    {
        return implode("\n", [
            'body { font-family: Arial, Helvetica, sans-serif; color: #1f2933; }',
            'p, div, td, th, li, span, strong, u { line-height: 160%; }',
            'p { margin: 0 0 12px; }',
            'ol, ul { margin: 0 0 12px; padding-left: 24px; }',
            'li { margin: 0 0 6px; }',
            'table { border-collapse: collapse; border-spacing: 0; }',
            'th, td { vertical-align: top; }',
            'h1, h2, h3, h4, h5, h6 { font-weight: bold; line-height: 135%; margin: 0 0 12px; }',
            '.titulos { text-align: center; border: solid 1px #000; font-weight: bold; margin: 0 0 12px; }',
            '.peticao-titulo-principal { text-align: center; text-transform: uppercase; font-size: 15pt; font-weight: bold; margin: 0 0 24px; }',
            '.peticao-subtitulo { text-align: left; text-transform: uppercase; font-size: 12pt; font-weight: bold; margin: 24px 0 12px; }',
            '.peticao-corpo, .peticao-fundamentacao, .peticao-pedido { text-align: justify; text-indent: 2cm; }',
            '.peticao-fundamentacao { margin-top: 10px; }',
            '.peticao-pedido { font-weight: bold; }',
            '.peticao-assinatura { margin-top: 36px; text-align: center; text-indent: 0; }',
            '.peticao-observacao { margin: 12px 0; padding: 10px 12px; border-left: 4px solid #d9b95b; background: #fff7d6; color: #694f00; text-indent: 0; }',
            '.peticao-tabela-compacta { width: 100%; border-collapse: collapse; font-size: 11pt; }',
            '.peticao-tabela-compacta th, .peticao-tabela-compacta td { border: 1px solid #cbd2d9; padding: 6px 8px; vertical-align: top; }',
            '.peticao-empty-line { min-height: 1.6em; display: block; }',
            'img { max-width: 100%; }',
        ]);
    }

    protected function normalizePdfMarkup($html)
    {
        $html = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

            $style = preg_replace_callback('/font-size\s*:\s*(\d+(?:\.\d+)?)px/i', function ($fontMatches) {
                return 'font-size:' . $fontMatches[1] . 'pt';
            }, $style);

            $style = preg_replace('/line-height\s*:\s*115%/i', 'line-height:160%', $style);
            $style = preg_replace('/line-height\s*:\s*1\.15\b/i', 'line-height:1.6', $style);

            if (stripos($style, 'word-wrap:') === false) {
                $style .= ';word-wrap:break-word;';
            }

            return 'style=' . $quote . $style . $quote;
        }, $html);

        return preg_replace('/<p([^>]*)>(&nbsp;|\s|<br\s*\/?>)*<\/p>/i', '<p$1>&nbsp;</p>', $html);
    }

    protected function preserveBlankEditorBlocks($html)
    {
        return preg_replace_callback('/<(p|div)([^>]*)>(.*?)<\/\1>/is', function ($matches) {
            $tag = $matches[1];
            $attributes = $matches[2];
            $innerHtml = $matches[3];

            if (!$this->isBlankEditorBlock($innerHtml)) {
                return $matches[0];
            }

            if (stripos($attributes, 'class=') !== false) {
                $attributes = preg_replace('/class=(["\'])(.*?)\1/i', 'class=$1$2 peticao-empty-line$1', $attributes, 1);
            } else {
                $attributes .= ' class="peticao-empty-line"';
            }

            return '<' . $tag . $attributes . '>&nbsp;</' . $tag . '>';
        }, $html);
    }

    protected function isBlankEditorBlock($innerHtml)
    {
        if (stripos($innerHtml, '<img') !== false) {
            return false;
        }

        $normalized = preg_replace('/<br\s*\/?>/i', '', $innerHtml);
        $normalized = preg_replace('/&nbsp;|&#160;/i', '', $normalized);
        $normalized = preg_replace('/<span\b[^>]*>\s*<\/span>/i', '', $normalized);
        $normalized = trim(strip_tags($normalized));

        return $normalized === '';
    }

    protected function normalizeWordMarkup($html)
    {
        $html = preg_replace_callback('/<table\b[^>]*>.*?<img\b[^>]*src=.*?<\/table>/is', function ($matches) {
            $table = $matches[0];
            $table = preg_replace('/<table\b([^>]*)>/i', '<table$1 class="word-header-table">', $table, 1);

            $table = preg_replace_callback('/<p\b([^>]*)style=(["\'])(.*?)\2([^>]*)>(.*?)<\/p>/is', function ($pMatches) {
                $style = html_entity_decode($pMatches[3], ENT_QUOTES, 'UTF-8');
                if (stripos($style, 'text-align: right') === false) {
                    return $pMatches[0];
                }

                return '<p class="word-header-contact">' . $pMatches[5] . '</p>';
            }, $table);

            return $table;
        }, $html);

        $html = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
            $style = preg_replace('/mso-[^:]+:[^;]+;?/i', '', $style);
            $style = preg_replace('/line-height\s*:\s*115%/i', 'line-height:160%', $style);
            $style = preg_replace('/line-height\s*:\s*1\.15\b/i', 'line-height:1.6', $style);
            $style = preg_replace('/font-size\s*:\s*11px/i', 'font-size:11pt', $style);
            $style = preg_replace('/font-size\s*:\s*9px/i', 'font-size:9pt', $style);

            return 'style=' . $quote . trim($style) . $quote;
        }, $html);

        return $html;
    }

    protected function normalizePrintMarkup($html)
    {
        return preg_replace_callback('/<table\b[^>]*>.*?<img\b[^>]*src=.*?<\/table>/is', function ($matches) {
            $table = $matches[0];
            $table = preg_replace('/<table\b([^>]*)>/i', '<table$1 class="print-header-table">', $table, 1);

            $table = preg_replace_callback('/<p\b([^>]*)style=(["\'])(.*?)\2([^>]*)>(.*?)<\/p>/is', function ($pMatches) {
                $style = html_entity_decode($pMatches[3], ENT_QUOTES, 'UTF-8');
                if (stripos($style, 'text-align: right') === false) {
                    return $pMatches[0];
                }

                return '<p class="print-header-contact">' . $pMatches[5] . '</p>';
            }, $table);

            return $table;
        }, $html);
    }

    protected function composePrintDocumentHtml($headerHtml, $bodyHtml, $footerHtml)
    {
        $sections = array_filter([
            trim((string) $headerHtml) !== '' ? '<div class="peticao-print-header-inline">' . $headerHtml . '</div>' : null,
            $bodyHtml,
            trim((string) $footerHtml) !== '' ? '<div class="peticao-print-footer-inline">' . $footerHtml . '</div>' : null,
        ]);

        return implode("\n", $sections);
    }

    protected function prepareExportFragment($html, $assetMode, $context)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        $html = $this->preserveBlankEditorBlocks(
            $this->preserveAlignedSpacingMarkup(
                $this->normalizeAssetImageSrc($html, $assetMode)
            )
        );

        if ($context === 'print' || $context === 'pdf') {
            return $this->normalizePrintMarkup($html);
        }

        if ($context === 'word') {
            return $this->normalizeWordMarkup($html);
        }

        if ($context === 'playwright-template') {
            return $this->normalizePlaywrightTemplateMarkup($html);
        }

        return $html;
    }

    protected function preparePlaywrightLayout(array $layout)
    {
        $bodyHtml = (string) ($layout['body_html'] ?? '');
        $headerHtml = (string) ($layout['header_html'] ?? '');
        $footerHtml = (string) ($layout['footer_html'] ?? '');

        $bodyHtml = $this->stripEmbeddedHeaderFooter($bodyHtml, $headerHtml, $footerHtml);
        $preparedHeaderHtml = $this->prepareExportFragment($headerHtml, 'inline', 'playwright-template');
        $preparedFooterHtml = $this->prepareExportFragment($footerHtml, 'inline', 'playwright-template');

        if ($this->hasConfiguredPlaywrightFont()) {
            $bodyHtml = $this->stripInlineFontFamily($bodyHtml);
            $preparedHeaderHtml = $this->stripInlineFontFamily($preparedHeaderHtml);
            $preparedFooterHtml = $this->stripInlineFontFamily($preparedFooterHtml);
        }

        return [
            'title' => (string) ($layout['title'] ?? 'peticao'),
            'body_html' => $this->preserveBlankEditorBlocks(
                $this->normalizePrintMarkup(
                    $this->preserveAlignedSpacingMarkup(
                        $this->normalizeAssetImageSrc($bodyHtml, 'inline')
                    )
                )
            ),
            'header_html' => $preparedHeaderHtml,
            'footer_html' => $preparedFooterHtml,
            'header_defaults' => $this->extractPlaywrightTemplateDefaults($preparedHeaderHtml),
            'footer_defaults' => $this->extractPlaywrightTemplateDefaults($preparedFooterHtml),
            'meta' => $layout['meta'] ?? [],
        ];
    }

    protected function normalizePlaywrightTemplateMarkup($html)
    {
        $html = preg_replace_callback('/<table\b([^>]*)>/i', function ($matches) {
            $attributes = $matches[1];

            if (stripos($attributes, 'style=') !== false) {
                $attributes = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($styleMatches) {
                    $quote = $styleMatches[1];
                    $style = rtrim(html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8'), ';');
                    $style .= ';width:100%;border-collapse:collapse;border-spacing:0;table-layout:fixed;';

                    return 'style=' . $quote . $style . $quote;
                }, $attributes, 1);
            } else {
                $attributes .= ' style="width:100%;border-collapse:collapse;border-spacing:0;table-layout:fixed;"';
            }

            return '<table' . $attributes . '>';
        }, $html, 1);

        $cellIndex = 0;
        $html = preg_replace_callback('/<td\b([^>]*)>/i', function ($matches) use (&$cellIndex) {
            $attributes = $matches[1];
            $cellIndex++;

            $extraStyle = $cellIndex === 1
                ? 'width:34%;vertical-align:middle;text-align:left;padding:0;'
                : 'width:66%;vertical-align:middle;text-align:right;padding:0;';

            if (stripos($attributes, 'style=') !== false) {
                $attributes = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($styleMatches) use ($extraStyle) {
                    $quote = $styleMatches[1];
                    $style = rtrim(html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8'), ';');
                    $style .= ';' . $extraStyle;

                    return 'style=' . $quote . $style . $quote;
                }, $attributes, 1);
            } else {
                $attributes .= ' style="' . $extraStyle . '"';
            }

            return '<td' . $attributes . '>';
        }, $html);

        $html = preg_replace('/<img\b([^>]*)>/i', '<img$1 style="max-width:100%;height:auto;display:block;" />', $html, 1);

        $html = preg_replace_callback('/<p\b([^>]*)>(.*?)<\/p>/is', function ($matches) {
            $attributes = $matches[1];
            $content = $matches[2];
            $style = 'margin:0;';

            if ($this->isRightAlignedBlock($attributes)) {
                $style .= 'text-align:right;width:100%;white-space:normal;';
            }

            if (stripos($attributes, 'style=') !== false) {
                $attributes = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($styleMatches) use ($style) {
                    $quote = $styleMatches[1];
                    $current = rtrim(html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8'), ';');
                    $current .= ';' . $style;

                    return 'style=' . $quote . $current . $quote;
                }, $attributes, 1);
            } else {
                $attributes .= ' style="' . $style . '"';
            }

            return '<p' . $attributes . '>' . $content . '</p>';
        }, $html);

        return $html;
    }

    protected function extractPlaywrightTemplateDefaults($html)
    {
        $html = (string) $html;
        $defaults = [];

        if (!preg_match_all('/style=(["\'])(.*?)\1/i', $html, $matches)) {
            return $defaults;
        }

        $propertyMap = [
            'font-size' => 'fontSize',
            'line-height' => 'lineHeight',
            'color' => 'color',
            'font-family' => 'fontFamily',
            'font-weight' => 'fontWeight',
            'letter-spacing' => 'letterSpacing',
            'text-transform' => 'textTransform',
        ];

        foreach ($matches[2] as $styleBlock) {
            $styleBlock = html_entity_decode($styleBlock, ENT_QUOTES, 'UTF-8');
            foreach (explode(';', $styleBlock) as $declaration) {
                if (strpos($declaration, ':') === false) {
                    continue;
                }

                [$property, $value] = array_map('trim', explode(':', $declaration, 2));
                $property = strtolower($property);
                if ($property === '' || $value === '' || !isset($propertyMap[$property])) {
                    continue;
                }

                $defaults[$propertyMap[$property]] = $value;
            }
        }

        return $defaults;
    }

    protected function hasConfiguredPlaywrightFont()
    {
        return trim((string) config('pdf.playwright.font_family', '')) !== ''
            && trim((string) config('pdf.playwright.font_regular_path', '')) !== '';
    }

    protected function stripInlineFontFamily($html)
    {
        if (!is_string($html) || trim($html) === '') {
            return $html;
        }

        return preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

            $style = preg_replace('/font-family\s*:\s*[^;]+;?/i', '', $style);
            $style = preg_replace('/;;+/', ';', $style);
            $style = trim($style);
            $style = trim($style, ';');

            if ($style === '') {
                return '';
            }

            return 'style=' . $quote . $style . $quote;
        }, $html);
    }

    protected function stripEmbeddedHeaderFooter($conteudoHtml, $cabecalhoHtml = null, $rodapeHtml = null)
    {
        $conteudoHtml = (string) $conteudoHtml;

        if (trim((string) $cabecalhoHtml) !== '') {
            $conteudoHtml = preg_replace('/^\s*' . preg_quote(trim((string) $cabecalhoHtml), '/') . '\s*/s', '', $conteudoHtml, 1);
            $conteudoHtml = $this->stripLeadingImageHeaderBlock($conteudoHtml);
        }

        if (trim((string) $rodapeHtml) !== '') {
            $conteudoHtml = preg_replace('/\s*' . preg_quote(trim((string) $rodapeHtml), '/') . '\s*$/s', '', $conteudoHtml, 1);
            $conteudoHtml = $this->stripTrailingImageFooterBlock($conteudoHtml);
        }

        return $conteudoHtml;
    }

    protected function stripLeadingImageHeaderBlock($html)
    {
        $patterns = [
            '/^\s*<div\b[^>]*>\s*(<table\b[^>]*>.*?<img\b[^>]*>.*?<\/table>)\s*<\/div>\s*/is',
            '/^\s*(<table\b[^>]*>.*?<img\b[^>]*>.*?<\/table>)\s*/is',
        ];

        foreach ($patterns as $pattern) {
            $updated = preg_replace($pattern, '', $html, 1, $count);
            if ($count > 0) {
                return $updated;
            }
        }

        return $html;
    }

    protected function stripTrailingImageFooterBlock($html)
    {
        $patterns = [
            '/\s*<div\b[^>]*>\s*(<table\b[^>]*>.*?<img\b[^>]*>.*?<\/table>)\s*<\/div>\s*$/is',
            '/\s*(<table\b[^>]*>.*?<img\b[^>]*>.*?<\/table>)\s*$/is',
        ];

        foreach ($patterns as $pattern) {
            $updated = preg_replace($pattern, '', $html, 1, $count);
            if ($count > 0) {
                return $updated;
            }
        }

        return $html;
    }

    protected function preserveAlignedSpacingMarkup($html)
    {
        foreach (['p', 'div', 'td'] as $tag) {
            $pattern = '/<' . $tag . '\b([^>]*)>(.*?)<\/' . $tag . '>/is';

            $html = preg_replace_callback($pattern, function ($matches) use ($tag) {
                $attributes = $matches[1];
                $innerHtml = $matches[2];

                if (!$this->isRightAlignedBlock($attributes) || !$this->hasIntentionalSpacing($innerHtml)) {
                    return $matches[0];
                }

                $attributes = $this->ensureWhiteSpaceMode($attributes, 'normal');
                $innerHtml = $this->normalizeRightAlignedSpacing($innerHtml);

                return '<' . $tag . $attributes . '>' . $innerHtml . '</' . $tag . '>';
            }, $html);
        }

        return $html;
    }

    protected function isRightAlignedBlock($attributes)
    {
        return (bool) preg_match('/\balign\s*=\s*(["\'])?right\1?/i', $attributes)
            || (bool) preg_match('/text-align\s*:\s*right/i', html_entity_decode($attributes, ENT_QUOTES, 'UTF-8'));
    }

    protected function hasIntentionalSpacing($innerHtml)
    {
        return preg_match('/ {2,}|&nbsp;|&#160;/i', $innerHtml) === 1;
    }

    protected function ensureWhiteSpaceMode($attributes, $mode)
    {
        if (preg_match('/style=(["\'])(.*?)\1/i', $attributes, $matches)) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

            if (stripos($style, 'white-space:') === false) {
                $style = rtrim(trim($style), ';') . ';white-space:' . $mode . ';';
            } else {
                $style = preg_replace('/white-space\s*:\s*[^;]+;?/i', 'white-space:' . $mode . ';', $style);
            }

            return preg_replace('/style=(["\'])(.*?)\1/i', 'style=' . $quote . $style . $quote, $attributes, 1);
        }

        return $attributes . ' style="white-space:' . $mode . ';"';
    }

    protected function normalizeRightAlignedSpacing($innerHtml)
    {
        $innerHtml = preg_replace('/\s*<br\s*\/?>\s*/i', '<br />', $innerHtml);
        $innerHtml = preg_replace('/[\r\n\t]+/', '', $innerHtml);
        $innerHtml = preg_replace('/(^|<br\s*\/?>|\r?\n)(?:\s|&nbsp;|&#160;)+/i', '$1', $innerHtml);
        $innerHtml = preg_replace('/(?:\s|&nbsp;|&#160;)+(<br\s*\/?>|\r?\n|$)/i', '$1', $innerHtml);

        return $innerHtml;
    }
}
