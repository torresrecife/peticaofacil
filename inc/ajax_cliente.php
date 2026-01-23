<?php

require_once __DIR__ . "/seguranca.php";
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\ClienteService($conexao1);
	$row = $service->getRow($_POST['cliente_id']);
	if ($row) {
		foreach ($row as $value) {
			echo ($value) . "-|-";
		}
	}
}elseif($_POST['flag']=="I"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\ClienteService($conexao1);
	$ok = $service->insert($_POST);
	if ($ok) {
		echo 1;
	} else {
		echo (getenv('APP_DEBUG') === 'true' && $service->getLastError()) ? $service->getLastError() : 0;
	}
}elseif($_POST['flag']=="U"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\ClienteService($conexao1);
	$ok = $service->update($_POST['cliente_id'], $_POST);
	if ($ok) {
		echo 1;
	} else {
		echo (getenv('APP_DEBUG') === 'true' && $service->getLastError()) ? $service->getLastError() : 0;
	}
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\ClienteService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\ClienteService($conexao1);
	$ok = $service->delete($_POST['cliente_id']);
	if ($ok) {
		echo 1;
	} else {
		echo (getenv('APP_DEBUG') === 'true' && $service->getLastError()) ? $service->getLastError() : 0;
	}
}
?>