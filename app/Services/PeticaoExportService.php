<?php

namespace App\Services;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Shared\Html as PhpWordHtml;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use RuntimeException;
use Throwable;

class PeticaoExportService
{
    public function exportWordFromLayout(array $layout)
    {
        $filename = $this->sanitizeFileName($layout['title'] ?? 'peticao') . '.docx';
        $content = $this->renderWordDocumentFromLayout($layout);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
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
            $content = $this->normalizePdfBinary($content);

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

        $conteudoHtml = $this->normalizeContentFontSize($conteudoHtml);
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
        return $this->renderWordDocumentFromLayout([
            'title' => $nomeArquivo,
            'body_html' => $conteudoHtml,
            'header_html' => $cabecalhoHtml,
            'footer_html' => $rodapeHtml,
        ]);
    }

    public function renderWordDocumentFromLayout(array $layout)
    {
        $editorCss = file_exists(public_path('ckeditor/contents.css'))
            ? $this->scopeEditorPrintCss(file_get_contents(public_path('ckeditor/contents.css')))
            : '';

        $bodyHtml = (string) ($layout['body_html'] ?? '');
        $headerHtml = (string) ($layout['header_html'] ?? '');
        $footerHtml = (string) ($layout['footer_html'] ?? '');
        $bodyHtml = $this->normalizeContentFontSize($bodyHtml);
        $rawHeaderHtml = $this->normalizeAssetImageSrc($headerHtml, 'filesystem');
        $rawFooterHtml = $this->normalizeAssetImageSrc($footerHtml, 'filesystem');

        $conteudoSemMoldura = $this->stripEmbeddedHeaderFooter($bodyHtml, $headerHtml, $footerHtml);
        $documentHtml = $this->preparePhpWordHtmlFragment(
            $this->preserveBlankEditorBlocks(
                $this->normalizeDocxMarkup(
                    $this->preserveAlignedSpacingMarkup(
                        $this->normalizeAssetImageSrc($conteudoSemMoldura, 'filesystem')
                    ),
                    false
                )
            ),
            $editorCss . "\n" . $this->buildDocxStyles()
        );
        $preparedHeaderHtml = $this->preparePhpWordHtmlFragment(
            $this->normalizeDocxMarkup(
                $this->preserveAlignedSpacingMarkup(
                    $rawHeaderHtml
                ),
                true
            ),
            $this->buildDocxStyles()
        );
        $preparedFooterHtml = $this->preparePhpWordHtmlFragment(
            $this->normalizeDocxMarkup(
                $this->preserveAlignedSpacingMarkup(
                    $rawFooterHtml
                ),
                true
            ),
            $this->buildDocxStyles()
        );

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);
        $phpWord->setDefaultParagraphStyle([
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1.6,
            'spacingLineRule' => \PhpOffice\PhpWord\SimpleType\LineSpacingRule::AUTO,
            'contextualSpacing' => false,
        ]);

        $phpWordTempDir = storage_path('app/phpword-temp');
        if (!is_dir($phpWordTempDir) && !@mkdir($phpWordTempDir, 0777, true) && !is_dir($phpWordTempDir)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio temporario do PHPWord.');
        }
        PhpWordSettings::setTempDir($phpWordTempDir);

        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2.54),
            'marginRight' => Converter::cmToTwip(1.69),
            'marginBottom' => Converter::cmToTwip(2.54),
            'marginLeft' => Converter::cmToTwip(1.69),
            'headerHeight' => Converter::cmToTwip(0.64),
            'footerHeight' => Converter::cmToTwip(0.64),
        ]);

        if (trim($rawHeaderHtml) !== '') {
            $headerContainer = $section->addHeader();
            if (!$this->appendNativeDocxHeaderFooter($headerContainer, $rawHeaderHtml)) {
                $this->appendPhpWordHtml($headerContainer, $preparedHeaderHtml, true);
            }
        }

        if (trim($rawFooterHtml) !== '') {
            $footerContainer = $section->addFooter();
            if (!$this->appendNativeDocxHeaderFooter($footerContainer, $rawFooterHtml)) {
                $this->appendPhpWordHtml($footerContainer, $preparedFooterHtml, true);
            }
        }

        if (!$this->appendNativeDocxBody($section, $documentHtml)) {
            $this->appendPhpWordHtml($section, $documentHtml, false);
        }

        $tempDir = storage_path('app/word-docx');
        if (!is_dir($tempDir) && !@mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio temporario do DOCX.');
        }

        $filename = $this->sanitizeFileName($layout['title'] ?? 'peticao');
        $docxPath = $tempDir . DIRECTORY_SEPARATOR . $filename . '_' . str_replace('.', '', uniqid('', true)) . '.docx';

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($docxPath);

            if (!file_exists($docxPath) || filesize($docxPath) === 0) {
                throw new RuntimeException('O DOCX nao foi gerado.');
            }

            return file_get_contents($docxPath);
        } finally {
            @unlink($docxPath);
        }
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

            return response($this->normalizePdfBinary(file_get_contents($pdfPath)), 200, [
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

        return response($this->normalizePdfBinary($pdf->Output($this->sanitizeFileName($nomeArquivo) . '.pdf', 'S')), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFileName($nomeArquivo) . '.pdf"',
            'X-Peticao-Pdf-Engine' => 'html2pdf',
        ]);
    }

    protected function shouldUseBrowserPdf()
    {
        return strtolower((string) config('pdf.engine', 'browser')) === 'browser';
    }

    protected function normalizePdfBinary($content)
    {
        $content = (string) $content;
        $pdfOffset = strpos($content, '%PDF-');

        if ($pdfOffset === false) {
            throw new RuntimeException('O arquivo gerado nao possui uma assinatura PDF valida.');
        }

        if ($pdfOffset > 0) {
            $prefix = substr($content, 0, $pdfOffset);
            $allowedPrefix = "\xEF\xBB\xBF\x00\x09\x0A\x0D\x20";

            if (trim($prefix, $allowedPrefix) !== '') {
                throw new RuntimeException('Foram encontrados dados inesperados antes da assinatura PDF.');
            }

            $content = substr($content, $pdfOffset);
        }

        return $content;
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

    protected function buildDocxStyles()
    {
        return implode("\n", [
            'html, body { margin: 0; padding: 0; background: #ffffff; color: #1f2933; font-family: Arial, Helvetica, sans-serif; }',
            'body { word-wrap: break-word; }',
            'p, div, td, th, li, span, strong, u { line-height: 1.6; }',
            'p { margin: 0 0 12px; text-align: justify; }',
            'td p { margin: 0; }',
            'img { max-width: 100%; height: auto; }',
            'table { border-collapse: collapse; border-spacing: 0; max-width: 100%; }',
            '.word-header-table { width: 100%; table-layout: fixed; border-collapse: collapse; }',
            '.word-header-table td { vertical-align: middle; padding: 0; }',
            '.word-header-table td:first-child { width: 34%; text-align: left; }',
            '.word-header-table td:last-child { width: 66%; text-align: right; }',
            '.word-header-contact { width: 100%; margin: 0; font-size: 9px; line-height: 1.15; text-align: right; white-space: normal; }',
            '.word-header-contact span { white-space: inherit !important; }',
            '.peticao-empty-line { margin: 0; line-height: 1; }',
        ]);
    }

    protected function preparePhpWordHtmlFragment($html, $css = '')
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $css = trim((string) $css);
        $wrappedHtml = '<html><head><meta charset="utf-8" />';
        if ($css !== '') {
            $wrappedHtml .= '<style type="text/css">' . $css . '</style>';
        }
        $wrappedHtml .= '</head><body>' . $html . '</body></html>';

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xml = $dom->saveXML($dom->documentElement);

        if (!is_string($xml) || trim($xml) === '') {
            throw new RuntimeException('Nao foi possivel normalizar o HTML para exportacao DOCX.');
        }

        return $xml;
    }

    protected function appendPhpWordHtml($container, $html, $preferNativeTable = false)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return;
        }

        if ($preferNativeTable && $this->appendNativePhpWordTable($container, $html)) {
            return;
        }

        PhpWordHtml::addHtml($container, $html, true, true);
    }

    protected function appendNativeDocxBody($container, $html)
    {
        if (!class_exists('DOMDocument')) {
            return false;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return false;
        }

        $xpath = new \DOMXPath($dom);
        $body = $xpath->query('//body')->item(0);
        $scope = $body ?: $dom->documentElement;
        if (!$scope) {
            return false;
        }

        $handled = false;

        foreach ($scope->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE && trim($childNode->nodeValue) === '') {
                continue;
            }

            if ($childNode->nodeType !== XML_ELEMENT_NODE) {
                $handled = false;
                continue;
            }

            $tag = strtolower($childNode->nodeName);

            if ($tag === 'table') {
                $handled = $this->appendNativePhpWordTable($container, $dom->saveHTML($childNode)) || $handled;
                continue;
            }

            if (in_array($tag, ['p', 'div', 'li'], true)) {
                if ($this->nodeContainsOnlyImage($childNode)) {
                    $imageNode = $this->getFirstDescendantByTagName($childNode, 'img');
                    if ($imageNode instanceof \DOMElement) {
                        $this->appendNativeDocxImage($container, $imageNode, $childNode);
                        $handled = true;
                        continue;
                    }
                }

                if ($this->isDocxEmptyParagraphNode($childNode)) {
                    $this->appendNativeDocxEmptyParagraph($container, $childNode);
                    $handled = true;
                    continue;
                }

                $this->appendNativeDocxTextBlock($container, $childNode);
                $handled = true;
                continue;
            }

            $handled = false;
        }

        return $handled;
    }

    protected function appendNativeDocxHeaderFooter($container, $html)
    {
        if (!class_exists('DOMDocument')) {
            return false;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return false;
        }

        $xpath = new \DOMXPath($dom);
        $tableNode = $xpath->query('//body//table')->item(0);
        if ($tableNode instanceof \DOMElement) {
            return $this->appendNativePhpWordTable($container, $dom->saveHTML($tableNode));
        }

        $paragraphNodes = $xpath->query('//body/*[self::p or self::div]');
        if ($paragraphNodes->length === 0) {
            return false;
        }

        $handled = false;
        foreach ($paragraphNodes as $paragraphNode) {
            if ($paragraphNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($paragraphNode->nodeName);
            if (($tag === 'p' || $tag === 'div') && $this->nodeContainsOnlyImage($paragraphNode)) {
                $imageNode = $xpath->query('.//img', $paragraphNode)->item(0);
                if ($imageNode instanceof \DOMElement) {
                    $this->appendNativeDocxImage($container, $imageNode, $paragraphNode);
                    $handled = true;
                }
                continue;
            }

            $lines = $this->extractDocxTextLines($paragraphNode);
            if (!empty($lines)) {
                $this->appendNativeDocxTextBlock($container, $paragraphNode);
                $handled = true;
            }
        }

        return $handled;
    }

    protected function appendNativePhpWordTable($container, $html)
    {
        if (!class_exists('DOMDocument')) {
            return false;
        }

        $tableNode = $this->extractSingleTableNodeFromHtml($html);
        if (!$tableNode) {
            return false;
        }

        $contentWidthTwip = $this->getDocxContentWidthTwip();

        $table = $container->addTable([
            'width' => $contentWidthTwip,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMarginTop' => 0,
            'cellMarginRight' => 0,
            'cellMarginBottom' => 0,
            'cellMarginLeft' => 0,
        ]);

        $hasRows = false;
        foreach ($tableNode->childNodes as $sectionNode) {
            if ($sectionNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if (strtolower($sectionNode->nodeName) === 'tr') {
                $hasRows = $this->appendNativePhpWordTableRow($table, $sectionNode, $contentWidthTwip) || $hasRows;
                continue;
            }

            foreach ($sectionNode->childNodes as $rowNode) {
                if ($rowNode->nodeType === XML_ELEMENT_NODE && strtolower($rowNode->nodeName) === 'tr') {
                    $hasRows = $this->appendNativePhpWordTableRow($table, $rowNode, $contentWidthTwip) || $hasRows;
                }
            }
        }

        return $hasRows;
    }

    protected function appendNativePhpWordTableRow($table, \DOMNode $rowNode, $contentWidthTwip = null)
    {
        $cells = [];
        foreach ($rowNode->childNodes as $cellNode) {
            if ($cellNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($cellNode->nodeName);
            if ($tag !== 'td' && $tag !== 'th') {
                continue;
            }

            $cells[] = $cellNode;
        }

        if (empty($cells)) {
            return false;
        }

        $table->addRow();
        $fallbackWidths = $this->resolveNativePhpWordRowFallbackWidths($cells, $contentWidthTwip);

        foreach ($cells as $index => $cellNode) {
            $cell = $table->addCell(
                $this->resolveNativePhpWordCellWidth($cellNode) ?: ($fallbackWidths[$index] ?? null),
                [
                    'valign' => $this->resolveNativePhpWordVAlign($cellNode),
                    'borderSize' => 0,
                    'borderColor' => 'FFFFFF',
                ]
            );

            $this->appendNativeDocxCellContent($cell, $cellNode);
        }

        return true;
    }

    protected function resolveNativePhpWordRowFallbackWidths(array $cells, $contentWidthTwip)
    {
        if ($contentWidthTwip === null || count($cells) !== 2) {
            return [];
        }

        $firstWidth = (int) round($contentWidthTwip * 0.34);

        return [
            $firstWidth,
            max(0, $contentWidthTwip - $firstWidth),
        ];
    }

    protected function appendNativeDocxCellContent($cell, \DOMNode $cellNode)
    {
        if ($this->nodeContainsOnlyImage($cellNode)) {
            $imageNode = $this->getFirstDescendantByTagName($cellNode, 'img');
            if ($imageNode instanceof \DOMElement) {
                $this->appendNativeDocxImage($cell, $imageNode, $cellNode);

                return true;
            }
        }

        $handled = false;

        foreach ($cellNode->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE && trim($childNode->nodeValue) !== '') {
                $this->appendNativeDocxTextBlock($cell, $cellNode);
                return true;
            }

            if ($childNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($childNode->nodeName);

            if ($tag === 'img') {
                $this->appendNativeDocxImage($cell, $childNode, $cellNode);
                $handled = true;
                continue;
            }

            if (in_array($tag, ['p', 'div'], true)) {
                if ($this->nodeContainsOnlyImage($childNode)) {
                    $imageNode = $this->getFirstDescendantByTagName($childNode, 'img');
                    if ($imageNode instanceof \DOMElement) {
                        $this->appendNativeDocxImage($cell, $imageNode, $childNode);
                        $handled = true;
                    }
                    continue;
                }

                $this->appendNativeDocxTextBlock($cell, $childNode);
                $handled = true;
                continue;
            }
        }

        if (!$handled) {
            $innerHtml = trim($this->innerHtml($cellNode));
            if ($innerHtml !== '') {
                $this->appendNativeDocxTextBlock($cell, $cellNode);
            }
        }

        return $handled;
    }

    protected function nodeContainsOnlyImage(\DOMNode $node)
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($this->innerHtml($node))));
        $imageNode = $this->getFirstDescendantByTagName($node, 'img');

        return $imageNode instanceof \DOMElement && $text === '';
    }

    protected function getFirstDescendantByTagName(\DOMNode $node, $tagName)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if (strtolower($child->nodeName) === strtolower($tagName)) {
                return $child;
            }

            $found = $this->getFirstDescendantByTagName($child, $tagName);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    protected function appendNativeDocxImage($container, \DOMNode $imageNode, \DOMNode $contextNode = null)
    {
        $src = $this->resolveDocxImageSource($imageNode);
        if ($src === null) {
            return;
        }

        $style = [];
        $width = $this->resolveDocxImageDimension($imageNode, 'width');
        $height = $this->resolveDocxImageDimension($imageNode, 'height');

        if ($width !== null) {
            $style['width'] = $width;
        }

        if ($height !== null) {
            $style['height'] = $height;
        }

        $style['unit'] = 'px';

        $alignment = $this->resolveNativePhpWordAlignment($contextNode ?: $imageNode);
        if ($alignment !== null) {
            $style['alignment'] = $alignment;
        }

        $container->addImage($src, $style);
    }

    protected function appendNativeDocxTextBlock($container, \DOMNode $node)
    {
        $textStyle = $this->extractDocxTextStyle($node);
        $paragraphStyle = $this->resolveDocxParagraphStyle($node, $textStyle['lineHeight'] ?? null, false);
        $textRun = $container->addTextRun($paragraphStyle);
        $baseFontStyle = !empty($textStyle['font']) ? $textStyle['font'] : $this->getDefaultDocxBodyFontStyle();
        $hasContent = $this->appendNativeDocxInlineContent($textRun, $node, $baseFontStyle);

        if (!$hasContent) {
            $textRun->addText(' ', $baseFontStyle);
        }
    }

    protected function appendNativeDocxEmptyParagraph($container, \DOMNode $node)
    {
        $paragraphStyle = $this->resolveDocxParagraphStyle($node, 1.6, true);
        $fontStyle = $this->extractDocxTextStyle($node)['font'] ?: $this->getDefaultDocxBodyFontStyle();
        $container->addText(' ', $fontStyle, $paragraphStyle);
    }

    protected function appendNativeDocxInlineContent($container, \DOMNode $node, array $inheritedFontStyle = [])
    {
        $hasContent = false;

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = html_entity_decode($child->nodeValue, ENT_QUOTES, 'UTF-8');
                if ($text === '') {
                    continue;
                }

                $text = preg_replace('/\s+/u', ' ', $text);
                if ($text === '') {
                    continue;
                }

                $container->addText($text, $inheritedFontStyle);
                $hasContent = true;
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if ($tag === 'br') {
                $container->addTextBreak();
                $hasContent = true;
                continue;
            }

            if ($tag === 'img') {
                continue;
            }

            $childFontStyle = $this->mergeDocxFontStyles($inheritedFontStyle, $this->extractDocxOwnFontStyle($child));
            $hasContent = $this->appendNativeDocxInlineContent($container, $child, $childFontStyle) || $hasContent;
        }

        return $hasContent;
    }

    protected function extractDocxTextLines(\DOMNode $node)
    {
        $lines = [''];
        $this->collectDocxTextLines($node, $lines);

        return array_values(array_map(function ($line) {
            return preg_replace('/\s+/u', ' ', trim($line));
        }, array_filter($lines, function ($line) {
            return $line !== null;
        })));
    }

    protected function collectDocxTextLines(\DOMNode $node, array &$lines)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $lines[count($lines) - 1] .= html_entity_decode($child->nodeValue, ENT_QUOTES, 'UTF-8');
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if ($tag === 'br') {
                $lines[] = '';
                continue;
            }

            $this->collectDocxTextLines($child, $lines);
        }
    }

    protected function isDocxEmptyParagraphNode(\DOMNode $node)
    {
        $html = trim($this->innerHtml($node));
        if ($html === '') {
            return true;
        }

        $normalized = preg_replace('/<br\s*\/?>/i', '', $html);
        $normalized = preg_replace('/&nbsp;|&#160;/i', '', $normalized);
        $normalized = preg_replace('/<span\b[^>]*>\s*<\/span>/i', '', $normalized);
        $normalized = trim(strip_tags($normalized));

        return $normalized === '';
    }

    protected function resolveDocxParagraphStyle(\DOMNode $node, $lineHeight = null, $isEmptyParagraph = false)
    {
        $paragraphStyle = [
            'alignment' => $this->resolveNativePhpWordAlignment($node) ?: 'left',
            'spaceBefore' => 0,
            'spaceAfter' => $isEmptyParagraph ? 0 : Converter::pixelToTwip(12),
            'contextualSpacing' => false,
            'spacingLineRule' => \PhpOffice\PhpWord\SimpleType\LineSpacingRule::AUTO,
            'lineHeight' => $lineHeight ?: 1.6,
        ];

        if ($node->attributes && $node->attributes->getNamedItem('style')) {
            $style = html_entity_decode($node->attributes->getNamedItem('style')->nodeValue, ENT_QUOTES, 'UTF-8');
            $marginLeft = $this->extractCssPropertyValue($style, 'margin-left');
            $textIndent = $this->extractCssPropertyValue($style, 'text-indent');

            if ($marginLeft !== null) {
                $paragraphStyle['indentation'] = $paragraphStyle['indentation'] ?? [];
                $paragraphStyle['indentation']['left'] = $this->cssSizeToTwip($marginLeft);
            }

            if ($textIndent !== null) {
                $paragraphStyle['indentation'] = $paragraphStyle['indentation'] ?? [];
                $paragraphStyle['indentation']['firstLine'] = $this->cssSizeToTwip($textIndent);
            }
        }

        return $paragraphStyle;
    }

    protected function extractDocxOwnFontStyle(\DOMNode $node)
    {
        $style = [];
        $properties = [
            'font-size' => null,
            'font-family' => null,
            'font-weight' => null,
            'color' => null,
        ];

        if ($node->attributes && $node->attributes->getNamedItem('class')) {
            $className = strtolower(trim((string) $node->attributes->getNamedItem('class')->nodeValue));
            if (strpos($className, 'word-header-contact') !== false) {
                $properties['font-size'] = '9px';
                $properties['font-family'] = 'Tahoma, Geneva, sans-serif';
            }
        }

        if ($node->attributes && $node->attributes->getNamedItem('style')) {
            $styleValue = html_entity_decode($node->attributes->getNamedItem('style')->nodeValue, ENT_QUOTES, 'UTF-8');
            foreach (array_keys($properties) as $property) {
                $value = $this->extractCssPropertyValue($styleValue, $property);
                if ($value !== null) {
                    $properties[$property] = $value;
                }
            }
        }

        $tag = strtolower($node->nodeName);
        if (in_array($tag, ['strong', 'b'], true)) {
            $style['bold'] = true;
        }

        if (!empty($properties['font-family'])) {
            $style['name'] = trim(str_replace(['"', "'"], '', explode(',', $properties['font-family'])[0]));
        }

        if (!empty($properties['font-size'])) {
            $style['size'] = $this->cssSizeToDocxPoint($properties['font-size']);
        }

        if (!empty($properties['font-weight']) && preg_match('/bold|700|800|900/i', $properties['font-weight'])) {
            $style['bold'] = true;
        }

        if (!empty($properties['color'])) {
            $style['color'] = ltrim($this->normalizeCssColorToHex($properties['color']), '#');
        }

        return array_filter($style, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function mergeDocxFontStyles(array $baseStyle, array $overrideStyle)
    {
        return array_merge($baseStyle, $overrideStyle);
    }

    protected function getDefaultDocxBodyFontStyle()
    {
        return [
            'name' => 'Tahoma',
            'size' => 9,
        ];
    }

    protected function extractDocxTextStyle(\DOMNode $node)
    {
        $style = [
            'font' => [],
            'lineHeight' => null,
        ];

        $properties = [
            'font-size' => null,
            'font-family' => null,
            'font-weight' => null,
            'color' => null,
            'line-height' => null,
        ];

        $styleNodes = $this->collectElementNodeAndDescendants($node);

        foreach ($styleNodes as $styleNode) {
            if ($styleNode->attributes && $styleNode->attributes->getNamedItem('class')) {
                $className = strtolower(trim((string) $styleNode->attributes->getNamedItem('class')->nodeValue));
                if (strpos($className, 'word-header-contact') !== false) {
                    $properties['font-size'] = $properties['font-size'] ?? '9px';
                    $properties['line-height'] = $properties['line-height'] ?? '1.15';
                    $properties['font-family'] = $properties['font-family'] ?? 'Tahoma, Geneva, sans-serif';
                }
            }

            if (!$styleNode->attributes || !$styleNode->attributes->getNamedItem('style')) {
                continue;
            }

            $styleValue = $styleNode->attributes->getNamedItem('style')->nodeValue;
            foreach (array_keys($properties) as $property) {
                if ($properties[$property] === null) {
                    $properties[$property] = $this->extractCssPropertyValue($styleValue, $property);
                }
            }
        }

        if (!empty($properties['font-family'])) {
            $style['font']['name'] = trim(str_replace(['"', "'"], '', explode(',', $properties['font-family'])[0]));
        }

        if (!empty($properties['font-size'])) {
            $style['font']['size'] = $this->cssSizeToDocxPoint($properties['font-size']);
        }

        if (!empty($properties['font-weight']) && preg_match('/bold|700|800|900/i', $properties['font-weight'])) {
            $style['font']['bold'] = true;
        }

        if (!empty($properties['color'])) {
            $style['font']['color'] = ltrim($this->normalizeCssColorToHex($properties['color']), '#');
        }

        if (!empty($properties['line-height'])) {
            $style['lineHeight'] = $this->cssLineHeightToDocx($properties['line-height'], $style['font']['size'] ?? 11);
        }

        return $style;
    }

    protected function collectElementNodeAndDescendants(\DOMNode $node)
    {
        $nodes = [];

        if ($node->nodeType === XML_ELEMENT_NODE) {
            $nodes[] = $node;
        }

        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            foreach ($this->collectElementNodeAndDescendants($child) as $descendant) {
                $nodes[] = $descendant;
            }
        }

        return $nodes;
    }

    protected function resolveDocxImageSource(\DOMNode $imageNode)
    {
        if (!$imageNode->attributes || !$imageNode->attributes->getNamedItem('src')) {
            return null;
        }

        $src = html_entity_decode($imageNode->attributes->getNamedItem('src')->nodeValue, ENT_QUOTES, 'UTF-8');
        if (stripos($src, 'file://') === 0) {
            $src = preg_replace('#^file:(//)?#i', '', $src);
            $src = preg_replace('#^/([A-Za-z]:/)#', '$1', $src);
        }

        return is_file($src) ? $src : null;
    }

    protected function resolveDocxImageDimension(\DOMNode $imageNode, $property)
    {
        $value = null;

        if ($imageNode->attributes && $imageNode->attributes->getNamedItem($property)) {
            $value = $imageNode->attributes->getNamedItem($property)->nodeValue;
        }

        if ($value === null && $imageNode->attributes && $imageNode->attributes->getNamedItem('style')) {
            $value = $this->extractCssPropertyValue($imageNode->attributes->getNamedItem('style')->nodeValue, $property);
        }

        if ($value === null) {
            return null;
        }

        return $this->cssSizeToPixels($value);
    }

    protected function resolveNativePhpWordAlignment(\DOMNode $node = null)
    {
        if ($node === null || !$node->attributes) {
            return null;
        }

        $alignment = null;

        if ($node->attributes->getNamedItem('align')) {
            $alignment = strtolower(trim((string) $node->attributes->getNamedItem('align')->nodeValue));
        }

        if ($alignment === null && $node->attributes->getNamedItem('style')) {
            $alignment = strtolower((string) $this->extractCssPropertyValue($node->attributes->getNamedItem('style')->nodeValue, 'text-align'));
            if ($alignment === null || $alignment === '') {
                $float = strtolower((string) $this->extractCssPropertyValue($node->attributes->getNamedItem('style')->nodeValue, 'float'));
                if (in_array($float, ['left', 'right'], true)) {
                    $alignment = $float;
                }
            }
        }

        if ($alignment === null && $node->attributes->getNamedItem('class')) {
            $className = strtolower(trim((string) $node->attributes->getNamedItem('class')->nodeValue));
            if (strpos($className, 'word-header-contact') !== false) {
                $alignment = 'right';
            }
        }

        if ($alignment === 'justify') {
            return 'both';
        }

        if (in_array($alignment, ['left', 'right', 'center', 'both'], true)) {
            return $alignment;
        }

        return null;
    }

    protected function extractSingleTableNodeFromHtml($html)
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $body = $xpath->query('//body')->item(0);
        $scope = $body ?: $dom->documentElement;
        if (!$scope) {
            return null;
        }

        $table = $xpath->query('./table | ./div/table', $scope)->item(0);
        if ($table instanceof \DOMElement) {
            return $table;
        }

        $firstElement = $this->getFirstElementChild($scope);
        if ($firstElement && strtolower($firstElement->nodeName) === 'div') {
            $child = $this->getFirstElementChild($firstElement);
            if ($child && strtolower($child->nodeName) === 'table') {
                return $child;
            }
        }

        if ($firstElement && strtolower($firstElement->nodeName) === 'table') {
            return $firstElement;
        }

        return null;
    }

    protected function getFirstElementChild(\DOMNode $node)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                return $child;
            }
        }

        return null;
    }

    protected function resolveNativePhpWordCellWidth(\DOMNode $cellNode)
    {
        $width = null;

        if ($cellNode->attributes && $cellNode->attributes->getNamedItem('width')) {
            $width = $cellNode->attributes->getNamedItem('width')->nodeValue;
        }

        if ($width === null && $cellNode->attributes && $cellNode->attributes->getNamedItem('style')) {
            $styleWidth = $this->extractCssPropertyValue($cellNode->attributes->getNamedItem('style')->nodeValue, 'width');
            if ($styleWidth !== null) {
                $width = $styleWidth;
            }
        }

        if ($width === null) {
            return null;
        }

        $width = trim((string) $width);

        if (preg_match('/^(\d+(?:\.\d+)?)%$/', $width, $matches)) {
            return (int) round($this->getDocxContentWidthTwip() * ((float) $matches[1] / 100));
        }

        if (preg_match('/^(\d+(?:\.\d+)?)px$/i', $width, $matches)) {
            return (int) round(Converter::pixelToTwip((float) $matches[1]));
        }

        if (preg_match('/^(\d+(?:\.\d+)?)$/', $width, $matches)) {
            return (int) round((float) $matches[1]);
        }

        return null;
    }

    protected function getDocxContentWidthTwip()
    {
        return (int) round(Converter::cmToTwip(21 - 1.69 - 1.69));
    }

    protected function getDocxContentWidthPixels()
    {
        return 794 - (64 * 2);
    }

    protected function resolveNativePhpWordVAlign(\DOMNode $cellNode)
    {
        $value = null;

        if ($cellNode->attributes && $cellNode->attributes->getNamedItem('valign')) {
            $value = strtolower(trim((string) $cellNode->attributes->getNamedItem('valign')->nodeValue));
        }

        if ($value === null && $cellNode->attributes && $cellNode->attributes->getNamedItem('style')) {
            $styleValue = $this->extractCssPropertyValue($cellNode->attributes->getNamedItem('style')->nodeValue, 'vertical-align');
            if ($styleValue !== null) {
                $value = strtolower(trim((string) $styleValue));
            }
        }

        if ($value === 'middle') {
            return 'center';
        }

        if (in_array($value, ['top', 'center', 'bottom'], true)) {
            return $value;
        }

        return 'center';
    }

    protected function extractCssPropertyValue($style, $property)
    {
        if (!is_string($style) || trim($style) === '') {
            return null;
        }

        if (preg_match('/(?:^|;)\s*' . preg_quote($property, '/') . '\s*:\s*([^;]+)/i', html_entity_decode($style, ENT_QUOTES, 'UTF-8'), $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function cssSizeToDocxPoint($value)
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d+(?:\.\d+)?)px$/i', $value, $matches)) {
            return round(Converter::pixelToPoint((float) $matches[1]), 2);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)pt$/i', $value, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/^(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    protected function cssSizeToPixels($value)
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d+(?:\.\d+)?)px$/i', $value, $matches)) {
            return (int) round((float) $matches[1]);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)pt$/i', $value, $matches)) {
            return (int) round(((float) $matches[1]) / 0.75);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return (int) round((float) $matches[1]);
        }

        return null;
    }

    protected function cssSizeToTwip($value)
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d+(?:\.\d+)?)px$/i', $value, $matches)) {
            return (int) round(Converter::pixelToTwip((float) $matches[1]));
        }

        if (preg_match('/^(\d+(?:\.\d+)?)pt$/i', $value, $matches)) {
            return (int) round(Converter::pointToTwip((float) $matches[1]));
        }

        if (preg_match('/^(\d+(?:\.\d+)?)cm$/i', $value, $matches)) {
            return (int) round(Converter::cmToTwip((float) $matches[1]));
        }

        if (preg_match('/^(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return (int) round((float) $matches[1]);
        }

        return 0;
    }

    protected function cssLineHeightToDocx($value, $fontSize = 11)
    {
        $value = trim((string) $value);
        $fontSize = (float) $fontSize;

        if (preg_match('/^(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/^(\d+(?:\.\d+)?)%$/', $value, $matches)) {
            return round(((float) $matches[1]) / 100, 2);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)px$/i', $value, $matches)) {
            $points = Converter::pixelToPoint((float) $matches[1]);
            return $fontSize > 0 ? round($points / $fontSize, 2) : null;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)pt$/i', $value, $matches)) {
            return $fontSize > 0 ? round(((float) $matches[1]) / $fontSize, 2) : null;
        }

        return null;
    }

    protected function normalizeCssColorToHex($value)
    {
        $value = trim((string) $value);

        if (preg_match('/^#([0-9a-f]{3})$/i', $value, $matches)) {
            $chars = strtolower($matches[1]);
            return '#' . $chars[0] . $chars[0] . $chars[1] . $chars[1] . $chars[2] . $chars[2];
        }

        if (preg_match('/^#([0-9a-f]{6})$/i', $value, $matches)) {
            return '#' . strtoupper($matches[1]);
        }

        if (preg_match('/rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/i', $value, $matches)) {
            return sprintf('#%02X%02X%02X', $matches[1], $matches[2], $matches[3]);
        }

        return '#1F2933';
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

    protected function normalizeContentFontSize($html)
    {
        if (!is_string($html) || trim($html) === '') {
            return $html;
        }

        return preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
            $style = preg_replace('/font-size\s*:\s*11px\b/i', 'font-size:12px', $style);

            return 'style=' . $quote . trim($style) . $quote;
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
            $style = preg_replace('/font-size\s*:\s*11px/i', 'font-size:12pt', $style);
            $style = preg_replace('/font-size\s*:\s*9px/i', 'font-size:9pt', $style);

            return 'style=' . $quote . trim($style) . $quote;
        }, $html);

        return $html;
    }

    protected function normalizeDocxMarkup($html, $forHeaderFooter = false)
    {
        if (!$forHeaderFooter) {
            $html = $this->ensureDocxBodyLineHeight($html, '1.6');
        }

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

        $html = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) use ($forHeaderFooter) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
            $style = preg_replace('/mso-[^:]+:[^;]+;?/i', '', $style);

            $style = $this->convertCssPxToPt($style, [
                'margin',
                'margin-top',
                'margin-right',
                'margin-bottom',
                'margin-left',
                'padding',
                'padding-top',
                'padding-right',
                'padding-bottom',
                'padding-left',
                'text-indent',
            ]);

            if ($forHeaderFooter) {
                $style = $this->convertCssPxToPt($style, [
                    'font-size',
                    'width',
                    'height',
                ]);
            }

            return 'style=' . $quote . trim($style) . $quote;
        }, $html);

        return $html;
    }

    protected function normalizeDocxBlankBlocks($html)
    {
        return preg_replace_callback('/<(p|div)([^>]*)class=(["\'])([^"\']*\bpeticao-empty-line\b[^"\']*)\3([^>]*)>(?:&nbsp;|\s|<br\s*\/?>)*<\/\1>/i', function ($matches) {
            $tag = strtolower($matches[1]);
            $before = trim($matches[2] . ' ' . $matches[5]);
            $classValue = trim($matches[4]);

            if (preg_match('/\bstyle=(["\'])(.*?)\1/i', $before, $styleMatches)) {
                $quote = $styleMatches[1];
                $style = html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8');
                $style = rtrim(trim($style), ';');
                $style .= ($style === '' ? '' : '; ') . 'margin:0; font-size:1pt; line-height:12px; height:12px;';
                $attributes = preg_replace('/\bstyle=(["\'])(.*?)\1/i', 'style=' . $quote . $style . $quote, $before, 1);
            } else {
                $attributes = trim($before . ' style="margin:0; font-size:1pt; line-height:12px; height:12px;"');
            }

            $attributes = trim('class="' . $classValue . '" ' . $attributes);

            return '<' . $tag . ' ' . $attributes . '>&nbsp;</' . $tag . '>';
        }, $html);
    }

    protected function transformDocxIndentedBlocks($html)
    {
        return preg_replace_callback('/<(p|div)\b([^>]*)style=(["\'])(.*?)\3([^>]*)>(.*?)<\/\1>/is', function ($matches) {
            $tag = strtolower($matches[1]);
            $before = $matches[2];
            $quote = $matches[3];
            $style = html_entity_decode($matches[4], ENT_QUOTES, 'UTF-8');
            $after = $matches[5];
            $innerHtml = $matches[6];

            $marginLeft = $this->extractCssPropertyValue($style, 'margin-left');
            $indentPixels = $marginLeft !== null ? $this->cssSizeToPixels($marginLeft) : null;
            if ($indentPixels === null || $indentPixels <= 0) {
                return $matches[0];
            }

            $cleanStyle = $this->removeCssProperty($style, 'margin-left');
            $cleanStyle = $this->removeCssProperty($cleanStyle, 'padding-left');
            $cleanStyle = trim($cleanStyle);

            $styleAttribute = $cleanStyle !== '' ? ' style=' . $quote . $cleanStyle . $quote : '';
            $contentTag = '<' . $tag . $before . $styleAttribute . $after . '>' . $innerHtml . '</' . $tag . '>';
            $contentWidth = $this->getDocxContentWidthPixels();
            $width = min((int) $indentPixels, max(0, $contentWidth - 40));
            $remainingWidth = max(40, $contentWidth - $width);

            return '<table style="width:' . $contentWidth . 'px; border-collapse:collapse; table-layout:fixed;" width="' . $contentWidth . '" border="0" cellspacing="0" cellpadding="0"><tr><td style="width:' . $width . 'px; padding:0; font-size:1px; line-height:1;" width="' . $width . '">&nbsp;</td><td style="width:' . $remainingWidth . 'px; padding:0; vertical-align:top;" width="' . $remainingWidth . '">' . $contentTag . '</td></tr></table>';
        }, $html);
    }

    protected function ensureDocxBodyLineHeight($html, $lineHeight)
    {
        return preg_replace_callback('/<(p|div|li)\b([^>]*)>/i', function ($matches) use ($lineHeight) {
            $tag = $matches[1];
            $attributes = $matches[2];

            if (preg_match('/\bstyle=(["\'])(.*?)\1/i', $attributes, $styleMatches)) {
                $quote = $styleMatches[1];
                $style = html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8');

                if ($this->extractCssPropertyValue($style, 'line-height') === null) {
                    $style = rtrim(trim($style), ';');
                    $style = $style === '' ? 'line-height:' . $lineHeight : $style . '; line-height:' . $lineHeight;
                    $attributes = preg_replace('/\bstyle=(["\'])(.*?)\1/i', 'style=' . $quote . $style . $quote, $attributes, 1);
                }

                return '<' . $tag . $attributes . '>';
            }

            return '<' . $tag . $attributes . ' style="line-height:' . $lineHeight . ';">';
        }, $html);
    }

    protected function removeCssProperty($style, $property)
    {
        if (!is_string($style) || trim($style) === '') {
            return '';
        }

        $style = preg_replace('/(?:^|;)\s*' . preg_quote($property, '/') . '\s*:\s*[^;]+;?/i', ';', $style);
        $style = preg_replace('/;{2,}/', ';', $style);

        return trim($style, " \t\n\r\0\x0B;");
    }

    protected function convertCssPxToPt($style, array $properties)
    {
        foreach ($properties as $property) {
            $style = preg_replace_callback(
                '/(' . preg_quote($property, '/') . '\s*:\s*)([^;]+)/i',
                function ($matches) {
                    return $matches[1] . preg_replace_callback('/(\d+(?:\.\d+)?)px\b/i', function ($valueMatches) {
                        $points = round(((float) $valueMatches[1]) * 0.75, 2);
                        $points = rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.');

                        return $points . 'pt';
                    }, $matches[2]);
                },
                $style
            );
        }

        return $style;
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
        $html = preg_replace_callback('/<div\b([^>]*)>(\s*<img\b[^>]*>\s*)<\/div>/is', function ($matches) {
            $attributes = $matches[1];
            $content = $matches[2];

            if (!$this->isRightAlignedBlock($attributes)) {
                return $matches[0];
            }

            $divStyle = 'text-align:right;width:100%;';
            if (stripos($attributes, 'style=') !== false) {
                $attributes = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($styleMatches) use ($divStyle) {
                    $quote = $styleMatches[1];
                    $current = rtrim(html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8'), ';');
                    $current .= ';' . $divStyle;

                    return 'style=' . $quote . $current . $quote;
                }, $attributes, 1);
            } else {
                $attributes .= ' style="' . $divStyle . '"';
            }

            $content = preg_replace_callback('/<img\b([^>]*)>/i', function ($imgMatches) {
                return $this->appendInlineStyleToTag('img', $imgMatches[1], 'max-width:100%;height:auto;display:inline-block;margin-left:auto;margin-right:0;');
            }, $content, 1);

            return '<div' . $attributes . '>' . $content . '</div>';
        }, $html);

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

        $html = preg_replace_callback('/<img\b([^>]*)>/i', function ($matches) {
            return $this->appendInlineStyleToTag('img', $matches[1], 'max-width:100%;height:auto;display:inline-block;');
        }, $html);

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

    protected function appendInlineStyleToTag($tagName, $attributes, $extraStyle)
    {
        $attributes = (string) $attributes;
        $extraStyle = trim((string) $extraStyle, ';');

        if ($extraStyle === '') {
            return '<' . $tagName . $attributes . ' />';
        }

        if (stripos($attributes, 'style=') !== false) {
            $attributes = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($styleMatches) use ($extraStyle) {
                $quote = $styleMatches[1];
                $current = rtrim(html_entity_decode($styleMatches[2], ENT_QUOTES, 'UTF-8'), ';');
                $current .= ';' . $extraStyle;

                return 'style=' . $quote . $current . $quote;
            }, $attributes, 1);
        } else {
            $attributes .= ' style="' . $extraStyle . '"';
        }

        return '<' . $tagName . $attributes . ' />';
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
            $conteudoHtml = $this->stripTrailingFooterTextBlock($conteudoHtml, $rodapeHtml);
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

    protected function stripTrailingFooterTextBlock($html, $footerHtml)
    {
        $footerText = $this->normalizeComparableText($footerHtml);
        if ($footerText === '') {
            return $html;
        }

        if (!class_exists('DOMDocument')) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapperId = 'peticao-footer-strip-root';
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="' . $wrapperId . '">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $root = $dom->getElementById($wrapperId);
        if (!$root) {
            return $html;
        }

        $lastElement = $this->getLastElementChild($root);
        if (!$lastElement) {
            return $html;
        }

        $candidateText = $this->normalizeComparableText($dom->saveHTML($lastElement));
        if ($candidateText === '') {
            return $html;
        }

        $isFooterMatch = $candidateText === $footerText
            || strpos($candidateText, $footerText) !== false
            || strpos($footerText, $candidateText) !== false;

        if (!$isFooterMatch) {
            return $html;
        }

        $root->removeChild($lastElement);

        return $this->innerHtml($root);
    }

    protected function normalizeComparableText($html)
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    protected function getLastElementChild(\DOMNode $node)
    {
        for ($child = $node->lastChild; $child !== null; $child = $child->previousSibling) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                return $child;
            }
        }

        return null;
    }

    protected function innerHtml(\DOMNode $node)
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
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
