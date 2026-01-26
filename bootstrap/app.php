<?php

$rootPath = dirname(__DIR__);
$autoloadPath = $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists($autoloadPath)) {
	require_once $autoloadPath;
} else {
	spl_autoload_register(function ($class) use ($rootPath) {
		$prefix = 'App\\';
		$prefixLen = strlen($prefix);
		if (strncmp($prefix, $class, $prefixLen) !== 0) {
			return;
		}
		$relativeClass = substr($class, $prefixLen);
		$file = $rootPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
			. str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	});
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

if (!function_exists('asset_version')) {
	function asset_version($relativePath) {
		$rootPath = dirname(__DIR__);
		$relativePath = ltrim($relativePath, '/');
		$fullPath = $rootPath . DIRECTORY_SEPARATOR
			. str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relativePath);
		if (file_exists($fullPath)) {
			return (string) filemtime($fullPath);
		}
		return '1';
	}
}

if (!function_exists('app_charset')) {
	function app_charset() {
		return 'UTF-8';
	}
}

if (!function_exists('app_to_utf8')) {
	function app_to_utf8($value) {
		if (!is_string($value)) {
			return $value;
		}
		$dbCharset = strtolower(getenv('DB_CHARSET') ?: '');
		if ($dbCharset === 'latin1') {
			return utf8_encode($value);
		}
		return $value;
	}
}
