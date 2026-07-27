<?php
	
	error_reporting(0);
	ini_set("display_errors", 0 );

	require_once __DIR__ . "/functions.php";
	require_once __DIR__ . "/seguranca.php";
	protegePagina();
	$doc_buffer = $_POST['name_text']."<div align='right' style='color:#ccc;'><i>".$_SESSION['usuarioLogin']."</i></div>";

	function normalize_pdf_image_src($html)
	{
		$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
		$projectRoot = dirname(__DIR__);

		return preg_replace_callback('/\bsrc=(["\'])(.*?)\1/i', function ($matches) use ($docRoot, $projectRoot) {
			$quote = $matches[1];
			$src = html_entity_decode(trim($matches[2]), ENT_QUOTES, 'UTF-8');

			if ($src === '' || strpos($src, 'data:') === 0 || preg_match('#^https?://#i', $src)) {
				return 'src=' . $quote . $src . $quote;
			}

			$clean = preg_replace('/[#?].*$/', '', $src);
			$clean = rawurldecode($clean);
			$clean = str_replace('\\', '/', $clean);

			$candidates = array();

			if (preg_match('#^file://#i', $clean)) {
				$localPath = preg_replace('#^file:(//)?#i', '', $clean);
				$localPath = preg_replace('#^/([A-Za-z]:/)#', '$1', $localPath);
				$candidates[] = $localPath;
			} elseif (isset($clean[0]) && $clean[0] === '/') {
				if ($docRoot !== '') {
					$candidates[] = $docRoot . $clean;
					$candidates[] = $docRoot . '/public' . $clean;
				}
				$candidates[] = $projectRoot . $clean;
				$candidates[] = $projectRoot . '/public' . $clean;
			} else {
				$candidates[] = $clean;
				if ($docRoot !== '') {
					$candidates[] = $docRoot . '/' . $clean;
					$candidates[] = $docRoot . '/public/' . $clean;
				}
				$candidates[] = $projectRoot . '/' . ltrim($clean, '/');
				$candidates[] = $projectRoot . '/public/' . ltrim($clean, '/');
			}

			$appMarker = '/peticaofacil/';
			$markerPos = strpos(strtolower($clean), $appMarker);
			if ($markerPos !== false) {
				$suffix = ltrim(substr($clean, $markerPos + strlen($appMarker)), '/');
				$candidates[] = $projectRoot . '/' . $suffix;
				$candidates[] = $projectRoot . '/public/' . $suffix;
			}

			foreach ($candidates as $candidate) {
				$candidate = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $candidate);
				$real = realpath($candidate);
				if ($real !== false) {
					$real = str_replace('\\', '/', $real);
					if (preg_match('/^[A-Za-z]:\//', $real)) {
						return 'src=' . $quote . 'file:///' . $real . $quote;
					}
					return 'src=' . $quote . 'file://' . $real . $quote;
				}
			}

			// Windows + caracteres acentuados pode falhar no realpath; usa melhor candidato decodificado.
			if (!empty($candidates)) {
				foreach ($candidates as $candidate) {
					$path = str_replace('\\', '/', $candidate);
					$path = preg_replace('#^/([A-Za-z]:/)#', '$1', $path);
					if (preg_match('/^[A-Za-z]:\//', $path)) {
						return 'src=' . $quote . 'file:///' . $path . $quote;
					}
				}
			}

			return 'src=' . $quote . $src . $quote;
		}, $html);
	}
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
		$arr_pecas = null;
		if (class_exists(\App\Services\PecaService::class)) {
			$pecaService = new \App\Services\PecaService($conexao1);
			$arr_pecas = $pecaService->getById($_POST['id_pecas']);
		}
		if ($arr_pecas) {
			echo normalize_pdf_image_src($arr_pecas['cod_pecas']) . "<p style='float:left'>" . $_SESSION['usuarioID'] . "</p>";
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
