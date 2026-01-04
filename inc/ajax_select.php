<?php
error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");
protegePagina();

if($_POST['flag']=="S" && $_POST['campoId']!='')
{
	$campoId  = $_POST['campoId'];
	$i = 0;
	$q  = " SELECT nome_dados FROM tp_dados_tb";
	$q .= " WHERE id_input = " . $campoId . " ";
	$nomes = "";
	$query = mysqli_query($conexao1,$q);
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	while($while = mysqli_fetch_assoc($query)){
		$nomes .= $while['nome_dados'] . "-|-";
	}
	echo trim($nomes);
}
?>