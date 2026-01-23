<?php

require_once __DIR__ . "/seguranca.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\UsuarioService::class)) {
		echo 0;
		exit;
	}
	$id_usu = $_POST['id_usu'];
	if ($id_usu === "" || !is_numeric($id_usu)) {
		exit;
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$row = $service->getRow($id_usu);
	if ($row) {
		foreach ($row as $w) {
			echo $w . "-|-";
		}
	}
}
elseif($_POST['flag']=="I")
{
	if (!class_exists(\App\Services\UsuarioService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$ok = $service->insert($_POST);
	echo $ok ? 1 : 0;
}
elseif($_POST['flag']=="U")
{
	if (!class_exists(\App\Services\UsuarioService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$ok = $service->update($_POST['id_usu'], $_POST);
	echo $ok ? 1 : 0;
}
elseif($_POST['flag']=="D")
{
	if (!class_exists(\App\Services\UsuarioService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$ok = $service->delete($_POST['id_usu']);
	echo $ok ? 1 : 0;
}
?>