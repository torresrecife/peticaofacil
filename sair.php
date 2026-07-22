<?php

function legacy_modern_base_url()
{
    $url = getenv('LARAVEL_APP_URL');
    if (!$url) {
        $url = 'http://127.0.0.1:8086';
    }

    return rtrim($url, '/');
}

header('Location: ' . legacy_modern_base_url() . '/legacy/logout');
exit;
