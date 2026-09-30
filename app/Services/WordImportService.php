<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class WordImportService
{
    public function importUploadedFile(UploadedFile $file)
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['doc', 'docx'], true)) {
            throw new RuntimeException('Formato de arquivo nao suportado. Envie um arquivo .doc ou .docx.');
        }

        $binary = $this->resolveBinary();
        if ($binary === null) {
            throw new RuntimeException('LibreOffice/soffice nao encontrado para importar arquivos Word.');
        }

        $baseTempDir = storage_path('app/word-import');
        if (!is_dir($baseTempDir) && !@mkdir($baseTempDir, 0777, true) && !is_dir($baseTempDir)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio temporario de importacao Word.');
        }

        $jobDir = $baseTempDir . DIRECTORY_SEPARATOR . str_replace('.', '', uniqid('word_import_', true));
        if (!@mkdir($jobDir, 0777, true) && !is_dir($jobDir)) {
            throw new RuntimeException('Nao foi possivel preparar o diretorio temporario do arquivo importado.');
        }

        $sourcePath = $jobDir . DIRECTORY_SEPARATOR . 'source.' . $extension;

        try {
            // HTTP uploads podem ser movidos; arquivos locais usados em testes ou
            // reprocessamentos devem ser copiados para preservar a origem.
            if (function_exists('is_uploaded_file') && is_uploaded_file($file->getPathname())) {
                $file->move($jobDir, basename($sourcePath));
            } elseif (!@copy($file->getPathname(), $sourcePath)) {
                throw new RuntimeException('Nao foi possivel copiar o arquivo Word para a area temporaria.');
            }
            $htmlPath = $this->convertWordToHtml($binary, $sourcePath, $jobDir);
            $html = file_get_contents($htmlPath);
            if ($html === false || trim($html) === '') {
                throw new RuntimeException('A conversao do arquivo Word nao gerou HTML legivel.');
            }

            $html = $this->normalizeHtmlEncoding($html);

            // Alguns documentos DOCX sao exportados pelo LibreOffice como texto
            // corrido, embora contenham tabelas. O PhpWord preserva a estrutura
            // de tabelas e celulas nesses casos e serve como segunda fonte HTML.
            if ($extension === 'docx') {
                $structuredHtml = $this->convertDocxWithPhpWord($sourcePath, $jobDir);
                if ($structuredHtml !== null && stripos($structuredHtml, '<table') !== false) {
                    $html = $structuredHtml;
                }
                $html = $this->appendDocxFootnotes($html, $sourcePath);
            }

            return $this->prepareImportedHtml($html, dirname($htmlPath));
        } finally {
            $this->deleteDirectory($jobDir);
        }
    }

    protected function resolveBinary()
    {
        $configured = trim((string) config('word-import.binary', ''));
        if ($configured !== '' && file_exists($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function convertWordToHtml($binary, $sourcePath, $outputDir)
    {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('O arquivo Word temporario nao foi encontrado para conversao.');
        }

        $profileDir = $outputDir . DIRECTORY_SEPARATOR . 'libreoffice-profile';
        $homeDir = $outputDir . DIRECTORY_SEPARATOR . 'home';
        $cacheDir = $homeDir . DIRECTORY_SEPARATOR . '.cache';
        $configDir = $homeDir . DIRECTORY_SEPARATOR . '.config';

        foreach ([$profileDir, $homeDir, $cacheDir, $configDir] as $dir) {
            if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('Nao foi possivel preparar o perfil temporario do LibreOffice.');
            }
        }

        $command = $this->buildLibreOfficeCommand(
            $binary,
            $sourcePath,
            $outputDir,
            $profileDir,
            $homeDir,
            $cacheDir,
            $configDir
        );

        $output = [];
        $exitCode = 0;
        @exec($command . ' 2>&1', $output, $exitCode);

        // Some LibreOffice Windows builds crash on documents containing footnotes
        // when the explicit StarWriter filter is requested. Retry with the generic
        // HTML filter before reporting the native process failure.
        if ($exitCode !== 0) {
            $output = [];
            $exitCode = 0;
            $retryCommand = $this->buildLibreOfficeCommand(
                $binary, $sourcePath, $outputDir,
                $profileDir . '-retry', $homeDir . '-retry', $cacheDir . '-retry', $configDir . '-retry', false
            );
            @exec($retryCommand . ' 2>&1', $output, $exitCode);
        }

        if ($exitCode !== 0) {
            $fallback = $this->extractDocxTextFallback($sourcePath, $outputDir);
            if ($fallback !== null) {
                return $fallback;
            }
            $details = trim(implode("\n", $output));
            if (filter_var($details, FILTER_VALIDATE_URL)) {
                $details = 'O conversor retornou uma URL em vez de executar o LibreOffice. Verifique WORD_IMPORT_BINARY e limpe o cache de configuracao.';
            }
            throw new RuntimeException('Falha ao converter o arquivo Word: ' . ($details ?: 'codigo de saida ' . $exitCode));
        }

        $basename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $matches = glob($outputDir . DIRECTORY_SEPARATOR . $basename . '.htm*');
        if (!$matches) {
            throw new RuntimeException('A conversao do arquivo Word nao gerou um arquivo HTML.');
        }

        return $matches[0];
    }

    protected function extractDocxTextFallback($sourcePath, $outputDir)
    {
        if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) !== 'docx' || !class_exists('ZipArchive')) {
            return null;
        }

        $zip = new \ZipArchive();
        $openResult = $zip->open($sourcePath);
        if ($openResult !== true && $openResult !== 1) {
            return null;
        }

        $parts = ['word/document.xml', 'word/footnotes.xml'];
        $html = '';
        foreach ($parts as $part) {
            $xml = $zip->getFromName($part);
            if ($xml === false) {
                continue;
            }
            $dom = new \DOMDocument('1.0', 'UTF-8');
            if (!@$dom->loadXML($xml)) {
                continue;
            }
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            foreach ($xpath->query('//w:p') as $paragraph) {
                $text = '';
                foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                    $text .= $node->textContent;
                }
                if (trim($text) !== '') {
                    $html .= '<p>' . htmlspecialchars($this->normalizeExtractedText($text), ENT_QUOTES, 'UTF-8') . '</p>';
                }
            }
        }
        $zip->close();

        if (trim($html) === '') {
            return null;
        }

        $path = $outputDir . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '.html';
        file_put_contents($path, '<html><body>' . $html . '</body></html>');
        return $path;
    }

    protected function convertDocxWithPhpWord($sourcePath, $outputDir)
    {
        if (!class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            return null;
        }

        try {
            $document = \PhpOffice\PhpWord\IOFactory::load($sourcePath);
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($document, 'HTML');
            $path = $outputDir . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '-phpword.html';
            $writer->save($path);
            $html = @file_get_contents($path);
            return $html !== false ? $this->normalizeHtmlEncoding($html) : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    protected function appendDocxFootnotes($html, $sourcePath)
    {
        if (!class_exists('ZipArchive')) {
            return $html;
        }
        $zip = new \ZipArchive();
        $opened = $zip->open($sourcePath);
        if ($opened !== true && $opened !== 1) {
            return $html;
        }
        $xml = $zip->getFromName('word/footnotes.xml');
        if ($xml === false) {
            $zip->close();
            return $html;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (!@$dom->loadXML($xml)) {
            $zip->close();
            return $html;
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $notes = [];
        foreach ($xpath->query('//w:footnote') as $note) {
            $id = (int) $note->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'id');
            if ($id < 0) {
                continue;
            }
            $text = trim($note->textContent);
            if ($text !== '') {
                $notes[] = '<p><sup>' . $id . '</sup> ' . htmlspecialchars($this->normalizeExtractedText($text), ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
        $zip->close();
        if (!$notes || stripos((string) $html, 'class="docx-footnotes"') !== false) {
            return $html;
        }
        return (string) $html . '<hr><section class="docx-footnotes"><h3>Referências</h3>' . implode('', $notes) . '</section>';
    }

    protected function normalizeExtractedText($text)
    {
        $text = (string) $text;
        if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
            if (function_exists('mb_convert_encoding')) {
                $converted = @mb_convert_encoding($text, 'UTF-8', ['Windows-1252', 'ISO-8859-1']);
                if ($converted !== false) {
                    return $converted;
                }
            }
        }
        return $text;
    }

    protected function buildLibreOfficeCommand($binary, $sourcePath, $outputDir, $profileDir, $homeDir, $cacheDir, $configDir, $explicitFilter = true)
    {
        foreach ([$profileDir, $homeDir, $cacheDir, $configDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
        }

        $conversion = $explicitFilter
            ? ' --convert-to ' . $this->quoteShellArgument('html:HTML (StarWriter)')
            : ' --convert-to html';

        $baseCommand = $this->quoteShellArgument($binary)
            . ' --headless --nologo --nodefault --nolockcheck --norestore'
            . ' -env:UserInstallation=' . $this->quoteShellArgument($this->pathToFileUri($profileDir))
            . $conversion . ' --outdir '
            . $this->quoteShellArgument($outputDir)
            . ' '
            . $this->quoteShellArgument($sourcePath);

        if (DIRECTORY_SEPARATOR === '\\') {
            return $baseCommand;
        }

        return 'HOME=' . $this->quoteShellArgument($homeDir)
            . ' XDG_CACHE_HOME=' . $this->quoteShellArgument($cacheDir)
            . ' XDG_CONFIG_HOME=' . $this->quoteShellArgument($configDir)
            . ' '
            . $baseCommand;
    }

    protected function prepareImportedHtml($html, $assetsDir)
    {
        $html = preg_replace('/<meta[^>]+charset=[^>]+>/i', '', $html);
        $html = preg_replace('/<meta[^>]+content=["\'][^"\']*charset=[^"\']*["\'][^>]*>/i', '', $html);

        $inlinedHtml = $this->inlineStyles($html);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8">' . $inlinedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return trim($inlinedHtml);
        }

        $this->inlineLocalImages($body, $assetsDir);

        $html = $this->innerHtml($body);
        $html = preg_replace('/<(?:meta|title|link)[^>]*>/i', '', $html);

        return trim((string) $html);
    }

    protected function inlineStyles($html)
    {
        if (!class_exists(CssToInlineStyles::class)) {
            return $html;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $css = '';
        foreach ($dom->getElementsByTagName('style') as $styleNode) {
            $css .= "\n" . $styleNode->textContent;
        }

        $inliner = new CssToInlineStyles();

        return $inliner->convert($html, $css);
    }

    protected function inlineLocalImages(\DOMNode $container, $assetsDir)
    {
        if (!$container instanceof \DOMElement) {
            return;
        }

        $images = $container->getElementsByTagName('img');
        for ($index = 0; $index < $images->length; $index++) {
            $image = $images->item($index);
            if (!$image instanceof \DOMElement) {
                continue;
            }

            $src = trim((string) $image->getAttribute('src'));
            if ($src === '' || stripos($src, 'data:') === 0) {
                continue;
            }

            $resolvedPath = $this->resolveImagePath($src, $assetsDir);
            if ($resolvedPath === null || !is_file($resolvedPath)) {
                continue;
            }

            $mimeType = function_exists('mime_content_type') ? mime_content_type($resolvedPath) : null;
            if (!$mimeType) {
                $mimeType = 'image/png';
            }

            $contents = file_get_contents($resolvedPath);
            if ($contents === false) {
                continue;
            }

            $image->setAttribute('src', 'data:' . $mimeType . ';base64,' . base64_encode($contents));
        }
    }

    protected function resolveImagePath($src, $assetsDir)
    {
        $src = html_entity_decode($src, ENT_QUOTES, 'UTF-8');

        if (stripos($src, 'file:///') === 0) {
            $path = preg_replace('#^file:///+#i', '', $src);

            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        if (preg_match('#^[a-z]+://#i', $src)) {
            return null;
        }

        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rawurldecode($src)), DIRECTORY_SEPARATOR);

        return $assetsDir . DIRECTORY_SEPARATOR . $relativePath;
    }

    protected function innerHtml(\DOMNode $node)
    {
        $html = '';
        foreach ($node->childNodes as $childNode) {
            $html .= $node->ownerDocument->saveHTML($childNode);
        }

        return $html;
    }

    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } elseif (file_exists($path)) {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    protected function quoteShellArgument($value)
    {
        return escapeshellarg($value);
    }

    protected function normalizeHtmlEncoding($html)
    {
        $encoding = $this->detectHtmlEncoding($html);
        if ($encoding === null) {
            $encoding = 'Windows-1252';
        }

        if (strcasecmp($encoding, 'UTF-8') !== 0) {
            if (function_exists('mb_convert_encoding')) {
                $converted = @mb_convert_encoding($html, 'UTF-8', $encoding);
                if ($converted !== false) {
                    return $converted;
                }
            }

            if (function_exists('iconv')) {
                $converted = @iconv($encoding, 'UTF-8//IGNORE', $html);
                if ($converted !== false) {
                    return $converted;
                }
            }
        }

        return $html;
    }

    protected function detectHtmlEncoding($html)
    {
        if (preg_match('/<meta[^>]+charset=["\']?\s*([A-Za-z0-9\-_]+)\s*["\']?/i', $html, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/<meta[^>]+content=["\'][^"\']*charset=([A-Za-z0-9\-_]+)/i', $html, $matches)) {
            return strtoupper($matches[1]);
        }

        if (function_exists('mb_detect_encoding')) {
            $detected = @mb_detect_encoding($html, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'], true);
            if ($detected !== false) {
                return strtoupper($detected);
            }
        }

        return null;
    }

    protected function pathToFileUri($path)
    {
        $normalizedPath = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $normalizedPath) === 1) {
            return 'file:///' . str_replace('%2F', '/', rawurlencode($normalizedPath));
        }

        return 'file://' . str_replace('%2F', '/', rawurlencode($normalizedPath));
    }
}
