<?php

function legacy_modern_base_url()
{
    $url = getenv('LARAVEL_APP_URL');
    if ($url) {
        return rtrim($url, '/');
    }

    $envPath = __DIR__ . DIRECTORY_SEPARATOR . 'laravel6' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, 'LARAVEL_APP_URL=') === 0) {
                return rtrim(trim(substr($line, strlen('LARAVEL_APP_URL=')), "\"'"), '/');
            }

            if (strpos($line, 'APP_URL=') === 0) {
                return rtrim(trim(substr($line, strlen('APP_URL=')), "\"'"), '/');
            }
        }
    }

    return 'http://bvaa.test/peticaofacil';
}

header('Location: ' . legacy_modern_base_url() . '/legacy/logout');
exit;
