<?php

error_reporting(0);
ini_set("display_errors", 0 );

header("Content-Type: text/html; charset=ISO-8859-1",true);

include("seguranca.php");
protegePagina();

$tabela = $_POST['tabela'] 	? $_POST['tabela'] : "''";
$campo0 = $_POST['campo0']	? $_POST['campo0'] : "''";
$id_ref = $_POST['id_ref']	? $_POST['id_ref'] : "''";
$id_val = $_POST['id_val']	? $_POST['id_val'] : "''";

if($_POST['conex']==1)
{
	$conex 	= $conexao1;
}
elseif($_POST['conex']==2)
{
	$conex 	= $conexao2;
}

$sel  = " SELECT $campo0";
$sel .= " FROM $tabela";
$sel .= " where ";
$sel .= " $id_ref = $id_val";
$sel = str_replace("\'","'",$sel);

$query = mysqli_query($conex,$sel);
$while = mysqli_fetch_array($query);
$result=$while[0];
echo $result;

?>