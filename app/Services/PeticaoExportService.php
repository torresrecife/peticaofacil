<?php

namespace App\Services;

use Illuminate\Http\Request;

class PeticaoExportService
{
    public function exportWord($nomeArquivo, $conteudoHtml)
    {
        $filename = $this->sanitizeFileName($nomeArquivo) . '.doc';

        return response($conteudoHtml, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request, $nomeArquivo, $conteudoHtml)
    {
        $this->prepareLegacyPdfEnvironment($request);

        $library = $this->resolvePdfLibraryPath();
        if (!file_exists($library)) {
            abort(500, 'Biblioteca de PDF nao encontrada.');
        }

        require_once $library;

        $content = '<style>' . $this->buildPdfStyles() . '</style>'
            . '<page backtop="19mm" backbottom="15mm" backleft="17mm" backright="17mm">'
            . $this->normalizePdfMarkup($this->normalizePdfImageSrc($conteudoHtml))
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
        ]);
    }

    public function sanitizeFileName($value)
    {
        $value = preg_replace('/[^\pL\pN_\-]+/u', '_', $value);
        $value = trim($value, '_');

        return $value !== '' ? $value : 'peticao';
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
            base_path('html2pdf\\html2pdf.class.php'),
            base_path('..\\html2pdf\\html2pdf.class.php'),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    protected function normalizePdfImageSrc($html)
    {
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $projectRoot = realpath(base_path());
        $publicRoot = realpath(public_path());
        $appPath = trim((string) parse_url((string) config('app.url', ''), PHP_URL_PATH), '/');

        return preg_replace_callback('/\bsrc=(["\'])(.*?)\1/i', function ($matches) use ($docRoot, $projectRoot, $publicRoot, $appPath) {
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

                if (preg_match('/^[A-Za-z]:\//', $real)) {
                    return 'src=' . $quote . 'file:///' . $real . $quote;
                }

                return 'src=' . $quote . 'file://' . $real . $quote;
            }

            return 'src=' . $quote . $src . $quote;
        }, $html);
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
            'img { max-width: 100%; }',
        ]);
    }

    protected function normalizePdfMarkup($html)
    {
        $html = preg_replace('/(?:&nbsp;[\s]*){2,}/i', ' ', $html);

        $html = preg_replace_callback('/style=(["\'])(.*?)\1/i', function ($matches) {
            $quote = $matches[1];
            $style = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

            $style = preg_replace_callback('/font-size\s*:\s*(\d+(?:\.\d+)?)px/i', function ($fontMatches) {
                return 'font-size:' . $fontMatches[1] . 'pt';
            }, $style);

            $style = preg_replace('/line-height\s*:\s*115%/i', 'line-height:160%', $style);
            $style = preg_replace('/line-height\s*:\s*1\.15\b/i', 'line-height:1.6', $style);
            $style = preg_replace('/white-space\s*:\s*nowrap\s*;?/i', 'white-space:normal;', $style);

            if (stripos($style, 'word-wrap:') === false) {
                $style .= ';word-wrap:break-word;';
            }

            return 'style=' . $quote . $style . $quote;
        }, $html);

        return preg_replace('/<p([^>]*)>(&nbsp;|\s|<br\s*\/?>)*<\/p>/i', '<p$1>&nbsp;</p>', $html);
    }
}
