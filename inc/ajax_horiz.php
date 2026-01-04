<?php 
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	error_reporting(0);
	ini_set("display_errors", 0 );
	
	include("seguranca.php");
	protegePagina();
	
	$hinput	   = ($_POST['hinput']);
	$inputdb_0 = $_POST['inputdb_0'];
	$inputdb_1 = $_POST['inputdb_1'];
	$inputdb_2 = $_POST['inputdb_2'];
	$inputdb_3 = $_POST['inputdb_3'];
	$inputdb_4 = $_POST['inputdb_4'];
	$inputdb_5 = $_POST['inputdb_5'];
	
	if($inputdb_4=="vert"){
		$conca = "*";
		$where = $inputdb_3 ? $inputdb_3 : '1=1';
		$and = "";
	}elseif($inputdb_4=="hori"){
		$conca = "id_lista,id_grupo,concat(return_2,return_3,return_4,return_5,return_6) as nome_lista";
		$where = $inputdb_5 ? " nome_lista='".$hinput . "' " : "1=1";
		$and = "and " . $inputdb_3; 
	}
	
	$qsel = mysqli_query($conexao1,"SELECT $conca FROM " . $inputdb_0 . " WHERE $where $and ORDER BY " . $inputdb_1 . " asc ");    
	//echo "SELECT $conca FROM " . $inputdb_0 . " WHERE $where $and ORDER BY " . $inputdb_1 . " asc ";
	echo "<option></option>";
	while($wsel = mysqli_fetch_array($qsel))
	{
		echo "<option value='" . $wsel[2] . "' ident='" . $wsel[0] . "' " . ( trim($dd_input)==trim($wsel[$inputdb_1]) ? 'selected' : '') . " >" . $wsel[$inputdb_1] . "</option>";
	}
	
?>