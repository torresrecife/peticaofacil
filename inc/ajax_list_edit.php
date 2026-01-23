<?php

require_once __DIR__ . "/seguranca.php";
require_once __DIR__ . "/response.php";
protegePagina();

function parseListaRows($numList, $post)
{
	$rows = array();
	$current = array();
	$fields = array('id_lista', 'id_grupo', 'nome_lista', 'return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6', 'id_setor');

	for ($i = 1; $i <= $numList; $i++) {
		if (!isset($post['listas_' . $i])) {
			continue;
		}
		$parts = explode("-|-", $post['listas_' . $i], 2);
		$name = $parts[0] ?? '';
		$value = $parts[1] ?? '';

		if ($name === 'id_lista' && !empty($current)) {
			$rows[] = $current;
			$current = array();
		}

		if ($name !== '') {
			$current[$name] = $value;
		}
	}

	if (!empty($current)) {
		$rows[] = $current;
	}

	foreach ($rows as $idx => $row) {
		foreach ($fields as $field) {
			if (!array_key_exists($field, $row)) {
				$rows[$idx][$field] = '';
			}
		}
	}

	return $rows;
}

if($_POST['flag']=="U" || $_POST['flag']=="I"){
	if (class_exists(\App\Services\ListaService::class)) {
		$service = new \App\Services\ListaService($conexao1);
		$numGrupo = $_POST['num_grupo'];
		if (!empty($_POST['lista_json'])) {
			$rows = json_decode($_POST['lista_json'], true);
			if (!is_array($rows)) {
				json_err("Lista invalida.");
			}
		} else {
			$rows = parseListaRows((int) $_POST['num_list'], $_POST);
		}
		$isNew = $_POST['novo_grupo'] === 'sim';
		$okGroup = $service->saveGrupo($numGrupo, $_POST['nome_grupo'], $isNew);
		$okRows = $service->saveItens($numGrupo, $rows);
		($okGroup && $okRows) ? json_ok() : json_err("Erro ao salvar lista.");
	}
	json_err("Servico indisponivel.");
}
elseif($_POST['flag']=="D")
{
	if (class_exists(\App\Services\ListaService::class)) {
		$service = new \App\Services\ListaService($conexao1);
		$ok = $service->deleteGroup($_POST['num_grupo']);
		$ok ? json_ok() : json_err("Erro ao remover lista.");
	}
	json_err("Servico indisponivel.");
}
?>