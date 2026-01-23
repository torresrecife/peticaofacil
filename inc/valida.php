<?php

require_once __DIR__ . '/seguranca.php';

$showNewPass = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$usuario = (isset($_POST['username'])) ? $_POST['username'] : '';
	$senha2  = (isset($_POST['passwd'])) ? $_POST['passwd'] : '';
	$senha   = md5($senha2);
	if (validaUsuario($usuario, $senha, $conexao1) == true){
		if (class_exists(\App\Services\LoginService::class) && class_exists(\App\Repositories\UsuarioAuthRepository::class)) {
			$repo = new \App\Repositories\UsuarioAuthRepository($conexao1);
			$service = new \App\Services\LoginService($repo);
			$acesso = $service->getAcesso($_SESSION['usuarioID']);
			if(empty($acesso) || $acesso=="0000-00-00 00:00:00"){
				$showNewPass = true;
			}else{
				$service->updateAcesso($_SESSION['usuarioID'], date("Y-m-d H:i:s"));
				header("Location: ../index.php");
				exit;
			}
		} else {
			if (!class_exists(\App\Services\LoginService::class) || !class_exists(\App\Repositories\UsuarioAuthRepository::class)) {
				expulsaVisitante();
				exit;
			}
			$repo = new \App\Repositories\UsuarioAuthRepository($conexao1);
			$service = new \App\Services\LoginService($repo);
			$acesso = $service->getAcesso($_SESSION['usuarioID']);
			if(empty($acesso) || $acesso=="0000-00-00 00:00:00"){
				$showNewPass = true;
			}else{
				$service->updateAcesso($_SESSION['usuarioID'], date("Y-m-d H:i:s"));
				header("Location: ../index.php");
				exit;
			}
		}
	}else{
		expulsaVisitante();
		exit;
	}
}
require __DIR__ . "/views/valida.php";