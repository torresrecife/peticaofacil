<?php

$rootPath = dirname(__DIR__);
$autoloadPath = $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists($autoloadPath)) {
	require_once $autoloadPath;
}

if (class_exists(\Dotenv\Dotenv::class)) {
	$envPath = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'env.local';
	if (file_exists($envPath)) {
		$dotenv = \Dotenv\Dotenv::createImmutable($rootPath, 'config/env.local');
		$dotenv->safeLoad();
	}
}

if (getenv('APP_DEBUG') === false) {
	$envPath = $rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'env.local';
	if (file_exists($envPath)) {
		$envValues = @parse_ini_file($envPath, false, INI_SCANNER_RAW);
		if (is_array($envValues)) {
			foreach ($envValues as $key => $value) {
				if (getenv($key) === false) {
					putenv($key . '=' . $value);
					$_ENV[$key] = $value;
					$_SERVER[$key] = $value;
				}
			}
		}
	}
}
