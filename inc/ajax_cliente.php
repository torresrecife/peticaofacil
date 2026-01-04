<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="E"){
	$cliente_id = $_POST['cliente_id'];
	$return = "";
	$i = 0;
	$q  = " SELECT * FROM tp_clientes_db";
	$q .= " WHERE cliente_id = $cliente_id";
	$query = mysqli_query($conexao1,$q);
	$while = mysqli_fetch_row($query);
	
	foreach($while as $w)
	{
		echo ($w) . "-|-";
	}
}elseif($_POST['flag']=="I"){
	$i  = " INSERT INTO tp_clientes_db SET";
	$i .= " cliente_name 	  = '" . $_POST['cliente_name'] . "', " ;
	$i .= " cliente_cod 	  = '" . $_POST['cliente_cod'] 	. "', " ;
	$i .= " cliente_area 	  = '" . $_POST['cliente_area'] . "', " ;
	$i .= " cliente_creator   = '"	. date("Y-m-d H:i:s")  	. "'  " ;
	$query = mysqli_query($conexao1,$i);
	echo 1;
}elseif($_POST['flag']=="U"){
	$i  = " UPDATE tp_clientes_db SET";
	$i .= " cliente_name 	   = '" . $_POST['cliente_name'] . "', ";
	$i .= " cliente_cod 	   = '" . $_POST['cliente_cod']  . "', ";
	$i .= " cliente_area 	   = '" . $_POST['cliente_area'] . "' ";
	$i .= " WHERE cliente_id   = " . $_POST['cliente_id']    . " " ;
	$query = mysqli_query($conexao1,$i);
	echo 1;
}elseif($_POST['flag']=="D"){
	mysqli_query($conexao1,"DELETE FROM `tp_clientes_db` WHERE `cliente_id`='" . $_POST['cliente_id'] . "' LIMIT 1");
	echo 1;
}
?>