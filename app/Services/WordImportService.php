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
            $file->move($jobDir, basename($sourcePath));
            $htmlPath = $this->convertWordToHtml($binary, $sourcePath, $jobDir);
            $html = file_get_contents($htmlPath);
            if ($html === false || trim($html) === '') {
                throw new RuntimeException('A conversao do arquivo Word nao gerou HTML legivel.');
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

        if ($exitCode !== 0) {
            throw new RuntimeException('Falha ao converter o arquivo Word: ' . trim(implode("\n", $output)));
        }

        $basename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $matches = glob($outputDir . DIRECTORY_SEPARATOR . $basename . '.htm*');
        if (!$matches) {
            throw new RuntimeException('A conversao do arquivo Word nao gerou um arquivo HTML.');
        }

        return $matches[0];
    }

    protected function buildLibreOfficeCommand($binary, $sourcePath, $outputDir, $profileDir, $homeDir, $cacheDir, $configDir)
    {
        $baseCommand = $this->quoteShellArgument($binary)
            . ' --headless --nologo --nodefault --nolockcheck --norestore'
            . ' -env:UserInstallation=' . $this->quoteShellArgument($this->pathToFileUri($profileDir))
            . ' --convert-to html --outdir '
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

    protected function pathToFileUri($path)
    {
        $normalizedPath = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $normalizedPath) === 1) {
            return 'file:///' . str_replace('%2F', '/', rawurlencode($normalizedPath));
        }

        return 'file://' . str_replace('%2F', '/', rawurlencode($normalizedPath));
    }
}
