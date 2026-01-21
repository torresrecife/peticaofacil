<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="I"){
	header("Content-Type: text/html; charset=UTF-8");
	$toptitle = $_POST['toptitle'] ? (strtoupper($_POST['toptitle'])) : "''";
	$tipo_id =  $_POST['tipo_id']  ? $_POST['tipo_id']  : "''";
	$title = '<div class="titulos">' . $toptitle . '</div><p>&nbsp;</p><p align="left"></p>';
	if (class_exists(\App\Services\ParagrafoService::class)) {
		$service = new \App\Services\ParagrafoService($conexao1);
		$ok = $service->create($tipo_id, $toptitle);
		print $ok ? "OK" : "ERRO";
	} else {
		$qOrder = mysqli_query($conexao1,"SELECT MAX(fund_order) FROM `tp_funda_tb` WHERE `tipo_id` = $tipo_id LIMIT 1");
		$wOrder = mysqli_fetch_array($qOrder);
		$query = mysqli_query($conexao1,"INSERT INTO `tp_funda_tb` SET `tipo_id` = $tipo_id, `fund_titulo` = '" . utf8_encode($toptitle) . "', `fund_text` = '$title', `fund_order` = " . ($wOrder[0]+1) . " ")or die("ERRO");
		print "OK";
	}
	
}elseif($_POST['flag']=="S"){
	header("Content-Type: text/html; charset=UTF-8");
	$fund_id   = $_POST['fund_id']   ? $_POST['fund_id']   : "''";
	$fund_text = $_POST['fund_text'] ? str_replace("%u2013","-",$_POST['fund_text']) : "''";
	if (class_exists(\App\Services\ParagrafoService::class)) {
		$service = new \App\Services\ParagrafoService($conexao1);
		$ok = $service->updateText($fund_id, $fund_text);
		print $ok ? "OK" : "ERRO";
	} else {
		$query = mysqli_query($conexao1,"UPDATE `tp_funda_tb` SET `fund_text` = '" . $fund_text . "' WHERE `fund_id` = " . $fund_id . " ") or die("ERRO");
		print "OK";
	}
	exit;
}elseif($_POST['flag']=="T"){
	$tiposetor = $_POST['tiposetor'] ? $_POST['tiposetor'] : "";
	$tipoclien = $_POST['tipoclien'] ? $_POST['tipoclien'] : "";
	$tipoarqui = $_POST['tipoarqui'] ? $_POST['tipoarqui'] : "";
	
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	$tipotitle = $_POST['tipotitle'] ? $_POST['tipotitle'] : "''";
	$query = mysqli_query($conexao1,"INSERT INTO `tp_tipo_tb` SET 
		`tipo_nome`  = '" . strtoupper($tipotitle) . "', 
		`id_cliente` = '" . $tipoclien . "', 
		`tipo_data`  = now(), 
		`tipo_stt` 	 = 'Y', 
		`id_setor` 	 = '" . $tiposetor . "', 
		`tipo_arq` 	 = '" . $tipoarqui . "' 	")or die("ERRO");
	//print "INSERT INTO `tp_tipo_tb` SET `tipo_nome` = '" . strtoupper($tipotitle) . "', `id_cliente` = " . $tipoclien . " `tipo_data` = now(), `tipo_stt` = 'Y' ";
	
	$tiposerve = $_POST['tiposerve'] ? $_POST['tiposerve'] : "";
	$tipobanco = $_POST['tipobanco'] ? $_POST['tipobanco'] : "";
	$tipousuar = $_POST['tipousuar'] ? $_POST['tipousuar'] : "";
	$tiposenha = $_POST['tiposenha'] ? $_POST['tiposenha'] : "";
	$tipotable = $_POST['tipotable'] ? $_POST['tipotable'] : "";
	$tipochave = $_POST['tipochave'] ? $_POST['tipochave'] : "";
	$tipoquery = $_POST['tipoquery'] ? addslashes($_POST['tipoquery']) : "";
	$tipowhere = $_POST['tipowhere'] ? addslashes($_POST['tipowhere']) : "where 1=1";
	
	if(	isset($_POST['tiposerve']) && 
		isset($_POST['tipobanco']) && 
		isset($_POST['tipousuar']) && 
		isset($_POST['tiposenha']) && 
		isset($_POST['tipotable']) && 
		isset($_POST['tipochave']))	{
		
		$qOrder = mysqli_query($conexao1,"SELECT MAX(tipo_id) FROM `tp_tipo_tb` LIMIT 1");
		$wOrder = mysqli_fetch_array($qOrder);
		
		$query2 = mysqli_query($conexao1,"INSERT INTO `tp_config_db` SET 
		`tipo_id`  = '" . $wOrder[0] . "', 
		`ip_db`    = '" . $tiposerve . "', 
		`data_db`  = '" . $tipobanco . "', 
		`usu_db`   = '" . $tipousuar . "', 
		`senha_db` = '" . $tiposenha . "', 
		`table_db` = '" . $tipotable . "', 
		`chave_db` = '" . $tipochave . "', 
		`query_db` = '" . ($tipoquery) . "', 
		`where_db` = '" . ($tipowhere) . "' 
		")or die("ERRO");
	}
	
	print "OK";
}elseif($_POST['flag']=="D"){
	if (class_exists(\App\Services\ParagrafoService::class)) {
		$service = new \App\Services\ParagrafoService($conexao1);
		$ok = $service->delete($_POST['idvalor']);
		print $ok ? "OK" : "ERRO";
	} else {
		$query = mysqli_query($conexao1,"DELETE FROM `tp_funda_tb` WHERE `fund_id`= " . $_POST['idvalor'] . " LIMIT 1 ") or die("ERRO");
		print "OK";
	}
}elseif($_POST['flag']=="DT"){
	$query1 = mysqli_query($conexao1,"DELETE FROM `tp_tipo_tb`   WHERE `tipo_id`= " . $_POST['tipoid'] . " LIMIT 1 ") or die("ERRO");
	$query2 = mysqli_query($conexao1,"DELETE FROM `tp_config_db` WHERE `tipo_id`= " . $_POST['tipoid'] . " LIMIT 1 ") or die("ERRO");
	print "OK";
}elseif($_POST['flag']=="C"){
	$query = mysqli_query($conexao1,"UPDATE `tp_tipo_tb` SET `cod_cabec` = '" . $_POST['fund_text'] . "' WHERE `tipo_id` = " . $_POST['fund_id'] . " ") or die("ERRO");
	print "OK";
	exit;
}elseif($_POST['flag']=="R"){
	$query = mysqli_query($conexao1,"UPDATE `tp_tipo_tb` SET `cod_rodap` = '" . $_POST['fund_text'] . "' WHERE `tipo_id` = " . $_POST['fund_id'] . " ") or die("ERRO");
	print "OK";
	exit;
}

?>