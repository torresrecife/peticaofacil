<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="E"){
	$id_db  = $_POST['id_db'];
	$return = "";
	$i = 0;
	$q  = " SELECT * FROM tp_config_db";
	$q .= " WHERE id_db = $id_db";
	$query = mysqli_query($conexao1,$q);
	$while = mysqli_fetch_row($query);
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	foreach($while as $w){
		echo $w . "-|-";
	}
}elseif($_POST['flag']=="I"){
	$i  = " INSERT INTO tp_config_db SET";
	$i .= " nome_db   = '" 	. $_POST['nome_db'] . "', " ;
	$i .= " ip_db 	  = '" 	. $_POST['ip_db'] 	. "', " ;
	$i .= " data_db   = '" 	. $_POST['data_db'] . "', " ;
	$i .= " usu_db 	  = '" 	. $_POST['usu_db'] 	. "', " ;
	$i .= " senha_db  = '"	. $_POST['senha_db'] . "', " ;
	$i .= " table_db  = '"	. $_POST['table_db'] . "', " ;
	$i .= " chave_db  = '"	. $_POST['chave_db'] . "', " ;
	$i .= " query_db  = '"	. addslashes($_POST['query_db']) . "', " ;
	$i .= " where_db  = '"	. addslashes($_POST['where_db']) . "' " ;
	$query = mysqli_query($conexao1,$i);
	echo 1;
}elseif($_POST['flag']=="U"){
	$i  = " UPDATE tp_config_db SET";
	$i .= " nome_db 	= '"  . $_POST['nome_db']  . "', " ;
	$i .= " ip_db 		= '" . $_POST['ip_db'] . "', " ;
	$i .= " data_db 	= '" . $_POST['data_db'] . "', " ;
	$i .= " usu_db 		= '" . $_POST['usu_db'] . "', " ;
	$i .= " senha_db 	= '" . $_POST['senha_db'] . "', " ;
	$i .= " table_db 	= '" . $_POST['table_db'] . "', " ;
	$i .= " chave_db 	= '" . $_POST['chave_db'] . "' " ;
	$i .= " query_db 	= '" . addslashes($_POST['query_db']) . "' " ;
	$i .= " where_db 	= '" . addslashes($_POST['where_db']) . "' " ;
	$i .= " WHERE id_db = " . $_POST['id_db'] 	. " " ;
	$query = mysqli_query($conexao1,$i);
	echo 1;
}elseif($_POST['flag']=="D"){
	mysqli_query($conexao1,"DELETE FROM `tp_config_db` WHERE `id_db`='" . $_POST['id_db'] . "' LIMIT 1");
	echo 1;
}
?>