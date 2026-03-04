<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\UsuarioService::class)) {
		json_err("Servico indisponivel.");
	}
	$id_usu = $_POST['id_usu'];
	if ($id_usu === "" || !is_numeric($id_usu)) {
		json_err("Usuario invalido.");
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$row = $service->getRow($id_usu);
	$items = array();
	if ($row) {
		foreach ($row as $w) {
			$items[] = function_exists('app_to_utf8') ? app_to_utf8($w) : $w;
		}
	}
	json_ok(array('items' => $items));
}
elseif($_POST['flag']=="I")
{
	if (!class_exists(\App\Services\UsuarioService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$ok = $service->insert($_POST);
	$ok ? json_ok() : json_err("Erro ao inserir.");
}
elseif($_POST['flag']=="U")
{
	if (!class_exists(\App\Services\UsuarioService::class)) {
		json_err("Servico indisponivel.");
	}
	if (!isset($_POST['id_usu']) || $_POST['id_usu'] === '' || !is_numeric($_POST['id_usu'])) {
		json_err("Usuario invalido.");
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$ok = $service->update($_POST['id_usu'], $_POST);
	$ok ? json_ok() : json_err("Erro ao atualizar.");
}
elseif($_POST['flag']=="D")
{
	if (!class_exists(\App\Services\UsuarioService::class)) {
		json_err("Servico indisponivel.");
	}
	if (!isset($_POST['id_usu']) || $_POST['id_usu'] === '' || !is_numeric($_POST['id_usu'])) {
		json_err("Usuario invalido.");
	}
	$service = new \App\Services\UsuarioService($conexao1);
	$ok = $service->delete($_POST['id_usu']);
	$ok ? json_ok() : json_err("Erro ao remover.");
}
?>
