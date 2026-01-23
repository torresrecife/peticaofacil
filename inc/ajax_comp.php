<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

$tabela = $_POST['tabela'] 	? $_POST['tabela'] : "''";
$campo0 = $_POST['campo0']	? $_POST['campo0'] : "''";
$id_ref = $_POST['id_ref']	? $_POST['id_ref'] : "''";
$id_val = $_POST['id_val']	? $_POST['id_val'] : "''";

if (!class_exists(\App\Services\CompService::class)) {
	json_err("Servico indisponivel.");
}

if($_POST['conex']==1)
{
	$conex 	= $conexao1;
}
elseif($_POST['conex']==2)
{
	$conex 	= $conexao2;
}

$service = new \App\Services\CompService($conex);
json_ok(array('value' => $service->fetchResult($tabela, $campo0, $id_ref, $id_val)));
?>