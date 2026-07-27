<?php

function legacy_gone_json($redirect, $message = null)
{
    http_response_code(410);
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'ok' => false,
        'message' => $message ?: 'Endpoint legado aposentado.',
        'redirect' => $redirect,
    ]);

    exit;
}
