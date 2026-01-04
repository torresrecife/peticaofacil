<?php
	
	error_reporting(0);
	ini_set("display_errors", 0 );

	require_once("functions.php");
	include("seguranca.php");
	protegePagina();
	$doc_buffer = $_POST['name_text']."<div align='right' style='color:#ccc;'><i>".$_SESSION['usuarioLogin']."</i></div>";
	
	$doc_buffer = str_replace('src="','src="'.$_SERVER['DOCUMENT_ROOT'].'',$doc_buffer);
	//echo $doc_buffer;
	//	
	//exit;
	$tipo_id = $_POST['tipo_id'];
	//$nomtipo = fc_select_name('tipo_id',$tipo_id,'tipo_nome','tp_tipo_tb',$conexao1);
	//$nomtipo = limita_caracteres($nomtipo,20,false);
	 
	$nomecli = preg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomepet'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
	//$nomtipo = preg_replace("[^a-zA-Z0-9_]", "", strtr($nomtipo, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ= ", "aaaaeeiooouucAAAAEEIOOOUUC-_"));
	//$nompeca = $nomtipo."-".$nomecli;
	$nompeca = $nomecli;
	
    ob_start();
	echo '<style>.titulos{text-align: center; border: solid 1px black; font-weight:bold;} p{margin:0px;line-height:115%;font-size:11pt;}</style>';
    echo '<page backtop="28mm" backbottom="15mm" backleft="25mm" backright="15mm" >';
	echo '<page_header>';
	echo '<div style="margin-left:20mm; margin-right:15mm; margin-top:0mm;">'.cabecalhoerodape($tipo_id,"cab","pdf",$conexao1).'</div>';
	echo '</page_header>';
    echo '<page_footer>';
	echo '<div style="margin-left:20mm; margin-right:15mm; margin-bottom:0mm; color: #333333">'.cabecalhoerodape($tipo_id,"rod","pdf",$conexao1).'</div>';
	echo '</page_footer>';
	if($_POST['is_pecas']==1){
		$query_pecas = mysqli_query($conexao1,"SELECT * from tp_pecas_tb where id_pecas='".$_POST['id_pecas']."' ") or die(mysqli_error());
		$arr_pecas = mysqli_fetch_array($query_pecas);
		echo $arr_pecas['cod_pecas'] . "<p style='float:left'>" . $_SESSION['usuarioID'] . "</p>";
	} else {
		echo $doc_buffer;
	}
	echo '</page>';
    $content = ob_get_clean();
    // convert in PDF
    require_once('../html2pdf/html2pdf.class.php');
    try
    {
        $html2pdf = new HTML2PDF('P','A4','pt');
	//  $html2pdf->setModeDebug();
        $html2pdf->setDefaultFont('arial');
        $html2pdf->writeHTML($content, isset($_GET['vuehtml']));
        $html2pdf->Output("/".$nompeca.'.pdf','D');
    }
    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }

?>