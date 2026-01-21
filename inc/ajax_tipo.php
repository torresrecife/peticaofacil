<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="T"){
	$tiposetor = $_POST['tiposetor'] ? $_POST['tiposetor'] : "";
	$tipoclien = $_POST['tipoclien'] ? $_POST['tipoclien'] : 0;
	$tipoarqui = $_POST['tipoarqui'] ? $_POST['tipoarqui'] : "";
	$tiposql   = $_POST['tiposql']   ? $_POST['tiposql']   : "";
	
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	$tipotitle = $_POST['tipotitle'] ? $_POST['tipotitle'] : "''";
	$tipotitle_pre = $_POST['tipotitle_pre'] ? $_POST['tipotitle_pre'] : "''";
	if (!class_exists(\App\Services\TipoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->create($_POST);
	print $ok ? "OK" : "ERRO";
}
elseif($_POST['flag']=="D")
{
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->delete($_POST['idvalor']);
	print $ok ? "OK" : "ERRO";
}
elseif($_POST['flag']=="DT")
{
	if (!class_exists(\App\Services\TipoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->deleteTipo($_POST['tipoid']);
	print $ok ? "OK" : "ERRO";
}
elseif($_POST['flag']=="C")
{
	if (!class_exists(\App\Services\TipoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->updateCabec($_POST['fund_id'], $_POST['fund_text']);
	print $ok ? "OK" : "ERRO";
	exit;
}
elseif($_POST['flag']=="R")
{
	if (!class_exists(\App\Services\TipoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->updateRodap($_POST['fund_id'], $_POST['fund_text']);
	print $ok ? "OK" : "ERRO";
	exit;
}


?>