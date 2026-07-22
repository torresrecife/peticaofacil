<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'legacy_redirect_bootstrap.php';

$tipoId = '';
if (isset($_POST['TIPOPET']) && trim((string) $_POST['TIPOPET']) !== '') {
    $tipoId = trim((string) $_POST['TIPOPET']);
} elseif (isset($_GET['TIPOPET']) && trim((string) $_GET['TIPOPET']) !== '') {
    $tipoId = trim((string) $_GET['TIPOPET']);
}

if ($tipoId !== '') {
    legacy_redirect_to_modern('/admin/modelos/' . rawurlencode($tipoId) . '/edit');
}

legacy_redirect_to_modern('/admin/modelos');
