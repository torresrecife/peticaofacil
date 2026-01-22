<?php
error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");
protegePagina();

if($_POST['flag']=="S" && $_POST['campoId']!='')
{
	if (!class_exists(\App\Services\SelectService::class)) {
		echo 0;
		exit;
	}
	$campoId  = $_POST['campoId'];
	$service = new \App\Services\SelectService($conexao1);
	$nomes = "";
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	foreach ($service->listDadosByInput($campoId) as $nome) {
		$nomes .= $nome . "-|-";
	}
	echo trim($nomes);
}
?>