<?php

$rootPath = dirname(__DIR__);
chdir($rootPath);

if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/public/') !== false) {
	$baseUrl = getenv('APP_URL') ?: '';
	$basePath = $baseUrl ? rtrim(parse_url($baseUrl, PHP_URL_PATH), '/') : '';
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? '';
	if ($host !== '') {
		$target = $scheme . '://' . $host . ($basePath ? $basePath : '') . '/';
	} else {
		$target = $baseUrl ? rtrim($baseUrl, '/') . '/' : '/';
	}
	header('Location: ' . $target);
	exit;
}

require_once $rootPath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

require_once $rootPath . DIRECTORY_SEPARATOR . 'index.php';
