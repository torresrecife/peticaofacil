<?php

namespace App\Services;

use App\Support\WordHtmlCleaner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class WordImportService
{
    /** A conversao pelo LibreOffice falhou e sobrou so o texto (sem formatacao). */
    protected $usedTextFallback = false;

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
            $this->usedTextFallback = false;
            $htmlPath = $this->convertWordToHtml($binary, $sourcePath, $jobDir);
            $html = file_get_contents($htmlPath);
            if ($html === false || trim($html) === '') {
                throw new RuntimeException('A conversao do arquivo Word nao gerou HTML legivel.');
            }

            $html = $this->normalizeHtmlEncoding($html);

            // O LibreOffice e a fonte mais fiel (negrito, alinhamento, recuos,
            // notas). O PhpWord entra quando o LibreOffice so devolveu o texto,
            // ou quando ele achatou uma tabela que o PhpWord preserva.
            if ($extension === 'docx') {
                $structuredHtml = $this->convertDocxWithPhpWord($sourcePath, $jobDir);
                if ($structuredHtml !== null && (
                    $this->usedTextFallback
                    || (stripos($html, '<table') === false && stripos($structuredHtml, '<table') !== false)
                )) {
                    $html = $structuredHtml;
                }
                $html = $this->appendDocxFootnotes($html, $sourcePath);
            }

            // Rotulos das listas como o Word mostra ("a)", "d.1)", "(i)").
            $listLabels = preg_match('/<(ol|ul)\b/i', $html)
                ? $this->extractListLabels($binary, $sourcePath, $jobDir)
                : [];

            return $this->prepareImportedHtml($html, dirname($htmlPath), $listLabels);
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
                Log::warning('Importacao Word: LibreOffice falhou, usando so o texto.', ['exit' => $exitCode, 'saida' => implode("\n", $output)]);
                $this->usedTextFallback = true;
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
            // O PhpWord grava arquivos temporarios; a pasta padrao do Windows
            // (C:\Windows\Temp) nao aceita escrita pelo Apache do Laragon.
            \PhpOffice\PhpWord\Settings::setTempDir($outputDir);
            $document = \PhpOffice\PhpWord\IOFactory::load($sourcePath);
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($document, 'HTML');
            $path = $outputDir . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '-phpword.html';
            $writer->save($path);
            $html = @file_get_contents($path);
            return $html !== false ? $this->normalizeHtmlEncoding($html) : null;
        } catch (\Throwable $exception) {
            Log::warning('Importacao Word: PhpWord falhou.', ['erro' => $exception->getMessage()]);
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
        // As notas ja vieram na conversao (LibreOffice/PhpWord): o WordHtmlCleaner arruma.
        if (preg_match('/sdfootnote|NoteRef|peticao-notas/i', (string) $html)) {
            $zip->close();
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
                $notes[] = '<p style="' . WordHtmlCleaner::NOTE_STYLE . '"><sup>' . (count($notes) + 1) . '</sup> ' . htmlspecialchars($this->normalizeExtractedText($text), ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
        $zip->close();
        if (!$notes) {
            return $html;
        }
        return (string) $html . '<div class="peticao-notas"><hr>' . implode('', $notes) . '</div>';
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

    /**
     * Converte o Word para texto pelo LibreOffice: nessa saida cada item de
     * lista vem com o numero escrito do jeito do Word ("a)", "d.1)", "(i)",
     * "•"). Devolve [['label' => ..., 'text' => ...], ...] na ordem do documento.
     */
    protected function extractListLabels($binary, $sourcePath, $outputDir)
    {
        $dir = $outputDir . DIRECTORY_SEPARATOR . 'texto';
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return [];
        }

        $command = $this->quoteShellArgument($binary)
            . ' --headless --nologo --nodefault --nolockcheck --norestore'
            . ' -env:UserInstallation=' . $this->quoteShellArgument($this->pathToFileUri($dir . DIRECTORY_SEPARATOR . 'perfil'))
            . ' --convert-to ' . $this->quoteShellArgument('txt:Text (encoded):UTF8')
            . ' --outdir ' . $this->quoteShellArgument($dir)
            . ' ' . $this->quoteShellArgument($sourcePath);
        $output = [];
        $exitCode = 0;
        @exec($command . ' 2>&1', $output, $exitCode);

        $path = $dir . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '.txt';
        $text = $exitCode === 0 && is_file($path) ? file_get_contents($path) : false;
        if ($text === false) {
            Log::warning('Importacao Word: conversao para texto (rotulos das listas) falhou.', ['exit' => $exitCode]);
            return [];
        }

        return self::parseListLabels($text);
    }

    /** Linhas de texto que comecam com rotulo de lista: "a) ...", "d.1) ...", "(i) ...", "3. ...", "• ...". */
    public static function parseListLabels($text)
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', (string) $text);
        $labels = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            // O recuo da linha (4 espacos por nivel) diz o nivel do item na lista do Word.
            if (preg_match('/^(\s*)(\(?[0-9]{1,3}(?:\.[0-9]{1,3})*[.)]|\(?[A-Za-z]{1,4}(?:\.[0-9]{1,3})*[.)]|[\x{2022}\x{25AA}\x{25CF}\x{25E6}\x{25A0}\x{27A2}\x{2013}\x{F0B7}\x{F0A7}o-])\s+(\S.*)$/u', $line, $match)) {
                $indent = strlen(str_replace("\t", '    ', $match[1]));
                $labels[] = ['label' => $match[2], 'text' => trim($match[3]), 'level' => max(1, (int) floor($indent / 4))];
            }
        }

        return $labels;
    }

    protected function prepareImportedHtml($html, $assetsDir, array $listLabels = [])
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

        return WordHtmlCleaner::clean(trim((string) $html), $listLabels);
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
                // Imagem que aponta para fora da conversao (ou nao existe):
                // remove o src para nao deixar um caminho local no HTML salvo.
                $image->removeAttribute('src');
                continue;
            }

            $mimeType = function_exists('mime_content_type') ? mime_content_type($resolvedPath) : null;
            if (!$mimeType) {
                $mimeType = 'image/png';
            }

            // Defesa adicional: so embute o que for realmente imagem, ainda que
            // o arquivo esteja dentro da pasta da conversao.
            if (stripos($mimeType, 'image/') !== 0) {
                $image->removeAttribute('src');
                continue;
            }

            $contents = file_get_contents($resolvedPath);
            if ($contents === false) {
                $image->removeAttribute('src');
                continue;
            }

            $image->setAttribute('src', 'data:' . $mimeType . ';base64,' . base64_encode($contents));
        }
    }

    /**
     * Resolve o src de uma imagem SOMENTE dentro da pasta temporaria daquela
     * conversao ($assetsDir). O LibreOffice e o PhpWord extraem as imagens do
     * .docx para essa pasta; qualquer outra coisa (file://, http://, caminho
     * absoluto, ou ".." escapando a pasta) e recusada, para um documento nao
     * conseguir fazer o servidor ler um arquivo seu arbitrario (ex.: o .env).
     */
    protected function resolveImagePath($src, $assetsDir)
    {
        $src = trim(html_entity_decode($src, ENT_QUOTES, 'UTF-8'));

        // Qualquer esquema (file://, http://, data: ja foi tratado antes) sai.
        if ($src === '' || preg_match('#^[a-z][a-z0-9+.\-]*:#i', $src)) {
            return null;
        }

        // Caminho absoluto (/etc/passwd, \\servidor\share, C:\...) sai.
        if (preg_match('#^([/\\\\]|[A-Za-z]:)#', $src)) {
            return null;
        }

        $base = realpath($assetsDir);
        if ($base === false) {
            return null;
        }

        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rawurldecode($src)), DIRECTORY_SEPARATOR);
        $candidate = realpath($base . DIRECTORY_SEPARATOR . $relativePath);
        if ($candidate === false) {
            return null;
        }

        // O arquivo resolvido tem de ficar dentro da pasta da conversao.
        $prefix = $base . DIRECTORY_SEPARATOR;
        if ($candidate !== $base && strncmp($candidate, $prefix, strlen($prefix)) !== 0) {
            return null;
        }

        return $candidate;
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

        // No Windows o escapeshellarg troca "%" por espaco: "C%3A" virava "C 3A"
        // e o LibreOffice fechava com erro. O caminho vai sem codificar.
        if (preg_match('/^[A-Za-z]:\//', $normalizedPath) === 1) {
            return 'file:///' . $normalizedPath;
        }

        return 'file://' . str_replace('%2F', '/', rawurlencode($normalizedPath));
    }
}
