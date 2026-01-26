<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="T"){
	$tiposetor = isset($_POST['tiposetor']) ? trim((string) $_POST['tiposetor']) : '';
	$tipoclien = isset($_POST['tipoclien']) ? $_POST['tipoclien'] : 0;
	$tipoarqui = isset($_POST['tipoarqui']) ? trim((string) $_POST['tipoarqui']) : '';
	$tiposql   = isset($_POST['tiposql'])   ? $_POST['tiposql']   : "";
	$tipotitle = isset($_POST['tipotitle']) ? trim((string) $_POST['tipotitle']) : '';
	$tipotitle_pre = isset($_POST['tipotitle_pre']) ? trim((string) $_POST['tipotitle_pre']) : '';

	if ($tipotitle === '') {
		json_err("Nome do modelo é obrigatório.");
	}
	if ($tiposetor === '' || !ctype_digit($tiposetor)) {
		json_err("Setor inválido.");
	}
	if (!class_exists(\App\Services\TipoService::class)) {
		json_err("Servico indisponivel.");
	}
	$service = new \App\Services\TipoService($conexao1);
	$ok = $service->create($_POST);
	if ($ok) {
		json_ok();
	}
	$message = "Erro ao criar tipo.";
	if (getenv('APP_DEBUG') === 'true') {
		$detail = $service->getLastError();
		if ($detail) {
			$message .= " " . $detail;
		}
	}
	json_err($message);
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