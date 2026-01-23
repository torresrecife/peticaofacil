<?php 
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	error_reporting(0);
	ini_set("display_errors", 0 );
	
	require_once __DIR__ . "/seguranca.php";
	protegePagina();
	
	$options = "<option></option>";
	if (class_exists(\App\Services\HorizService::class)) {
		$service = new \App\Services\HorizService($conexao1);
		$options = $service->buildOptions($_POST, $dd_input ?? null);
	}
	require __DIR__ . "/views/ajax_horiz_options.php";
	exit;
?>