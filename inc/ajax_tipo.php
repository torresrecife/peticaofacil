<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="T"){
	$tiposetor = $_POST['tiposetor'] ? $_POST['tiposetor'] : "";
	$tipoclien = $_POST['tipoclien'] ? $_POST['tipoclien'] : 0;
	$tipoarqui = $_POST['tipoarqui'] ? $_POST['tipoarqui'] : "";
	$tiposql   = $_POST['tiposql']   ? $_POST['tiposql']   : "";
	
	$tipotitle = $_POST['tipotitle'] ? $_POST['tipotitle'] : "''";
	$tipotitle_pre = $_POST['tipotitle_pre'] ? $_POST['tipotitle_pre'] : "''";
	if (!class_exists(\App\Services\TipoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->create($_POST);
	$ok ? json_ok() : json_err("Erro ao criar tipo.");
}
elseif($_POST['flag']=="D")
{
	if (!class_exists(\App\Services\ParagrafoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\ParagrafoService($conexao1);
	$ok = $service->delete($_POST['idvalor']);
	$ok ? json_ok() : json_err("Erro ao remover paragrafo.");
}
elseif($_POST['flag']=="DT")
{
	if (!class_exists(\App\Services\TipoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->deleteTipo($_POST['tipoid']);
	$ok ? json_ok() : json_err("Erro ao remover tipo.");
}
elseif($_POST['flag']=="C")
{
	if (!class_exists(\App\Services\TipoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->updateCabec($_POST['fund_id'], $_POST['fund_text']);
	$ok ? json_ok() : json_err("Erro ao atualizar cabecalho.");
}
elseif($_POST['flag']=="R")
{
	if (!class_exists(\App\Services\TipoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->updateRodap($_POST['fund_id'], $_POST['fund_text']);
	$ok ? json_ok() : json_err("Erro ao atualizar rodape.");
}


?>