<?php

return [
    'enabled' => (bool) env('OPENAI_ENABLED', true),
    'api_key' => env('OPENAI_API_KEY'),
    'base_url' => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
    'model' => env('OPENAI_MODEL', 'gpt-5.6'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 60),
];
