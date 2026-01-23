<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

$rows = array();
if ($_POST['dados'] == 1) {
	if (!class_exists(\App\Services\SelectService::class)) {
		json_err("Servico indisponivel.");
	}
	$area_id  = $_POST['flag'];
	$service = new \App\Services\SelectService($conexao1);
	$rows = $service->listClientesByArea($area_id);
}

ob_start();
require __DIR__ . "/views/ajax_select2_options.php";
$html = ob_get_clean();
json_ok(array('html' => $html));