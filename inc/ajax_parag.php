<?php

require_once __DIR__ . "/seguranca.php";
protegePagina();

if($_POST['flag']=="I"){
	header("Content-Type: text/html; charset=UTF-8");
	$toptitle = $_POST['toptitle'] ? (strtoupper($_POST['toptitle'])) : "''";
	$tipo_id =  $_POST['tipo_id']  ? $_POST['tipo_id']  : "''";
	$title = '<div class="titulos">' . $toptitle . '</div><p>&nbsp;</p><p align="left"></p>';
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->create($tipo_id, $toptitle);
	print $ok ? "OK" : "ERRO";
	
}elseif($_POST['flag']=="S"){
	header("Content-Type: text/html; charset=UTF-8");
	$fund_id   = $_POST['fund_id']   ? $_POST['fund_id']   : "''";
	$fund_text = $_POST['fund_text'] ? str_replace("%u2013","-",$_POST['fund_text']) : "''";
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->updateText($fund_id, $fund_text);
	print $ok ? "OK" : "ERRO";
	exit;
}elseif($_POST['flag']=="T"){
	print "ERRO";
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		print "ERRO";
		exit;
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->delete($_POST['idvalor']);
	print $ok ? "OK" : "ERRO";
}elseif($_POST['flag']=="DT"){
	print "ERRO";
}elseif($_POST['flag']=="C"){
	print "ERRO";
	exit;
}elseif($_POST['flag']=="R"){
	print "ERRO";
	exit;
}

?>