<?php

include("seguranca.php");
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
	if (!empty($_POST['lista_json'])) {
		$decoded = json_decode($_POST['lista_json'], true);
		if (!is_array($decoded)) {
			echo 0;
			exit;
		}
		if (class_exists(\App\Services\ListaService::class)) {
			$service = new \App\Services\ListaService($conexao1);
			$numGrupo = $_POST['num_grupo'];
			$isNew = $_POST['novo_grupo'] === 'sim';
			$okGroup = $service->saveGrupo($numGrupo, $_POST['nome_grupo'], $isNew);
			$okRows = $service->saveItens($numGrupo, $decoded);
			echo ($okGroup && $okRows) ? 1 : 0;
			exit;
		}
	}
	if (class_exists(\App\Services\ListaService::class)) {
		$service = new \App\Services\ListaService($conexao1);
		$numGrupo = $_POST['num_grupo'];
		$rows = parseListaRows((int) $_POST['num_list'], $_POST);
		$isNew = $_POST['novo_grupo'] === 'sim';
		$okGroup = $service->saveGrupo($numGrupo, $_POST['nome_grupo'], $isNew);
		$okRows = $service->saveItens($numGrupo, $rows);
		echo ($okGroup && $okRows) ? 1 : 0;
		exit;
	}

	if($_POST['novo_grupo']=="sim"){
		$query = mysqli_query($conexao1," INSERT INTO tp_grupo_tb SET nome_grupo = '" . $_POST['nome_grupo'] . "', id_grupo=" . $_POST['num_grupo'] . ", data_cad=now() ");
	}elseif($_POST['novo_grupo']=="nao"){
		$query = mysqli_query($conexao1," UPDATE tp_grupo_tb SET nome_grupo = '" . $_POST['nome_grupo'] . "', data_cad=now() WHERE id_grupo=" . $_POST['num_grupo'] . " ");
	}
	
	$listas_1 = explode("-|-", $_POST['listas_1']);
	
	mysqli_query($conexao1,"DELETE FROM `tp_lista_tb` WHERE `id_grupo`='" . $listas_1[1] . "' ");
	
	$n 	 = 0;
	$ins = "";
	for ($i = 1; $i <= $_POST['num_list']; $i++) {
		
		if(isset($_POST['listas_'.$i])){
			$all_list = explode("-|-",$_POST['listas_'.$i]);
			$n++;
			$ins .= $all_list[0]." = '".$all_list[1]."' ";
			if($n<9){
				$ins .= ", ";
			}
			if($n==9){
				$query = mysqli_query($conexao1," INSERT INTO tp_lista_tb SET " . $ins . ", data_cad=now() ");
				$n =0;
				$ins = "";
			}
		}
	}
	if($query){
		echo 1;
	}
}
elseif($_POST['flag']=="D")
{
	mysqli_query($conexao1,"DELETE FROM tp_grupo_tb WHERE id_grupo=" . $_POST['num_grupo'] . " LIMIT 1");
	mysqli_query($conexao1,"DELETE FROM tp_lista_tb WHERE id_grupo=" . $_POST['num_grupo'] . " LIMIT 1");
	echo 1;
}
?>