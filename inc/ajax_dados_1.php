<?php

error_reporting(0);
ini_set("display_errors", 0 );

header("Content-Type: text/html; charset=ISO-8859-1",true);

require_once __DIR__ . "/seguranca.php";
protegePagina();

$tabela = $_POST['tabela'] 	? $_POST['tabela'] : "''";
$campo0 = $_POST['campo0']	? $_POST['campo0'] : "''";
$id_ref = $_POST['id_ref']	? $_POST['id_ref'] : "''";
$id_val = $_POST['id_val']	? $_POST['id_val'] : "''";

if($_POST['conex']==1)
{
	$conex 	= $conexao1;
}
elseif($_POST['conex']==2)
{
	$conex 	= $conexao2;
}

if (!class_exists(\App\Services\CompService::class)) {
	echo 0;
	exit;
}

$service = new \App\Services\CompService($conex);
echo $service->fetchSingleValue($tabela, $campo0, $id_ref, $id_val);

?>