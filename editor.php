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

$tipoId = '';
if (isset($_POST['tipo_id']) && trim((string) $_POST['tipo_id']) !== '') {
    $tipoId = trim((string) $_POST['tipo_id']);
} elseif (isset($_GET['tipo_id']) && trim((string) $_GET['tipo_id']) !== '') {
    $tipoId = trim((string) $_GET['tipo_id']);
}

if ($tipoId !== '') {
    legacy_redirect_to_modern('/peticoes/' . rawurlencode($tipoId));
}

legacy_redirect_to_modern('/pecas');
