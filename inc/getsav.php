<?php
	
	include("../inc/functions.php");
	include("../inc/seguranca.php");
	protegePagina();
	
	$tipo_id = $_POST['tipo_id'];
	$idpecas = $_POST['id_pecas'];
	$nomtipo = fc_select_name('tipo_id',$tipo_id,'tipo_nome','tp_tipo_tb',$conexao1);
	$nomtipo = limita_caracteres($nomtipo,30,false);
	 
	//$nomecli = preg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomepet'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
	$nomecli 	= $_POST['nomepet'];
	//$nomtipo = preg_replace("[^a-zA-Z0-9_]", "", strtr($nomtipo, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ= ", "aaaaeeiooouucAAAAEEIOOOUUC-_"));
	$nompeca = $nomtipo."-".$nomecli;
	
	$usu_nivel = $_SESSION['usuarioNivel'];
	$usu_idusu = $_SESSION['usuarioID'];
	
	$Qcdsv = mysqli_query($conexao1,"select cod_sav from tp_pecas_tb where cod_sav = '".$_POST['codsav']."' limit 1 ");
	
	if(mysqli_num_rows($Qcdsv)==1){
		
		$query_doc = mysqli_query($conexao1,"UPDATE tp_pecas_tb SET 
		tipo_id='"	 .$tipo_id."', 
		id_usu='"	 .$usu_idusu."', 
		nome_pecas='".$nomtipo."', 
		nome_cli='"	 .$nomecli."', 
		cod_pecas='" .str_replace("_|_","&",$_POST['name_text'])."', 
		data_cad='"	 .date('Y-m-d H:i:s')."'
		where id_pecas = '".$idpecas."' 
		and cod_sav = '".$_POST['codsav']."' ");
		echo $idpecas;
		
	}else{
		
		$query_doc = mysqli_query($conexao1,"INSERT INTO tp_pecas_tb SET 
		tipo_id='"	 .$tipo_id."', 
		id_usu='"	 .$usu_idusu."', 
		nome_pecas='".$nomtipo."', 
		nome_cli='"	 .$nomecli."', 
		cod_pecas='" .str_replace("_|_","&",$_POST['name_text'])."', 
		data_cad='"	 .date('Y-m-d H:i:s')."',
		cod_sav='"	 .$_POST['codsav']."' ");
			
		$q_peca = mysqli_query($conexao1,"SELECT MAX(t.id_pecas) AS 'id_pecas' FROM tp_pecas_tb AS t");
		$w_peca = mysqli_fetch_array($q_peca);
		echo $w_peca['id_pecas'];
	}
?>