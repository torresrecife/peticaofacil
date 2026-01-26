<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="I"){
	$toptitle = $_POST['toptitle'] ? (strtoupper($_POST['toptitle'])) : "''";
	$tipo_id =  $_POST['tipo_id']  ? $_POST['tipo_id']  : "''";
	$title = '<div class="titulos">' . $toptitle . '</div><p>&nbsp;</p><p align="left"></p>';
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->create($tipo_id, $toptitle);
	if ($ok === 2) {
		json_err("Título já está em uso.");
	}
	$ok ? json_ok(array('title' => $title)) : json_err("Erro ao criar paragrafo.");
	
}elseif($_POST['flag']=="S"){
	$fund_id   = $_POST['fund_id']   ? $_POST['fund_id']   : "''";
	$fund_text = $_POST['fund_text'] ? str_replace("%u2013","-",$_POST['fund_text']) : "''";
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->updateText($fund_id, $fund_text);
	$ok ? json_ok() : json_err("Erro ao salvar paragrafo.");
}elseif($_POST['flag']=="T"){
	json_err("Operacao invalida.");
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->delete($_POST['idvalor']);
	$ok ? json_ok() : json_err("Erro ao remover paragrafo.");
}elseif($_POST['flag']=="DT"){
	json_err("Operacao invalida.");
}elseif($_POST['flag']=="C"){
	json_err("Operacao invalida.");
}elseif($_POST['flag']=="R"){
	json_err("Operacao invalida.");
}

?>