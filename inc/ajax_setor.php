<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\SetorService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SetorService($conexao1);
	$row = $service->getRow($_POST['id_setor']);
	$items = array();
	if ($row) {
		foreach ($row as $value) {
			$items[] = $value;
		}
	}
	json_ok(array('items' => $items));
}elseif($_POST['flag']=="I"){
	if (!class_exists(\App\Services\SetorService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SetorService($conexao1);
	$ok = $service->insert($_POST);
	$ok ? json_ok() : json_err("Erro ao inserir.");
}elseif($_POST['flag']=="U"){
	if (!class_exists(\App\Services\SetorService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SetorService($conexao1);
	$ok = $service->update($_POST['id_setor'], $_POST);
	$ok ? json_ok() : json_err("Erro ao atualizar.");
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\SetorService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SetorService($conexao1);
	$ok = $service->delete($_POST['id_setor']);
	$ok ? json_ok() : json_err("Erro ao remover.");
}
?>