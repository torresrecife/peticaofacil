<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="U"){
	if (!class_exists(\App\Services\UsuarioService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$service->updatePassword($_POST['id_usu'], $_POST['senha_usu1'] ?? '');
	$service->updateAcesso($_POST['id_usu'], date("Y-m-d H:i:s"));
	json_ok();
}else{
	json_err("Operacao invalida.");
}

?>