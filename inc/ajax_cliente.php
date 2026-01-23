<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ClienteService($conexao1);
	$row = $service->getRow($_POST['cliente_id']);
	$items = array();
	if ($row) {
		foreach ($row as $value) {
			$items[] = $value;
		}
	}
	json_ok(array('items' => $items));
}elseif($_POST['flag']=="I"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ClienteService($conexao1);
	$ok = $service->insert($_POST);
	if ($ok) {
		json_ok();
	} else {
		$message = (getenv('APP_DEBUG') === 'true' && $service->getLastError()) ? $service->getLastError() : "Erro ao inserir.";
		json_err($message);
	}
}elseif($_POST['flag']=="U"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ClienteService($conexao1);
	$ok = $service->update($_POST['cliente_id'], $_POST);
	if ($ok) {
		json_ok();
	} else {
		$message = (getenv('APP_DEBUG') === 'true' && $service->getLastError()) ? $service->getLastError() : "Erro ao atualizar.";
		json_err($message);
	}
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ClienteService($conexao1);
	$ok = $service->delete($_POST['cliente_id']);
	if ($ok) {
		json_ok();
	} else {
		$message = (getenv('APP_DEBUG') === 'true' && $service->getLastError()) ? $service->getLastError() : "Erro ao remover.";
		json_err($message);
	}
}
?>