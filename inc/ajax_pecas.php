<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

//parâmetros dos usuários
$usu_setor = $_SESSION['usuarioSetor'];
$usu_nivel = $_SESSION['usuarioNivel'];
$usu_id    = $_SESSION['usuarioID'];

$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 0;
$search = $_POST['search'] ?? '';

if($_POST['flag']=="H"){
	if (!class_exists(\App\Services\PecaService::class)) {
		json_err("Servico indisponivel.");
	}
	$tipoId = $_POST['tipo_id'] ?? '';
	if ($tipoId === '' || !is_numeric($tipoId)) {
		json_err("Tipo invalido.");
	}
	$service = new \App\Services\PecaService($conexao1);
	$result = $service->fetchList((int) $tipoId, $limit, $search, $usu_nivel, $usu_id);
	$rows = $result['rows'];
	$qtd = array($result['total']);
	$tipoId = (int) $tipoId;
	ob_start();
	require __DIR__ . "/views/ajax_pecas.php";
	$html = ob_get_clean();
	json_ok(array('html' => $html));
}
?>

