<?php
header("Content-Type: text/html; charset=utf-8",true);

error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");
protegePagina();
if($_POST['flag']==1){
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\InputService($conexao1);
	$maxId = $service->getMaxInputId();
	echo "@campo" . ($maxId ?? 0) . "@";
}elseif($_POST['flag']==2){
	if (!class_exists(\App\Services\InputService::class)) {
		echo 1;
		exit;
	}
	$tipoid = $_POST['tipoid'];
	$service = new \App\Services\InputService($conexao1);
	echo $service->getNextInputOrderForTipo($tipoid);
}

?>