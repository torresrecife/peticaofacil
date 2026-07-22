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

        $library = base_path('..\\html2pdf\\html2pdf.class.php');
        if (!file_exists($library)) {
            abort(500, 'Biblioteca de PDF nao encontrada.');
        }

        require_once $library;

        $content = '<style>p{margin:0;line-height:115%;font-size:11pt;} .titulos{text-align:center;border:solid 1px #000;font-weight:bold;}</style>'
            . '<page backtop="20mm" backbottom="15mm" backleft="20mm" backright="15mm">'
            . $this->normalizePdfImageSrc($conteudoHtml)
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

    protected function normalizePdfImageSrc($html)
    {
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $projectRoot = realpath(base_path('..'));

        return preg_replace_callback('/\bsrc=(["\'])(.*?)\1/i', function ($matches) use ($docRoot, $projectRoot) {
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
                if ($projectRoot !== false) {
                    $candidates[] = $projectRoot . '/' . ltrim($clean, '/');
                    $candidates[] = $projectRoot . '/public/' . ltrim($clean, '/');
                }
            }

            $appMarker = '/peticaofacil/';
            $markerPos = strpos(strtolower($clean), $appMarker);
            if ($markerPos !== false && $projectRoot !== false) {
                $suffix = ltrim(substr($clean, $markerPos + strlen($appMarker)), '/');
                $candidates[] = $projectRoot . '/' . $suffix;
                $candidates[] = $projectRoot . '/public/' . $suffix;
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
}
