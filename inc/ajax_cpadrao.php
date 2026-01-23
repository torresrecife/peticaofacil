<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";

protegePagina();

$tipoid = $_POST['tipoid'] ? $_POST['tipoid']:"";

if($_POST['flag']=="I"){
	if (!class_exists(\App\Services\CampoPadraoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\CampoPadraoService($conexao1);
	$ok = $service->createForTipo($tipoid);
	$ok ? json_ok() : json_err("Erro ao criar campo padrao.");
}
?>