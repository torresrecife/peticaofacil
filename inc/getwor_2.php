<?php
// Example use
	error_reporting(0);
	ini_set(“display_errors”, 0 );

include("../Html2Rtf/class_rtf.php");
$rtf = new rtf("../Html2Rtf/rtf_config.php");
$rtf->setPaperSize(5);
$rtf->setPaperOrientation(1);
$rtf->setDefaultFontFace(1);
$rtf->setDefaultFontSize(24);
$rtf->setAuthor("Fábio Torres");
$rtf->setOperator("fabiotorres@abraz.adv.br");
$rtf->setTitle("RTF Document");
$rtf->addColour("#000000");

	
if($_POST['is_pecas']==1){
	
	include("../inc/seguranca.php");
	protegePagina();
	
	$query_pecas = mysqli_query($conexao1,"SELECT * from tp_pecas_tb where id_pecas='".$_POST['id_pecas']."' ") or die(mysqli_error());
	$arr_pecas = mysqli_fetch_array($query_pecas);
	$rtf->addText($arr_pecas['cod_pecas']);
	$rtf->getDocument();
	
} else {

	$text = str_replace('\"','',$_POST['name_text']);
	$rtf->addText($text);
	$rtf->getDocument();
}

?>