<?php

function json_response($ok, $data = null, $message = '')
{
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=utf-8');
	}

	$payload = array(
		'ok' => (bool) $ok,
		'data' => $data,
		'message' => (string) $message,
	);

	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function json_ok($data = null, $message = '')
{
	json_response(true, $data, $message);
}

function json_err($message = 'Erro', $data = null)
{
	json_response(false, $data, $message);
}

