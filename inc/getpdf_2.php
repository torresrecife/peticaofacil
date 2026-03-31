<?php
	
	//error_reporting(0);
	//ini_set("display_errors", 0 );
    //
	//require_once("functions.php");
	//include("seguranca.php");
	//protegePagina();
	////$doc_buffer = "<style> p{height:0px}; </style>";
	//$doc_buffer .= $_POST['name_text'];
	//$doc_buffer = str_replace('src="','src="'.$_SERVER['DOCUMENT_ROOT'].'',$doc_buffer);
	//
	//$tipo_id = $_POST['tipo_id'];
	// 
	//$nomecli = preg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomepet'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
	//$nompeca = $nomecli;
	//
	//$header = '
	//<table width="100%">
	//	<tr>
	//		<td width="33%" align="right" style="height:55px;">' . cabecalhoerodape($tipo_id,"cab","pdf") . '</td>
	//	</tr>
	//</table>';
    //
	//$footer = '<div align="center" style="height:15px">'.cabecalhoerodape($tipo_id,"rod","pdf").'</div>';
	//include("../mpdf60/mpdf.php");
	////$mpdf=new mPDF('c','A4','','',32,25,47,47,10,10); 
	//$mpdf=new mPDF('utf-8'); 
    //
	//$mpdf->SetHTMLHeader($header);
	//$mpdf->SetHTMLFooter($footer);
	//$mpdf->setDefaultFont('arial');
	//$mpdf->WriteHTML($doc_buffer, isset($_GET['vuehtml']));
	//$mpdf->Output($nompeca.'.pdf','D');
	//
	//exit;
	
	error_reporting(0);
	ini_set("display_errors", 0 );

	require_once __DIR__ . "/functions.php";
	require_once __DIR__ . "/seguranca.php";
	protegePagina();
	$doc_buffer = $_POST['name_text']."<div align='right' style='color:#ccc;'><i>".$_SESSION['usuarioLogin']."</i></div>";

	function normalize_pdf_image_src($html)
	{
		$baseUrl = getenv('APP_URL') ?: '';
		$basePath = $baseUrl ? rtrim(parse_url($baseUrl, PHP_URL_PATH), '/') . '/' : '/';
		return preg_replace_callback('/src="([^"]+)"/i', function ($matches) use ($basePath) {
			$src = $matches[1];
			if ($src === '' || strpos($src, 'data:') === 0 || preg_match('#^https?://#i', $src)) {
				return 'src="' . $src . '"';
			}
			$normalized = $src;
			if (strpos($normalized, $basePath) === 0) {
				$normalized = '/' . ltrim(substr($normalized, strlen($basePath)), '/');
			}
			if ($normalized[0] === '/') {
				$path = $_SERVER['DOCUMENT_ROOT'] . $normalized;
			} else {
				$path = $normalized;
			}
			$real = realpath($path);
			if (!$real) {
				$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
				$prefix = $basePath !== '/' ? rtrim($basePath, '/') : '';
				$subpath = $normalized;
				if ($prefix !== '' && strpos($normalized, '/' . ltrim($prefix, '/')) === 0) {
					$subpath = substr($normalized, strlen('/' . ltrim($prefix, '/')));
				}
				$subpath = ltrim($subpath, '/');
				$candidate = $docRoot . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $subpath;
				$real = realpath($candidate);
			}
			if (!$real) {
				$publicCandidate = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($normalized, '/');
				$real = realpath($publicCandidate);
			}
			if (!$real) {
				$projectRoot = dirname(__DIR__);
				$publicCandidate = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($normalized, '/');
				$real = realpath($publicCandidate);
			}
			if ($real) {
				$path = $real;
			}
			$path = str_replace('\\', '/', $path);
			$path = rawurldecode($path);
			$path = preg_replace('#^/([A-Za-z]:/)#', '$1', $path);
			if (preg_match('/^[A-Za-z]:\//', $path)) {
				return 'src="file:///' . $path . '"';
			}
			return 'src="file://' . $path . '"';
		}, $html);
	}
	//	echo $doc_buffer;
	//	
	//	exit;
	$tipo_id = $_POST['tipo_id'];
	//$nomtipo = fc_select_name('tipo_id',$tipo_id,'tipo_nome','tp_tipo_tb',$conexao1);
	//$nomtipo = limita_caracteres($nomtipo,20,false);
	 
	$nomecli = preg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomepet'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
	//$nomtipo = preg_replace("[^a-zA-Z0-9_]", "", strtr($nomtipo, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ= ", "aaaaeeiooouucAAAAEEIOOOUUC-_"));
	//$nompeca = $nomtipo."-".$nomecli;
	$nompeca = $nomecli;
	
    ob_start();
	echo '<style>.titulos{text-align: center; border: solid 1px black; font-weight:bold;} p{margin:0px;line-height:115%;font-size:11pt;} </style>';
    echo '<page backtop="28mm" backbottom="15mm" backleft="25mm" backright="15mm" >';
	echo '<page_header>';
	echo '<div style="margin-left:20mm; margin-right:15mm; margin-top:0mm;">'.cabecalhoerodape($tipo_id,"cab","pdf",$conexao1).'</div>';
	echo '</page_header>';
    echo '<page_footer>';
	echo '<div style="margin-left:20mm; margin-right:15mm; margin-bottom:-10mm; margin-top:5mm; color: #333333">'.cabecalhoerodape($tipo_id,"rod","pdf",$conexao1).'</div>';
	echo '</page_footer>';
	if($_POST['is_pecas']==1){
		$arr_pecas = null;
		if (class_exists(\App\Services\PecaService::class)) {
			$pecaService = new \App\Services\PecaService($conexao1);
			$arr_pecas = $pecaService->getById($_POST['id_pecas']);
		}
		if ($arr_pecas) {
			echo normalize_pdf_image_src($arr_pecas['cod_pecas']);
		}
	} else {
		echo normalize_pdf_image_src($doc_buffer);
	}
	echo '</page>';
    $content = ob_get_clean();
    // convert in PDF
    require_once __DIR__ . '/../html2pdf/html2pdf.class.php';
    try
    {
        if (ob_get_length()) {
            ob_end_clean();
        }
        $html2pdf = new HTML2PDF('P','A4','pt', true, 'UTF-8');
	//  $html2pdf->setModeDebug();
        $html2pdf->setDefaultFont('dejavusans');
        $html2pdf->writeHTML($content, isset($_GET['vuehtml']));
        $html2pdf->Output($nompeca.'.pdf','D');
    }
    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }
?>
