<?php
$publicRoot = dirname(__DIR__);
$appRoot = dirname($publicRoot);
chdir($appRoot);
require $appRoot . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'ajax_input.php';

