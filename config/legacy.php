<?php

return [
    'app_url' => env('LEGACY_APP_URL', ''),
    'ckfinder_root' => env('LEGACY_CKFINDER_ROOT', ''),
    'ckfinder_base_url' => env('LEGACY_CKFINDER_BASE_URL', ''),
    'mirror_legacy_pecas' => env('LEGACY_PECAS_MIRROR', false),
    'mirror_legacy_modelos' => env('LEGACY_MODELOS_MIRROR', false),
    'mirror_legacy_listas' => env('LEGACY_LISTAS_MIRROR', false),
    'mirror_legacy_sql_configs' => env('LEGACY_SQL_CONFIGS_MIRROR', false),
    'mirror_legacy_users' => env('LEGACY_USERS_MIRROR', false),
    'auth_fallback_legacy_users' => env('LEGACY_USERS_AUTH_FALLBACK', false),
];
