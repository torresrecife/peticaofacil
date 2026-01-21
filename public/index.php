<?php

$rootPath = dirname(__DIR__);
chdir($rootPath);

if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/public/') !== false) {
	$baseUrl = getenv('APP_URL') ?: '/bvaa/peticaofacil';
	header('Location: ' . rtrim($baseUrl, '/') . '/');
	exit;
}

require_once $rootPath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

require_once $rootPath . DIRECTORY_SEPARATOR . 'index.php';
