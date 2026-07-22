<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'legacy_redirect_bootstrap.php';

$pecaId = '';
if (isset($_POST['id_pecas']) && trim((string) $_POST['id_pecas']) !== '') {
    $pecaId = trim((string) $_POST['id_pecas']);
} elseif (isset($_GET['id_pecas']) && trim((string) $_GET['id_pecas']) !== '') {
    $pecaId = trim((string) $_GET['id_pecas']);
}

if ($pecaId !== '') {
    legacy_redirect_to_modern('/pecas/' . rawurlencode($pecaId) . '/editar');
}

legacy_redirect_to_modern('/pecas');
