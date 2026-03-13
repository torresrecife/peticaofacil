<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$row = $service->getRow($_POST['id_db']);
	$items = array();
	if ($row) {
		foreach ($row as $value) {
			if (function_exists('app_to_utf8')) {
				$items[] = app_to_utf8($value);
			} else {
				$items[] = $value;
			}
		}
	}
	json_ok(array('items' => $items));
}elseif($_POST['flag']=="I"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$ok = $service->insert($_POST);
	$ok ? json_ok() : json_err("Erro ao inserir.");
}elseif($_POST['flag']=="U"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$ok = $service->update($_POST['id_db'], $_POST);
	$ok ? json_ok() : json_err("Erro ao atualizar.");
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$ok = $service->delete($_POST['id_db']);
	$ok ? json_ok() : json_err("Erro ao remover.");
}
?>
