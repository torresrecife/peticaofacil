<?php
	
	include("../inc/functions.php");
	include("../inc/seguranca.php");
	protegePagina();
	
$tipo_id = $_POST['tipo_id'];
$idpecas = $_POST['id_pecas'];
$nomtipo = '';
if (class_exists(\App\Repositories\TipoRepository::class)) {
	$tipoRepo = new \App\Repositories\TipoRepository($conexao1);
	$tipoRow = $tipoRepo->findWithClienteById($tipo_id);
	$nomtipo = $tipoRow['tipo_nome'] ?? '';
}
$nomtipo = limita_caracteres($nomtipo,30,false);
	 
	//$nomecli = preg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomepet'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
	$nomecli 	= $_POST['nomepet'];
	//$nomtipo = preg_replace("[^a-zA-Z0-9_]", "", strtr($nomtipo, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ= ", "aaaaeeiooouucAAAAEEIOOOUUC-_"));
	$nompeca = $nomtipo."-".$nomecli;
	
	$usu_nivel = $_SESSION['usuarioNivel'];
	$usu_idusu = $_SESSION['usuarioID'];
	
$texto = str_replace("_|_","&",$_POST['name_text']);
if (class_exists(\App\Services\PecaService::class)) {
	$pecaService = new \App\Services\PecaService($conexao1);
	$id = $pecaService->savePeca($tipo_id, $idpecas, $nomtipo, $nomecli, $texto, $_POST['codsav'], $usu_idusu);
	echo $id ? $id : 0;
} else {
	echo 0;
}
?>