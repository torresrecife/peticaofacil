<?php

return [
    'app_url' => env('LEGACY_APP_URL', ''),
    'ckfinder_root' => env('LEGACY_CKFINDER_ROOT', ''),
    'ckfinder_base_url' => env('LEGACY_CKFINDER_BASE_URL', ''),
    'mirror_legacy_pecas' => env('LEGACY_PECAS_MIRROR', true),
    'mirror_legacy_modelos' => env('LEGACY_MODELOS_MIRROR', true),
    'mirror_legacy_listas' => env('LEGACY_LISTAS_MIRROR', false),
    'mirror_legacy_sql_configs' => env('LEGACY_SQL_CONFIGS_MIRROR', false),
    'mirror_legacy_users' => env('LEGACY_USERS_MIRROR', false),
    'auth_fallback_legacy_users' => env('LEGACY_USERS_AUTH_FALLBACK', false),
    'compat_public_model_routes' => env('LEGACY_PUBLIC_MODEL_ROUTE_COMPAT', true),
    'compat_public_piece_editor_route' => env('LEGACY_PUBLIC_PIECE_EDITOR_COMPAT', true),
    'compat_admin_sql_routes' => env('LEGACY_ADMIN_SQL_ROUTE_COMPAT', true),
    'compat_admin_model_routes' => env('LEGACY_ADMIN_MODEL_ROUTE_COMPAT', true),
];
