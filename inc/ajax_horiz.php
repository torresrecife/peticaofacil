<?php 
	require_once __DIR__ . "/seguranca.php";
	require_once __DIR__ . "/response.php";
	protegePagina();
	
	$options = "<option></option>";
	if (class_exists(\App\Services\HorizService::class)) {
		$service = new \App\Services\HorizService($conexao1);
		$options = $service->buildOptions($_POST, $dd_input ?? null);
	} else {
		json_err("Servico indisponivel.");
	}
	ob_start();
	require __DIR__ . "/views/ajax_horiz_options.php";
	$html = ob_get_clean();
	json_ok(array('html' => $html));
?>