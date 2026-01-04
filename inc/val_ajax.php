<?php
header("Content-Type: text/html; charset=utf-8",true);

error_reporting(0);
ini_set("display_errors", 0 );
	
include("seguranca.php");
protegePagina();
if($_POST['flag']==1){
	$mxInp = mysqli_query($conexao1,"SELECT MAX(id_input) FROM tp_inputs_tb WHERE listsel = 'N' limit 1 ");
	$mxWil = mysqli_fetch_array($mxInp);
	echo "@campo".$mxWil[0]."@";
}elseif($_POST['flag']==2){
	$tipoid = $_POST['tipoid'];
	$mxInp2 = mysqli_query($conexao1,"SELECT (MAX(input_order)+1) as 'oinput' FROM tp_inputs_tb WHERE listsel='N' and tipo_id='$tipoid' limit 1 ");
	$mxWil2 = mysqli_fetch_array($mxInp2);
	if(mysqli_num_rows($mxInp2)>0){
		echo $mxWil2['oinput'];
	}else{
		echo 1;
	}
}

?>