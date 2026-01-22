<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="U"){
	if (!class_exists(\App\Services\UsuarioService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$service->updatePassword($_POST['id_usu'], $_POST['senha_usu1'] ?? '');
	$service->updateAcesso($_POST['id_usu'], date("Y-m-d H:i:s"));
	echo 1;
}else{
	echo 2;
}

?>