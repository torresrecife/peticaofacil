<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

$rows = array();
$dadosFlag = $_POST['dados'] ?? null;
if ($dadosFlag == 1) {
	if (!class_exists(\App\Services\SelectService::class)) {
		json_err("Servico indisponivel.");
	}
	$area_id  = $_POST['flag'] ?? '';
	if ($area_id === '' || !is_numeric($area_id)) {
		json_err("Area invalida.");
	}
	$service = new \App\Services\SelectService($conexao1);
	$rows = $service->listClientesByArea((int) $area_id);
}

ob_start();
require __DIR__ . "/views/ajax_select2_options.php";
$html = ob_get_clean();
json_ok(array('html' => $html));