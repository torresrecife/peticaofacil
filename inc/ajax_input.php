<?php
header("Content-Type: text/html; charset=ISO-8859-1", true);

error_reporting(0);
ini_set("display_errors", 0);

require_once __DIR__ . "/seguranca.php";

protegePagina();

if ($_POST['flag'] == "I") {
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\InputService($conexao1);
	$status = $service->create($_POST);
	echo $status;
	exit;
} elseif ($_POST['flag'] == "E" && $_POST['campoId'] != '') {
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\InputService($conexao1);
	$row = $service->getInputRow($_POST['campoId']);
	header("Content-Type: text/html; charset=ISO-8859-1", true);
	if ($row) {
		foreach ($row as $value) {
			echo utf8_decode($value) . "-|-";
		}
	}
	exit;
} elseif ($_POST['flag'] == "U" && $_POST['campoId'] != '') {
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\InputService($conexao1);
	$ok = $service->update($_POST['campoId'], $_POST);
	echo $ok ? 1 : 0;
	exit;
} elseif ($_POST['flag'] == "D") {
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\InputService($conexao1);
	$ok = $service->deleteInput($_POST['idvalor']);
	echo $ok ? 1 : 0;
	exit;
} elseif ($_POST['flag'] == "L") {
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$service = new \App\Services\InputService($conexao1);
	$ok = $service->createListSelect($_POST);
	echo $ok ? 1 : 0;
	exit;
} elseif ($_POST['flag'] == "G") {
	if (!class_exists(\App\Services\InputService::class)) {
		echo 0;
		exit;
	}
	$idvalor = $_POST['idvalor'];
	$service = new \App\Services\InputService($conexao1);
	header("Content-Type: text/html; charset=ISO-8859-1", true);
	$rows = $service->listInputsByTipo($idvalor);
	echo "<select name=\"inputLoad\" id=\"inputLoad\" class=\"input-default\" style=\"width:160px; height:20px\">";
	echo "<option></option>";
	foreach ($rows as $row) {
		$id = $row['id_input'];
		$title = $row['input_title'];
		echo "<option value='$(this).val($(\"#campo" . $id . "\").val());'>" . $title . "</option>";
	}
	echo "</select>";
	exit;
}
?>