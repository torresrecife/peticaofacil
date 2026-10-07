<?php

return [
    'enabled' => (bool) env('LANGUAGETOOL_ENABLED', false),
    'base_url' => rtrim(env('LANGUAGETOOL_BASE_URL', 'http://127.0.0.1:8081'), '/'),
    'language' => env('LANGUAGETOOL_LANGUAGE', 'pt-BR'),
    'timeout' => (int) env('LANGUAGETOOL_TIMEOUT', 20),
    'connect_timeout' => (int) env('LANGUAGETOOL_CONNECT_TIMEOUT', 5),
    'username' => env('LANGUAGETOOL_USERNAME'),
    'api_key' => env('LANGUAGETOOL_API_KEY'),
];
