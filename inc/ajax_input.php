<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";

protegePagina();

if ($_POST['flag'] == "I") {
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\InputService($conexao1);
	$status = $service->create($_POST);
	$status ? json_ok(array('status' => $status)) : json_err("Erro ao inserir.", array('status' => $status));
} elseif ($_POST['flag'] == "E" && $_POST['campoId'] != '') {
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\InputService($conexao1);
	$row = $service->getInputRow($_POST['campoId']);
	$items = array();
	if ($row) {
		foreach ($row as $value) {
			$items[] = $value;
		}
	}
	json_ok(array('items' => $items));
} elseif ($_POST['flag'] == "U" && $_POST['campoId'] != '') {
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\InputService($conexao1);
	$ok = $service->update($_POST['campoId'], $_POST);
	$ok ? json_ok() : json_err("Erro ao atualizar.");
} elseif ($_POST['flag'] == "D") {
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\InputService($conexao1);
	$ok = $service->deleteInput($_POST['idvalor']);
	$ok ? json_ok() : json_err("Erro ao remover.");
} elseif ($_POST['flag'] == "L") {
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\InputService($conexao1);
	$ok = $service->createListSelect($_POST);
	$ok ? json_ok() : json_err("Erro ao criar lista.");
} elseif ($_POST['flag'] == "G") {
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$idvalor = $_POST['idvalor'];
	$service = new \App\Services\InputService($conexao1);
	$rows = $service->listInputsByTipo($idvalor);
	ob_start();
	require __DIR__ . "/views/ajax_input_select.php";
	$html = ob_get_clean();
	json_ok(array('html' => $html));
}
?>