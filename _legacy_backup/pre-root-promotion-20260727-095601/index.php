<?php

function legacy_request_path()
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);

    return is_string($path) && $path !== '' ? $path : '/';
}

function legacy_requested_entrypoint()
{
    return basename(legacy_request_path());
}

error_reporting(1);
ini_set('display_errors', 1);
header('Cache-Control: no cache');
session_cache_limiter('private_no_expire');
session_cache_limiter('public');

include 'inc/seguranca.php';
protegePagina();

function legacy_modern_base_url()
{
    $url = getenv('LARAVEL_APP_URL');
    if ($url) {
        return rtrim($url, '/');
    }

    $envPath = __DIR__ . DIRECTORY_SEPARATOR . 'laravel6' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, 'LARAVEL_APP_URL=') === 0) {
                return rtrim(trim(substr($line, strlen('LARAVEL_APP_URL=')), "\"'"), '/');
            }

            if (strpos($line, 'APP_URL=') === 0) {
                return rtrim(trim(substr($line, strlen('APP_URL=')), "\"'"), '/');
            }
        }
    }

    return 'http://bvaa.test/peticaofacil';
}

function legacy_bridge_key()
{
    $envPath = __DIR__ . DIRECTORY_SEPARATOR . 'laravel6' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, 'LEGACY_BRIDGE_KEY=') === 0) {
                return trim(substr($line, strlen('LEGACY_BRIDGE_KEY=')), "\"'");
            }
            if (strpos($line, 'APP_KEY=') === 0) {
                return trim(substr($line, strlen('APP_KEY=')), "\"'");
            }
        }
    }

    return 'peticaofacil-legacy-bridge';
}

function legacy_bridge_url($path)
{
    $uid = isset($_SESSION['usuarioID']) ? (int) $_SESSION['usuarioID'] : 0;
    $path = '/' . ltrim($path, '/');
    $ts = time();
    $sig = hash_hmac('sha256', $uid . '|' . $ts . '|' . $path, legacy_bridge_key());

    return legacy_modern_base_url() . '/legacy/bridge?uid=' . $uid . '&ts=' . $ts . '&path=' . rawurlencode($path) . '&sig=' . $sig;
}

function legacy_redirect_to_modern($path)
{
    header('Location: ' . legacy_bridge_url($path));
    exit;
}

function legacy_handle_entrypoint($entrypoint)
{
    switch ($entrypoint) {
        case 'admin.php':
            legacy_redirect_to_modern('/admin/modelos');
            break;
        case 'cliente.php':
            legacy_redirect_to_modern('/admin/clientes');
            break;
        case 'dados.php':
            $tipoId = '';
            if (isset($_POST['TIPOPET']) && trim((string) $_POST['TIPOPET']) !== '') {
                $tipoId = trim((string) $_POST['TIPOPET']);
            } elseif (isset($_GET['TIPOPET']) && trim((string) $_GET['TIPOPET']) !== '') {
                $tipoId = trim((string) $_GET['TIPOPET']);
            }

            if ($tipoId !== '') {
                legacy_redirect_to_modern('/peticoes/' . rawurlencode($tipoId));
            }

            legacy_redirect_to_modern('/peticoes');
            break;
        case 'editor.php':
        case 'assinatura.php':
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
            break;
        case 'list.php':
            legacy_redirect_to_modern('/admin/listas');
            break;
        case 'parag.php':
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
            break;
        case 'pecas.php':
            legacy_redirect_to_modern('/pecas');
            break;
        case 'setor.php':
            legacy_redirect_to_modern('/admin/setores');
            break;
        case 'sql.php':
            legacy_redirect_to_modern('/admin/servidores');
            break;
        case 'usu.php':
            legacy_redirect_to_modern('/admin/usuarios');
            break;
    }
}

$legacyEntrypoint = legacy_requested_entrypoint();

if (in_array($legacyEntrypoint, [
    'admin.php',
    'assinatura.php',
    'cliente.php',
    'dados.php',
    'editor.php',
    'list.php',
    'parag.php',
    'pecas.php',
    'setor.php',
    'sql.php',
    'usu.php',
], true)) {
    legacy_handle_entrypoint($legacyEntrypoint);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['hid_enviar'])) {
    require __DIR__ . '/laravel6/public/index.php';
    return;
}

$hidEnviar = isset($_POST['hid_enviar']) ? trim((string) $_POST['hid_enviar']) : '';
$tipoPet = isset($_POST['TIPOPET']) && $_POST['TIPOPET'] !== ''
    ? trim((string) $_POST['TIPOPET'])
    : (isset($_GET['TIPOPET']) ? trim((string) $_GET['TIPOPET']) : '');

if ($hidEnviar === '') {
    legacy_redirect_to_modern('/painel');
}

$redirectMap = [
    '1' => $tipoPet !== '' ? '/peticoes/' . rawurlencode($tipoPet) : '/peticoes',
    '2' => $tipoPet !== '' ? '/peticoes/' . rawurlencode($tipoPet) : '/peticoes',
    '3' => $tipoPet !== '' ? '/peticoes/' . rawurlencode($tipoPet) : '/peticoes',
    '4' => $tipoPet !== '' ? '/peticoes/' . rawurlencode($tipoPet) : '/peticoes',
    '5' => '/admin/modelos',
    '6' => $tipoPet !== '' ? '/admin/modelos/' . rawurlencode($tipoPet) . '/edit' : '/admin/modelos',
    '7' => $tipoPet !== '' ? '/admin/modelos/' . rawurlencode($tipoPet) . '/edit' : '/admin/modelos',
    '8' => '/admin/usuarios',
    '9' => '/admin/setores',
    '10' => '/pecas',
    '11' => '/admin/servidores',
    '12' => '/admin/modelos',
    '13' => '/admin/clientes',
];

$target = isset($redirectMap[$hidEnviar]) ? $redirectMap[$hidEnviar] : '/painel';

legacy_redirect_to_modern($target);
