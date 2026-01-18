<?php

$bootstrapPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

if (file_exists($bootstrapPath)) {
	require_once $bootstrapPath;
}
