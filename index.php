<?php

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
    if (!$url) {
        $url = 'http://127.0.0.1:8086';
    }

    return rtrim($url, '/');
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
