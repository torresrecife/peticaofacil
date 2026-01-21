<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="E"){
	$id_setor = $_POST['id_setor'];
	$return = "";
	$i = 0;
	$q  = " SELECT * FROM tp_setor_tb";
	$q .= " WHERE id_setor = $id_setor";
	$query = mysqli_query($conexao1,$q);
	$while = mysqli_fetch_row($query);
	
	foreach($while as $w){
		echo $w . "-|-";
	}
}elseif($_POST['flag']=="I"){
	if (class_exists(\App\Services\SetorService::class)) {
		$service = new \App\Services\SetorService($conexao1);
		$ok = $service->insert($_POST);
		echo $ok ? 1 : 0;
	} else {
		$i  = " INSERT INTO tp_setor_tb SET";
		$i .= " nome_setor = '" . $_POST['nome_setor'] . "', " ;
		$i .= " data_cad   = '"	. date("Y-m-d H:i:s")  . "' " ;
		$query = mysqli_query($conexao1,$i);
		echo 1;
	}
}elseif($_POST['flag']=="U"){
	if (class_exists(\App\Services\SetorService::class)) {
		$service = new \App\Services\SetorService($conexao1);
		$ok = $service->update($_POST['id_setor'], $_POST);
		echo $ok ? 1 : 0;
	} else {
		$i  = " UPDATE tp_setor_tb SET";
		$i .= " nome_setor 	   = '" . $_POST['nome_setor'] . "' ";
		$i .= " WHERE id_setor =  " . $_POST['id_setor']   . " " ;
		$query = mysqli_query($conexao1,$i);
		echo 1;
	}
}elseif($_POST['flag']=="D"){
	if (class_exists(\App\Services\SetorService::class)) {
		$service = new \App\Services\SetorService($conexao1);
		$ok = $service->delete($_POST['id_setor']);
		echo $ok ? 1 : 0;
	} else {
		mysqli_query($conexao1,"DELETE FROM `tp_setor_tb` WHERE `id_setor`='" . $_POST['id_setor'] . "' LIMIT 1");
		echo 1;
	}
}
?>