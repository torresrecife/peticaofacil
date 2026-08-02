<?php

return [
    'engine' => env('PDF_ENGINE', 'browser'),
    'fallback_engine' => env('PDF_FALLBACK_ENGINE', 'html2pdf'),
    'browser_binary' => env('PDF_BROWSER_BINARY', ''),
    'browser_timeout' => (int) env('PDF_BROWSER_TIMEOUT', 60),
    'browser_virtual_time_budget' => (int) env('PDF_BROWSER_VIRTUAL_TIME_BUDGET', 4000),
    'page' => [
        'top_mm' => (int) env('PDF_PRINT_MARGIN_TOP_MM', 19),
        'right_mm' => (int) env('PDF_PRINT_MARGIN_RIGHT_MM', 17),
        'bottom_mm' => (int) env('PDF_PRINT_MARGIN_BOTTOM_MM', 15),
        'left_mm' => (int) env('PDF_PRINT_MARGIN_LEFT_MM', 17),
    ],
];
