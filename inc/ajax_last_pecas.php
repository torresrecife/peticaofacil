<?php

require_once __DIR__ . '/seguranca.php';
require_once __DIR__ . "/response.php";
protegePagina();

$usu_setor = $_SESSION['usuarioSetor'];
$usu_nivel = $_SESSION['usuarioNivel'];
$usu_id = $_SESSION['usuarioID'];
$usu_cliente = $_SESSION['usuarioCliente'];

$cacheDir = __DIR__ . '/../storage/cache';
$cacheKey = 'last_pecas_' . $usu_nivel . '_' . $usu_id . '_' . $usu_setor . '_' . $usu_cliente;
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';
$cacheTtl = 120;

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
	$cached = json_decode(file_get_contents($cacheFile), true);
	if (is_array($cached) && isset($cached['html'])) {
		json_ok(array('html' => $cached['html']));
	}
}

$texto_pecas = "";
if ($usu_nivel == 'USU') {
	$texto_pecas = "Suas últimas petições salvas:";
} else {
	if ($usu_setor != 0) {
		$texto_pecas = "Últimas petições do setor:";
		if ($usu_cliente != 0) {
			$texto_pecas = "Últimas petições da carteira:";
		}
	} else {
		$texto_pecas = "Últimas petições:";
	}
}

$qpet1 = array();
$qpet2 = array();
if (class_exists(\App\Services\PecaService::class)) {
	$pecaService = new \App\Services\PecaService($conexao1);
	$qpet1 = $pecaService->listRecent($usu_nivel, $usu_id, $usu_setor, $usu_cliente, 10);
	$qpet2 = $pecaService->listTodayCounts($usu_nivel, $usu_id, $usu_setor, $usu_cliente);
}

$a = 0;
$usu = array();
foreach ($qpet2 as $wpet2) {
	$nomeUsu = $wpet2["nome_usu"];
	$total = (int) ($wpet2["total"] ?? 0);
	$usu[$nomeUsu] = $total;
	$a += $total;
}
ob_start();
require __DIR__ . "/views/ajax_last_pecas.php";
$html = ob_get_clean();
if (function_exists('app_to_utf8')) {
	$html = app_to_utf8($html);
}
if (is_dir($cacheDir)) {
	file_put_contents($cacheFile, json_encode(array('html' => $html)));
}
json_ok(array('html' => $html));
