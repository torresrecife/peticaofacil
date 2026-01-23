<?php

require_once __DIR__ . "/seguranca.php";
protegePagina();

//parâmetros dos usuários
$usu_setor = $_SESSION['usuarioSetor'];
$usu_nivel = $_SESSION['usuarioNivel'];
$usu_id    = $_SESSION['usuarioID'];

$limit=$_POST['limit'];
$search=$_POST['search'];

if($_POST['flag']=="H"){
	if (!class_exists(\App\Services\PecaService::class)) {
		exit;
	}
	$service = new \App\Services\PecaService($conexao1);
	$result = $service->fetchList($_POST['tipo_id'], $limit, $search, $usu_nivel, $usu_id);
	$rows = $result['rows'];
	$qtd = array($result['total']);
	$tipoId = $_POST['tipo_id'];
	require __DIR__ . "/views/ajax_pecas.php";
}
?>

