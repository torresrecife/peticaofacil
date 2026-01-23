<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();
if($_POST['flag']==1){
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\InputService($conexao1);
	$maxId = $service->getMaxInputId();
	$value = "@campo" . ($maxId ?? 0) . "@";
	json_ok(array('value' => $value));
}elseif($_POST['flag']==2){
	if (!class_exists(\App\Services\InputService::class)) {
		json_err("Servico indisponivel.");
	}
	$tipoid = $_POST['tipoid'];
	$service = new \App\Services\InputService($conexao1);
	json_ok(array('value' => $service->getNextInputOrderForTipo($tipoid)));
}

?>