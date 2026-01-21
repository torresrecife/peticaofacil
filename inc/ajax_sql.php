<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="E"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$row = $service->getRow($_POST['id_db']);
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	if ($row) {
		foreach ($row as $value) {
			echo $value . "-|-";
		}
	}
}elseif($_POST['flag']=="I"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$ok = $service->insert($_POST);
	echo $ok ? 1 : 0;
}elseif($_POST['flag']=="U"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$ok = $service->update($_POST['id_db'], $_POST);
	echo $ok ? 1 : 0;
}elseif($_POST['flag']=="D"){
	if (!class_exists(\App\Services\SqlConfigService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\SqlConfigService($conexao1);
	$ok = $service->delete($_POST['id_db']);
	echo $ok ? 1 : 0;
}
?>