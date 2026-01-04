<?php
header("Content-Type: text/html; charset=ISO-8859-1",true);

error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");

protegePagina();

$tipoid = $_POST['tipoid'] ? $_POST['tipoid']:"";

if($_POST['flag']=="I"){
		$qIns1  = "INSERT INTO tp_inputs_tb SET ";
		$qIns1 .= "tipo_id 	   = $tipoid, ";
 	    $qIns1 .= "input_title = '".('CÓDIGO')."',";
		$qIns1 .= "input_tipo  = 'TEXT',";
	    $qIns1 .= "input_val   = 'pj', ";
	    $qIns1 .= "nomepet     = 'Y' ";
	    $query1 = mysqli_query($conexao1,$qIns1);
		
		$qIns2  = "INSERT INTO tp_inputs_tb SET ";
		$qIns2 .= "tipo_id 	  = $tipoid, ";
 	    $qIns2 .= "input_title = 'VARA',";
		$qIns2 .= "input_tipo  = 'TEXT',";
	    $qIns2 .= "input_val   = 'vara' ";
	    $query2 = mysqli_query($conexao1,$qIns2);
		
		$qIns3  = "INSERT INTO tp_inputs_tb SET ";
		$qIns3 .= "tipo_id 	  = $tipoid, ";
 	    $qIns3 .= "input_title = 'COMARCA',";
		$qIns3 .= "input_tipo  = 'TEXT',";
	    $qIns3 .= "input_val   = 'comarca' ";
	    $query2 = mysqli_query($conexao1,$qIns3);
		
		$qIns4  = "INSERT INTO tp_inputs_tb SET ";
		$qIns4 .= "tipo_id 	  = $tipoid, ";
 	    $qIns4 .= "input_title = 'ESTADO',";
		$qIns4 .= "input_tipo  = 'TEXT',";
	    $qIns4 .= "input_val   = 'uf' ";
	    $query4 = mysqli_query($conexao1,$qIns4);
		
		$qIns5  = "INSERT INTO tp_inputs_tb SET ";
		$qIns5 .= "tipo_id 	  = $tipoid, ";
 	    $qIns5 .= "input_title = 'PROCESSO',";
		$qIns5 .= "input_tipo  = 'TEXT',";
	    $qIns5 .= "input_val   = 'numero_processo' ";
	    $query5 = mysqli_query($conexao1,$qIns5);
		
		$qIns6  = "INSERT INTO tp_inputs_tb SET ";
		$qIns6 .= "tipo_id 	   = $tipoid, ";
 	    $qIns6 .= "input_title = 'AUTOR',";
		$qIns6 .= "input_tipo  = 'TEXT',";
	    $qIns6 .= "input_val   = 'autor' ";
	    $query6 = mysqli_query($conexao1,$qIns6);
		
		$qIns7  = "INSERT INTO tp_inputs_tb SET ";
		$qIns7 .= "tipo_id 	   = $tipoid, ";
 	    $qIns7 .= "input_title = '".('RÉU')."',";
		$qIns7 .= "input_tipo  = 'TEXT',";
	    $qIns7 .= "input_val   = 'reu' ";
	    $query7 = mysqli_query($conexao1,$qIns7);
		
		echo 1;
}
?>