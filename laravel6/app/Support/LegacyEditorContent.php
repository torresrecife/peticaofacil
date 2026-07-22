<?php

namespace App\Support;

class LegacyEditorContent
{
    public static function normalize($html)
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }

        $legacyBaseUrl = rtrim((string) config('legacy.app_url'), '/');
        if ($legacyBaseUrl === '') {
            return $html;
        }

        return preg_replace_callback('/\bsrc=(["\'])(.*?)\1/i', function ($matches) use ($legacyBaseUrl) {
            $quote = $matches[1];
            $src = trim($matches[2]);

            if ($src === '' || strpos($src, 'data:') === 0 || preg_match('#^(https?:)?//#i', $src) || strpos($src, 'file://') === 0) {
                return 'src=' . $quote . $src . $quote;
            }

            if (strpos($src, '/peticaofacil/') === 0) {
                return 'src=' . $quote . $legacyBaseUrl . substr($src, strlen('/peticaofacil')) . $quote;
            }

            if (strpos($src, '/img/') === 0 || strpos($src, 'img/') === 0) {
                return 'src=' . $quote . $legacyBaseUrl . '/' . ltrim($src, '/') . $quote;
            }

            $resolved = static::resolveLegacyAssetUrl($src, $legacyBaseUrl);
            if ($resolved !== null) {
                return 'src=' . $quote . $resolved . $quote;
            }

            return 'src=' . $quote . $src . $quote;
        }, $html);
    }

    public static function denormalize($html)
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }

        $legacyBaseUrl = rtrim((string) config('legacy.app_url'), '/');
        if ($legacyBaseUrl === '') {
            return $html;
        }

        $legacyPath = rtrim((string) parse_url($legacyBaseUrl, PHP_URL_PATH), '/');

        return preg_replace_callback('/\bsrc=(["\'])(.*?)\1/i', function ($matches) use ($legacyBaseUrl, $legacyPath) {
            $quote = $matches[1];
            $src = trim($matches[2]);

            if ($src === '' || stripos($src, $legacyBaseUrl) !== 0) {
                return 'src=' . $quote . $src . $quote;
            }

            $suffix = substr($src, strlen($legacyBaseUrl));
            $relative = ($legacyPath !== '' ? $legacyPath : '') . '/' . ltrim($suffix, '/');
            $relative = preg_replace('#/+#', '/', $relative);

            return 'src=' . $quote . $relative . $quote;
        }, $html);
    }

    protected static function resolveLegacyAssetUrl($src, $legacyBaseUrl)
    {
        $basename = basename(parse_url($src, PHP_URL_PATH) ?: $src);
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return null;
        }

        $projectRoot = realpath(base_path('..'));
        if ($projectRoot === false) {
            return null;
        }

        $candidatePatterns = [
            $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'userfiles' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $basename,
            $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'ckfinder' . DIRECTORY_SEPARATOR . 'userfiles' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $basename,
            $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'ckfinder' . DIRECTORY_SEPARATOR . 'userfiles' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $basename,
        ];

        foreach ($candidatePatterns as $pattern) {
            $matches = glob($pattern, GLOB_NOSORT);
            if (empty($matches)) {
                continue;
            }

            $path = str_replace('\\', '/', $matches[0]);
            $publicRoot = str_replace('\\', '/', $projectRoot . DIRECTORY_SEPARATOR . 'public') . '/';
            if (strpos($path, $publicRoot) !== 0) {
                continue;
            }

            return $legacyBaseUrl . '/' . ltrim(substr($path, strlen($publicRoot)), '/');
        }

        return null;
    }
}
