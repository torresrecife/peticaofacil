<?php

$publicRoot = __DIR__;
$appRoot = dirname($publicRoot);

chdir($appRoot);

require $appRoot . DIRECTORY_SEPARATOR . 'login.php';
