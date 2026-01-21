<?php 
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	error_reporting(0);
	ini_set("display_errors", 0 );
	
	include("seguranca.php");
	protegePagina();
	
	if (!class_exists(\App\Services\HorizService::class)) {
		echo "<option></option>";
		exit;
	}

	$service = new \App\Services\HorizService($conexao1);
	echo $service->buildOptions($_POST, $dd_input ?? null);
	exit;
	
?>