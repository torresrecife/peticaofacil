<?php

require_once __DIR__ . '/bootstrap.php';
use App\Infra\Database;

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
	$baseUrl = normalizeAppUrl(getenv('APP_URL'));
	$basePath = $baseUrl ? parse_url($baseUrl, PHP_URL_PATH) : '';
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
	$payload = date('Y-m-d H:i:s') . " db=" . json_encode(array(
		'host' => getenv('DB_HOST'),
		'user' => getenv('DB_USER'),
		'db' => getenv('DB_NAME'),
		'port' => getenv('DB_PORT'),
		'app_debug' => getenv('APP_DEBUG'),
		'entry' => __FILE__,
		'uri' => $_SERVER['REQUEST_URI'] ?? ''
	), JSON_UNESCAPED_UNICODE) . PHP_EOL;
	@file_put_contents($logFile, $payload, FILE_APPEND);
}

$_SG['conectaServidor'] = true;
$_SG['caseSensitive'] 	= false;
$_SG['validaSempre'] 	= true;
$_SG['servidor'] 		= getenv('DB_HOST') ?: '127.0.0.1';
$_SG['usuario'] 		= getenv('DB_USER') ?: 'root';
$_SG['senha'] 			= getenv('DB_PASS') ?: 'root';
$_SG['banco'] 			= getenv('DB_NAME') ?: 'peticaofacil';
$_SG['porta'] 			= getenv('DB_PORT') ?: 3306;
$_SG['paginaLogin'] 	= 'index.php';
$_SG['tabela'] 			= 'tp_usu_tb';

if ($_SG['conectaServidor'] == true){
	if (class_exists(Database::class)) {
		$conexao1 = Database::mysql();
	} else {
		$conexao1 = mysqli_connect($_SG['servidor'],$_SG['usuario'],$_SG['senha'],$_SG['banco'], $_SG['porta']);
		if (!$conexao1) {
			if (is_dir($logDir) && is_writable($logDir)) {
				$err = mysqli_connect_error();
				@file_put_contents($logFile, date('Y-m-d H:i:s') . " connect_error=" . $err . PHP_EOL, FILE_APPEND);
			}
			die("MySQL: Não foi possível conectar-se ao servidor [".$_SG['servidor']."].");
		}
		$charset = getenv('DB_CHARSET') ?: 'latin1';
		@mysqli_set_charset($conexao1, $charset);
	}
}
function validaUsuario($usuario, $senha, $conex){
	global $_SG;
	if (class_exists(\App\Services\LoginService::class) && class_exists(\App\Repositories\UsuarioAuthRepository::class)) {
		$repo = new \App\Repositories\UsuarioAuthRepository($conex);
		$service = new \App\Services\LoginService($repo, $_SG['caseSensitive'], $_SG['validaSempre']);
		return $service->authenticate($usuario, $senha);
	}
	return false;
}
function protegePagina(){
	global $_SG;
	if (!isset($_SESSION['usuarioID']) OR !isset($_SESSION['usuarioNome'])){
		expulsaVisitante2();
	}else if (!isset($_SESSION['usuarioID']) OR !isset($_SESSION['usuarioNome'])) {
		if ($_SG['validaSempre'] == true) {
			if (!validaUsuario($_SESSION['usuarioLogin'], $_SESSION['usuarioSenha'])) {
				expulsaVisitante();
			}
		}
	}
}
function expulsaVisitante(){
	global $_SG;
	unset($_SESSION['usuarioID'], $_SESSION['usuarioNome'], $_SESSION['usuarioLogin'], $_SESSION['usuarioSenha']);
	$_SESSION = array();
	if (session_status() === PHP_SESSION_ACTIVE) {
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		session_destroy();
	}
	$baseUrl = normalizeAppUrl(getenv('APP_URL'));
	if (!$baseUrl) {
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
	}
	$message = "Usuário ou senha Inválidos!";
	$target = rtrim($baseUrl, '/') . '/login.php';
	require __DIR__ . "/views/login_redirect.php";
	exit;
}

function expulsaVisitante2(){
	global $_SG;
	unset($_SESSION['usuarioID'], $_SESSION['usuarioNome'], $_SESSION['usuarioLogin'], $_SESSION['usuarioSenha']);
	$_SESSION = array();
	if (session_status() === PHP_SESSION_ACTIVE) {
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		session_destroy();
	}
	$baseUrl = normalizeAppUrl(getenv('APP_URL'));
	if (!$baseUrl) {
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
	}
	$message = "";
	$target = rtrim($baseUrl, '/') . '/login.php';
	require __DIR__ . "/views/login_redirect.php";
	exit;
}

function normalizeAppUrl($baseUrl) {
	if (!$baseUrl) {
		return '';
	}
	$scheme = parse_url($baseUrl, PHP_URL_SCHEME);
	if (!$scheme || !in_array($scheme, array('http', 'https'), true)) {
		return '';
	}
	return rtrim($baseUrl, '/');
}

?>