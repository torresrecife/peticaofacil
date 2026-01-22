<?php
header("Content-Type: text/html; charset=ISO-8859-1",true);

error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");

protegePagina();

$tipoid = $_POST['tipoid'] ? $_POST['tipoid']:"";

if($_POST['flag']=="I"){
	if (!class_exists(\App\Services\CampoPadraoService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\CampoPadraoService($conexao1);
	$ok = $service->createForTipo($tipoid);
	echo $ok ? 1 : 0;
}
?>