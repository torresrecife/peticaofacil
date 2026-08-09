<?php

return [
    'engine' => env('PDF_ENGINE', 'browser'),
    'fallback_engine' => env('PDF_FALLBACK_ENGINE', 'html2pdf'),
    'browser_binary' => env('PDF_BROWSER_BINARY', ''),
    'browser_timeout' => (int) env('PDF_BROWSER_TIMEOUT', 60),
    'browser_virtual_time_budget' => (int) env('PDF_BROWSER_VIRTUAL_TIME_BUDGET', 4000),
    'playwright' => [
        'node_binary' => env('PDF_PLAYWRIGHT_NODE_BINARY', 'node'),
        'browser_binary' => env('PDF_PLAYWRIGHT_BROWSER_BINARY', ''),
        'script' => env('PDF_PLAYWRIGHT_SCRIPT', base_path('scripts/pdf-renderer/render-peticao.js')),
        'timeout' => (int) env('PDF_PLAYWRIGHT_TIMEOUT', 90),
        'margin' => [
            'top' => env('PDF_PLAYWRIGHT_MARGIN_TOP', '16.9mm'),
            'right' => env('PDF_PLAYWRIGHT_MARGIN_RIGHT', '16.9mm'),
            'bottom' => env('PDF_PLAYWRIGHT_MARGIN_BOTTOM', '16.9mm'),
            'left' => env('PDF_PLAYWRIGHT_MARGIN_LEFT', '16.9mm'),
        ],
    ],
    'page' => [
        'top_mm' => (int) env('PDF_PRINT_MARGIN_TOP_MM', 19),
        'right_mm' => (int) env('PDF_PRINT_MARGIN_RIGHT_MM', 17),
        'bottom_mm' => (int) env('PDF_PRINT_MARGIN_BOTTOM_MM', 15),
        'left_mm' => (int) env('PDF_PRINT_MARGIN_LEFT_MM', 17),
    ],
];
