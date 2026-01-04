<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="U" || $_POST['flag']=="I"){
	if($_POST['novo_grupo']=="sim"){
		$query = mysqli_query($conexao1," INSERT INTO tp_grupo_tb SET nome_grupo = '" . $_POST['nome_grupo'] . "', id_grupo=" . $_POST['num_grupo'] . ", data_cad=now() ");
	}elseif($_POST['novo_grupo']=="nao"){
		$query = mysqli_query($conexao1," UPDATE tp_grupo_tb SET nome_grupo = '" . $_POST['nome_grupo'] . "', data_cad=now() WHERE id_grupo=" . $_POST['num_grupo'] . " ");
	}
	
	$listas_1 = explode("-|-", $_POST['listas_1']);
	
	mysqli_query($conexao1,"DELETE FROM `tp_lista_tb` WHERE `id_grupo`='" . $listas_1[1] . "' ");
	
	$n 	 = 0;
	$ins = "";
	for ($i = 1; $i <= $_POST['num_list']; $i++) {
		
		if(isset($_POST['listas_'.$i])){
			$all_list = explode("-|-",$_POST['listas_'.$i]);
			$n++;
			$ins .= $all_list[0]." = '".$all_list[1]."' ";
			if($n<9){
				$ins .= ", ";
			}
			if($n==9){
				$query = mysqli_query($conexao1," INSERT INTO tp_lista_tb SET " . $ins . ", data_cad=now() ");
				$n =0;
				$ins = "";
			}
		}
	}
	if($query){
		echo 1;
	}
}
elseif($_POST['flag']=="D")
{
	mysqli_query($conexao1,"DELETE FROM tp_grupo_tb WHERE id_grupo=" . $_POST['num_grupo'] . " LIMIT 1");
	mysqli_query($conexao1,"DELETE FROM tp_lista_tb WHERE id_grupo=" . $_POST['num_grupo'] . " LIMIT 1");
	echo 1;
}
?>