<?php

require_once __DIR__ . '/bootstrap.php';
use App\Infra\Database;

session_start();

$_SG['conectaServidor'] = true;
$_SG['caseSensitive'] 	= false;
$_SG['validaSempre'] 	= true;
$_SG['servidor'] 		= getenv('DB_HOST') ?: '127.0.0.1';
$_SG['usuario'] 		= getenv('DB_USER') ?: 'root';
$_SG['senha'] 			= getenv('DB_PASS') ?: 'root';
$_SG['banco'] 			= getenv('DB_NAME') ?: 'peticaofacil';
$_SG['paginaLogin'] 	= 'index.php';
$_SG['tabela'] 			= 'tp_usu_tb';

if ($_SG['conectaServidor'] == true){
	if (class_exists(Database::class)) {
		$conexao1 = Database::mysql();
	} else {
		$conexao1 = mysqli_connect($_SG['servidor'],$_SG['usuario'],$_SG['senha'],$_SG['banco'])or die("MySQL: Não foi possível conectar-se ao servidor [".$_SG['servidor']."].");
	}
}
function validaUsuario($usuario, $senha, $conex){
	global $_SG;
	$cS 		= ($_SG['caseSensitive']) ? 'BINARY' : '';
	$nusuario 	= addslashes($usuario);
	$nsenha   	= addslashes($senha);
	$sql 	  	= "SELECT * FROM `".$_SG['tabela']."` WHERE " . $cS . " `login_usu` = '" . $nusuario . "' AND ".$cS." `senha_usu` = '".$nsenha."' LIMIT 1";
	$query 	    = mysqli_query($conex,$sql);
	if($query === FALSE) {
		die(mysqli_error());
	}
	$resultado 	= mysqli_fetch_assoc($query);
	
	if (empty($resultado)){
		return false;
	}else{
		$_SESSION['usuarioID'] 	  	= $resultado['id_usu']; 
		$_SESSION['usuarioNome']  	= $resultado['nome_usu']; 
		$_SESSION['usuarioNivel'] 	= $resultado['nivel_usu'];
		$_SESSION['usuarioST'] 	  	= $resultado['status_usu'];
		$_SESSION['usuarioSetor'] 	= $resultado['id_setor'];
		$_SESSION['usuarioCliente']	= $resultado['id_cliente'];

		if ($_SG['validaSempre'] == true){
			$_SESSION['usuarioLogin'] = $usuario;
			$_SESSION['usuarioSenha'] = $senha;
		}
		return true;
	}
	mysqli_close($conexao10);
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
	echo ('<script language="javascript">alert("Usuário ou senha Inválidos!")</script>');
	$baseUrl = getenv('APP_URL');
	if (!$baseUrl) {
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
	}
	exit ('<SCRIPT LANGUAGE="JavaScript">window.location="'.rtrim($baseUrl, '/').'/login.php";</script>');
}

function expulsaVisitante2(){
	global $_SG;
	unset($_SESSION['usuarioID'], $_SESSION['usuarioNome'], $_SESSION['usuarioLogin'], $_SESSION['usuarioSenha']);
	$baseUrl = getenv('APP_URL');
	if (!$baseUrl) {
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
	}
	exit ('<SCRIPT LANGUAGE="JavaScript">window.location="'.rtrim($baseUrl, '/').'/login.php";</script>');
}

?>