<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="T"){
	$tiposetor = $_POST['tiposetor'] ? $_POST['tiposetor'] : "";
	$tipoclien = $_POST['tipoclien'] ? $_POST['tipoclien'] : 0;
	$tipoarqui = $_POST['tipoarqui'] ? $_POST['tipoarqui'] : "";
	$tiposql   = $_POST['tiposql']   ? $_POST['tiposql']   : "";
	
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	$tipotitle = $_POST['tipotitle'] ? $_POST['tipotitle'] : "''";
	$tipotitle_pre = $_POST['tipotitle_pre'] ? $_POST['tipotitle_pre'] : "''";
	$query = mysqli_query($conexao1,"INSERT INTO `tp_tipo_tb` SET 
		`id_db` 	 = '" . $tiposql . "', 
		`tipo_nome`  = '" . strtoupper($tipotitle) . "',
		`nome_pre` 	 = '" . strtoupper($tipotitle_pre) . "', 
		`id_cliente` = '" . $tipoclien . "', 
		`tipo_data`  = now(), 
		`tipo_stt`   = 'Y', 
		`id_setor`   = '" . $tiposetor . "', 
		`tipo_arq`   = '" . $tipoarqui . "' 	
		")or die("ERRO");
	//print "INSERT INTO `tp_tipo_tb` SET `tipo_nome` = '" . strtoupper($tipotitle) . "', `tipo_usu` = " . $tipoclien . " `tipo_data` = now(), `tipo_stt` = 'Y' ";
	print "OK";
}
elseif($_POST['flag']=="D")
{
	$query = mysqli_query($conexao1,"DELETE FROM `tp_funda_tb` WHERE `fund_id`= " . $_POST['idvalor'] . " LIMIT 1 ") or die("ERRO");
	print "OK";
}
elseif($_POST['flag']=="DT")
{
	$query1 = mysqli_query($conexao1,"DELETE FROM `tp_tipo_tb`   WHERE `tipo_id`= " . $_POST['tipoid'] . " LIMIT 1 ") or die("ERRO");
	print "OK";
}
elseif($_POST['flag']=="C")
{
	$query = mysqli_query($conexao1,"UPDATE `tp_tipo_tb` SET `cod_cabec` = '" . $_POST['fund_text'] . "' WHERE `tipo_id` = " . $_POST['fund_id'] . " ") or die("ERRO");
	print "OK";
	exit;
}
elseif($_POST['flag']=="R")
{
	$query = mysqli_query($conexao1,"UPDATE `tp_tipo_tb` SET `cod_rodap` = '" . $_POST['fund_text'] . "' WHERE `tipo_id` = " . $_POST['fund_id'] . " ") or die("ERRO");
	print "OK";
	exit;
}


?>