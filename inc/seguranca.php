<?php

require_once __DIR__ . '/bootstrap.php';

use App\Infra\Database;

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $baseUrl = normalizeAppUrl(getenv('APP_URL'));
    $basePath = $baseUrl ? parse_url($baseUrl, PHP_URL_PATH) : getRequestBasePath();
    $scheme = $baseUrl ? parse_url($baseUrl, PHP_URL_SCHEME) : '';
    $cookiePath = $basePath ? rtrim($basePath, '/') . '/' : '/';
    $cookieParams = session_get_cookie_params();
    $secure = $cookieParams['secure'] ?? false;
    if ($scheme === 'http') {
        $secure = false;
    }
    session_set_cookie_params(
        $cookieParams['lifetime'] ?? 0,
        $cookiePath,
        $cookieParams['domain'] ?? '',
        $secure,
        $cookieParams['httponly'] ?? false
    );
}

session_start();

$logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'db.log';
    $payload = date('Y-m-d H:i:s') . " db=" . json_encode([
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'db' => getenv('DB_NAME'),
        'port' => getenv('DB_PORT'),
        'app_debug' => getenv('APP_DEBUG'),
        'entry' => __FILE__,
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($logFile, $payload, FILE_APPEND);
}

$_SG['conectaServidor'] = true;
$_SG['caseSensitive'] = false;
$_SG['validaSempre'] = true;
$_SG['servidor'] = getenv('DB_HOST') ?: '127.0.0.1';
$_SG['usuario'] = getenv('DB_USER') ?: 'root';
$_SG['senha'] = getenv('DB_PASS') ?: 'root';
$_SG['banco'] = getenv('DB_NAME') ?: 'peticaofacil';
$_SG['porta'] = getenv('DB_PORT') ?: 3306;
$_SG['paginaLogin'] = 'index.php';
$_SG['tabela'] = 'tp_usu_tb';

if ($_SG['conectaServidor'] == true) {
    if (class_exists(Database::class)) {
        $conexao1 = Database::mysql();
    } else {
        $conexao1 = mysqli_connect($_SG['servidor'], $_SG['usuario'], $_SG['senha'], $_SG['banco'], $_SG['porta']);
        if (!$conexao1) {
            if (is_dir($logDir) && is_writable($logDir)) {
                $err = mysqli_connect_error();
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " connect_error=" . $err . PHP_EOL, FILE_APPEND);
            }
            die('MySQL: Nao foi possivel conectar-se ao servidor [' . $_SG['servidor'] . '].');
        }
        $charset = getenv('DB_CHARSET') ?: 'latin1';
        @mysqli_set_charset($conexao1, $charset);
    }
}

function validaUsuario($usuario, $senha, $conex)
{
    global $_SG;
    if (class_exists(\App\Services\LoginService::class) && class_exists(\App\Repositories\UsuarioAuthRepository::class)) {
        $repo = new \App\Repositories\UsuarioAuthRepository($conex);
        $service = new \App\Services\LoginService($repo, $_SG['caseSensitive'], $_SG['validaSempre']);
        return $service->authenticate($usuario, $senha);
    }
    return false;
}

function protegePagina()
{
    global $_SG;
    if (!isset($_SESSION['usuarioID']) || !isset($_SESSION['usuarioNome'])) {
        expulsaVisitante2();
    } elseif (!isset($_SESSION['usuarioID']) || !isset($_SESSION['usuarioNome'])) {
        if ($_SG['validaSempre'] == true) {
            if (!validaUsuario($_SESSION['usuarioLogin'], $_SESSION['usuarioSenha'])) {
                expulsaVisitante();
            }
        }
    }
}

function expulsaVisitante()
{
    unset($_SESSION['usuarioID'], $_SESSION['usuarioNome'], $_SESSION['usuarioLogin'], $_SESSION['usuarioSenha']);
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        clearSessionCookie();
        session_destroy();
    }

    header('Location: ' . modernLoginUrl());
    exit;
}

function expulsaVisitante2()
{
    unset($_SESSION['usuarioID'], $_SESSION['usuarioNome'], $_SESSION['usuarioLogin'], $_SESSION['usuarioSenha']);
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        clearSessionCookie();
        session_destroy();
    }

    $target = modernLoginUrl();
    if (!headers_sent()) {
        header('Location: ' . $target);
        exit;
    }

    echo '<script>window.location=' . json_encode($target) . ';</script>';
    exit;
}

function normalizeAppUrl($baseUrl)
{
    if (!$baseUrl) {
        return '';
    }
    $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
    if (!$scheme || !in_array($scheme, ['http', 'https'], true)) {
        return '';
    }
    return rtrim($baseUrl, '/');
}

function modernLoginUrl()
{
    $directUrl = getenv('LARAVEL_APP_URL');
    if ($directUrl) {
        return rtrim($directUrl, '/') . '/login';
    }

    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'laravel6' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $values = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $values[trim($parts[0])] = trim($parts[1], "\"'");
        }

        if (!empty($values['LARAVEL_APP_URL'])) {
            return rtrim($values['LARAVEL_APP_URL'], '/') . '/login';
        }

        if (!empty($values['APP_URL'])) {
            return rtrim($values['APP_URL'], '/') . '/login';
        }
    }

    return 'http://127.0.0.1:8086/login';
}

function getRequestBasePath()
{
    if (empty($_SERVER['SCRIPT_NAME'])) {
        return '';
    }
    $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    while (in_array(basename($basePath), ['inc', 'public'], true)) {
        $basePath = str_replace('\\', '/', dirname($basePath));
    }
    if ($basePath === '/' || $basePath === '.') {
        return '';
    }
    return rtrim($basePath, '/');
}

function clearSessionCookie()
{
    if (!ini_get('session.use_cookies')) {
        return;
    }
    $params = session_get_cookie_params();
    $baseUrl = normalizeAppUrl(getenv('APP_URL'));
    $basePath = $baseUrl ? parse_url($baseUrl, PHP_URL_PATH) : getRequestBasePath();
    $paths = array_filter(array_unique([
        $params['path'] ?? '/',
        '/',
        $basePath ? rtrim($basePath, '/') . '/' : '/',
    ]));
    foreach ($paths as $path) {
        setcookie(session_name(), '', time() - 42000, $path, $params['domain'], $params['secure'], $params['httponly']);
    }
}
