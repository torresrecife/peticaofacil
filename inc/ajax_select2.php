<?php
require_once __DIR__ . "/seguranca.php";
protegePagina();

$rows = array();
if ($_POST['dados'] == 1) {
	if (!class_exists(\App\Services\SelectService::class)) {
		exit;
	}
	$area_id  = $_POST['flag'];
	$service = new \App\Services\SelectService($conexao1);
	$rows = $service->listClientesByArea($area_id);
}

require __DIR__ . "/views/ajax_select2_options.php";