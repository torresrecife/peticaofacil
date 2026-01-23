<?php

require_once __DIR__ . "/seguranca.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\SetorService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SetorService($conexao1);
	$row = $service->getRow($_POST['id_setor']);
	if ($row) {
		foreach ($row as $value) {
			echo $value . "-|-";
		}
	}
}elseif($_POST['flag']=="I"){
	if (!class_exists(\App\Services\SetorService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SetorService($conexao1);
	$ok = $service->insert($_POST);
	echo $ok ? 1 : 0;
}elseif($_POST['flag']=="U"){
	if (!class_exists(\App\Services\SetorService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SetorService($conexao1);
	$ok = $service->update($_POST['id_setor'], $_POST);
	echo $ok ? 1 : 0;
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\SetorService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SetorService($conexao1);
	$ok = $service->delete($_POST['id_setor']);
	echo $ok ? 1 : 0;
}
?>