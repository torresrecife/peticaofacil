<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="S" && $_POST['campoId']!='')
{
	if (!class_exists(\App\Services\SelectService::class)) {
		json_err("Servico indisponivel.");
	}
	$campoId  = $_POST['campoId'];
	$service = new \App\Services\SelectService($conexao1);
	$items = array();
	foreach ($service->listDadosByInput($campoId) as $nome) {
		$items[] = $nome;
	}
	json_ok(array('items' => $items));
}
?>