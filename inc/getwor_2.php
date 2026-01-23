<?php
// Example use
	error_reporting(0);
	ini_set("display_errors", 0);

require_once __DIR__ . "/../Html2Rtf/class_rtf.php";
$rtf = new rtf(__DIR__ . "/../Html2Rtf/rtf_config.php");
$rtf->setPaperSize(5);
$rtf->setPaperOrientation(1);
$rtf->setDefaultFontFace(1);
$rtf->setDefaultFontSize(24);
$rtf->setAuthor("Fabio Torres");
$rtf->setOperator("fabiotorres@abraz.adv.br");
$rtf->setTitle("RTF Document");
$rtf->addColour("#000000");

	
if($_POST['is_pecas']==1){
	
	require_once __DIR__ . "/seguranca.php";
	protegePagina();
	
	$arr_pecas = null;
	if (class_exists(\App\Services\PecaService::class)) {
		$pecaService = new \App\Services\PecaService($conexao1);
		$arr_pecas = $pecaService->getById($_POST['id_pecas']);
	}
	if ($arr_pecas) {
		$rtf->addText($arr_pecas['cod_pecas']);
	}
	$rtf->getDocument();
	
} else {

	$text = str_replace('\"','',$_POST['name_text']);
	$rtf->addText($text);
	$rtf->getDocument();
}

?>