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
}
