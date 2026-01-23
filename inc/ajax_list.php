<?php

require_once __DIR__ . "/seguranca.php";
protegePagina();

if($_POST['flag']=="E")
{
	$id_grupo  = $_POST['id_lista'];
	$return = "";
	if (!class_exists(\App\Repositories\ListaRepository::class)) {
		echo 0;
		exit;
	}

	$wg = null;
	$nun_grupo = null;
	$listRows = array();

	$repo = new \App\Repositories\ListaRepository($conexao1);
	if ($id_grupo != "") {
		$wg = $repo->findGroupById($id_grupo);
		$nun_grupo = $id_grupo;
		$listRows = $repo->listItemsByGroup($id_grupo);
	} else {
		$nun_grupo = $repo->nextGroupId();
	}
	
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	require __DIR__ . "/views/ajax_list.php";
}

?>