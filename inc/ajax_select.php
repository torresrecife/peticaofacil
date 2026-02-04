<?php
require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

if($_POST['flag']=="S")
{
	if (!class_exists(\App\Services\SelectService::class)) {
		json_err("Servico indisponivel.");
	}
	$campoIdRaw  = $_POST['campoId'] ?? '';
	$campoId = is_string($campoIdRaw) ? trim($campoIdRaw) : $campoIdRaw;
	if ($campoId === '' || !is_numeric($campoId)) {
		$msg = "Campo invalido.";
		if (getenv('APP_DEBUG') === 'true') {
			$msg .= " campoId=" . json_encode($campoIdRaw);
		}
		json_err($msg);
	}
	$service = new \App\Services\SelectService($conexao1);
	$items = array();
	foreach ($service->listDadosByInput($campoId) as $row) {
		$nome = $row['nome_dados'] ?? '';
		$ret = $row['return_1'] ?? '';
		if (function_exists('app_to_utf8')) {
			$nome = app_to_utf8($nome);
			$ret = app_to_utf8($ret);
		}
		$items[] = array(
			'id_dados' => $row['id_dados'] ?? null,
			'nome_dados' => $nome,
			'return_1' => $ret,
		);
	}
	json_ok(array('items' => $items));
}
?>