<?php 
// RTF Generator Class
//
// Example of use:
// 	$rtf = new rtf("rtf_config.php");
// 	$rtf->setPaperSize(5);
// 	$rtf->setPaperOrientation(1);
// 	$rtf->setDefaultFontFace(0);
// 	$rtf->setDefaultFontSize(24);
// 	$rtf->setAuthor("noginn");
// 	$rtf->setOperator("me@noginn.com");
// 	$rtf->setTitle("RTF Document");
// 	$rtf->addColour("#000000");
// 	$rtf->addText($_POST['text']);
// 	$rtf->getDocument();
//

require_once("source_rtf.php");

class rtf {

	// {\colortbl;\red 0\green 0\blue 0;\red 255\green 0\ blue0;\red0 ...}
	var $colour_table = array();
	var $colour_rgb;
	// {\fonttbl{\f0}{\f1}{f...}}
	var $font_table = array();
	var $font_face;
	var $font_size;
	// {\info {\title <title>} {\author <author>} {\operator <operator>}}
	var $info_table = array();
	var $page_width;
	var $page_height;
	var $page_size;
	var $page_orientation;
	var $rtf_version;
	var $tab_width;
	
	var $document;
	var $buffer;
	
	function rtf($config="rtf_config.php") {
		require_once($config);
		
		$this->setDefaultFontFace($font_face);
		$this->setDefaultFontSize($font_size);
		$this->setPaperSize($paper_size);
		$this->setPaperOrientation($paper_orientation);
		$this->rtf_version = $rtf_version;
		$this->tab_width = $tab_width;
	}
	
	function setDefaultFontFace($face) {
		$this->font_face = $face; // $font is interger
	}
	
	function setDefaultFontSize($size) {
		$this->font_size = $size;
	}
	
	function setTitle($title="") {
		$this->info_table["title"] = $title;
	}
	
	function setAuthor($author="") {
		$this->info_table["author"] = $author;
	}
	
	function setOperator($operator="") {
		$this->info_table["operator"] = $operator;
	}
	
	function setPaperSize($size=0) {
		global $inch, $cm, $mm;
		
		// 1 => Letter (8.5 x 11 inch)
		// 2 => Legal (8.5 x 14 inch)
		// 3 => Executive (7.25 x 10.5 inch)
		// 4 => A3 (297 x 420 mm)
		// 5 => A4 (210 x 297 mm)
		// 6 => A5 (148 x 210 mm)
		// Orientation considered as Portrait
		
		switch($size) {
			case 1:
				$this->page_width = floor(8.5*$inch);
				$this->page_height = floor(11*$inch);
				$this->page_size = 1;
				break;	
			case 2:
				$this->page_width = floor(8.5*$inch);
				$this->page_height = floor(14*$inch);
				$this->page_size = 5;
				break;	
			case 3:
				$this->page_width = floor(7.25*$inch);
				$this->page_height = floor(10.5*$inch);
				$this->page_size = 7;
				break;	
			case 4:
				$this->page_width = floor(297*$mm);
				$this->page_height = floor(420*$mm);
				$this->page_size = 8;
				break;	
			case 5:
			default:
				$this->page_width = floor(210*$mm);
				$this->page_height = floor(297*$mm);
				$this->page_size = 9;
				break;	
			case 6:
				$this->page_width = floor(148*$mm);
				$this->page_height = floor(210*$mm);
				$this->page_size = 10;
				break;	
		}
	}
	
	function setPaperOrientation($orientation=0) {
		// 1 => Portrait
		// 2 => Landscape
		
		switch($orientation) {
			case 1:
			default:
				$this->page_orientation = 1;
				break;
			case 2:
				$this->page_orientation = 2;
				break;	
		}
	}
	
	function addColour($hexcode) {
		// Get the RGB values
		$this->hex2rgb($hexcode);
		
		// Register in the colour table array
		$this->colour_table[] = array(
			"red"	=>	$this->colour_rgb["red"],
			"green"	=>	$this->colour_rgb["green"],
			"blue"	=>	$this->colour_rgb["blue"]
		);
	}
	
	// Convert HEX to RGB (#FFFFFF => r255 g255 b255)
	function hex2rgb($hexcode) {
		$hexcode = str_replace("#", "", $hexcode); 
		$rgb = array();
		$rgb["red"] = hexdec(substr($hexcode, 0, 2));
		$rgb["green"] = hexdec(substr($hexcode, 2, 2));
		$rgb["blue"] = hexdec(substr($hexcode, 4, 2));
		
		$this->colour_rgb = $rgb;
	}
	
	// Convert newlines into \par
	function nl2par($text) {
		$text = str_replace("\n", "\\par ", $text);
		
		return $text;
	}
	
	// Add a text string to the document buffer
	function addText($text) {
		$text = str_replace("\n", "", $text);
		$text = str_replace("\t", "", $text);
		$text = str_replace("\r", "", $text);
		
		$this->document .= $text;
	}
	
	// Ouput the RTF file
	function getDocument() {
		$this->buffer .= "{";
		// Header
		$this->buffer .= $this->getHeader();
		// Footer
		$this->buffer .= $this->getFooter();
		// Font table
		$this->buffer .= $this->getFontTable();
		// Colour table
		$this->buffer .= $this->getColourTable();
		// File Information
		$this->buffer .= $this->getInformation();
		// Default font values
		$this->buffer .= $this->getDefaultFont();
		// Page display settings
		$this->buffer .= $this->getPageSettings();
		// Parse the text into RTF
		$this->buffer .= $this->parseDocument();
		$this->buffer .= "}";		
		
		//include("seguranca.php");
		//include("functions.php");
		
		if(!isset($_POST['is_pecas'])){
		
			include("../inc/functions.php");
			include("../inc/seguranca.php");
			protegePagina();
			
			$dir_cont = $_POST['url_dir'];
			$tipo_id = $_POST['tipo_id'];
			//$nomtipo = fc_select_name('tipo_id',$tipo_id,'tipo_nome','tp_tipo_tb',$conexao1);
			//$nomtipo = limita_caracteres($nomtipo,20,false);
			 
			$nomecli = preg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomepet'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
			//$nomtipo = preg_replace("[^a-zA-Z0-9_]", "", strtr($nomtipo, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ= ", "aaaaeeiooouucAAAAEEIOOOUUC-_"));
			//$nompeca = $nomtipo."-".$nomecli;
			$nompeca = $nomecli;
			
					
			$usu_nivel = $_SESSION['usuarioNivel'];
			$usu_idusu = $_SESSION['usuarioID'];
		
			//$query_doc = mysqli_query("INSERT INTO tp_pecas_tb SET 
			//tipo_id='".$tipo_id."', 
			//id_usu='".$usu_idusu."', 
			//nome_pecas='".$nomtipo."', 
			//nome_cli='".$nomecli."', 
			//cod_pecas='".$_POST['name_text']."', 
			//data_cad='".date('Y-m-d H:i:s')."' ");
			
		} else {
			$nompeca = $_POST['nomepet']."-".$_POST['nomecli'];
		}
		
		//$query_doc = mysqli_query("INSERT INTO documentos_tb SET st=1, pasta='', dossie='', subpasta='Corpo Principal', tpdoc='CONTESTAÇÃO', url='" . $dir_cont . '/DEFESA_' . $nomecli . '_' . date('YmdHis') . '.rtf' . "', arquivo='" . 'DEFESA_' . $nomecli . '_' . date('YmdHis') . '.rtf' . "', data_hora_st1='" . date('Y-m-d H:i:s') . "', data_hora_st2='" . date('Y-m-d H:i:s') . "', data_hora_st3='" . date('Y-m-d H:i:s') . "' ");
		//if(!file_exists($dir_cont))
		//{
		//	mkdir("$dir_cont", 0755);
		//}
		//
		//$fopen = fopen("$dir_cont/" . $nomtipo . "-" . $nomecli . "_" . date('YmdHis') . ".rtf", "w");
		//fwrite($fopen,$this->buffer);
		//fclose($fopen);

		
		header("Content-Type: text/enriched\n");
		header("Content-Disposition: attachment; filename=$nompeca.rtf");
		echo $this->buffer;
	}
	
	// Header
	function getHeader() {
		$header_buffer  = "\\rtf{$this->rtf_version}\\ansi\\deff0\\deftab{$this->tab_width} \\paperw11906\\paperh16838\\margl1701\\margr1134\\margt100\\margb134\\gutter0\\ltrsect";
		$header_buffer .= "{\\headerr " . $_POST['cod_cabec'] . "}";
		return $header_buffer;
	}
	function getFooter() {
		$footer_buffer  = "\\ltrpar\\sectd\\ltrsect\\psz6\\sbknone\\linex0\\headery539\\footery0\\colsx708\\endnhere\\pgbrdropt32\\sectlinegrid360\\sectdefaultcl\\sectrsid609040\\sftnbj";
		$footer_buffer .= "{\\footerr{". $_POST['cod_rodap'] . "} \\pard{\\qr{\\field{\\fldinst{\\f18  PAGE }}}\\q0 \\par}}";
		return $footer_buffer;
	}
	
	// Font table
	function getFontTable() {
		global $fonts_array;
		
		$font_buffer = "{\\fonttbl\n";
		foreach($fonts_array AS $fnum => $farray) {
			$font_buffer .= "{\\f{$fnum}\\f{$farray['family']}\\fcharset{$farray['charset']} {$farray['name']}}\n";
		}
		$font_buffer .= "}\n\n";
		
		return $font_buffer;
	}
	
	// Colour table
	function getColourTable() {
		$colour_buffer = "";
		if(sizeof($this->colour_table) > 0) {
			$colour_buffer = "{\\colortbl;\n";
			foreach($this->colour_table AS $cnum => $carray) {
				$colour_buffer .= "\\red{$carray['red']}\\green{$carray['green']}\\blue{$carray['blue']};\n";	
			}
			$colour_buffer .= "}\n\n";
		}
		
		return $colour_buffer;
	}
	
	// Information
	function getInformation() {
		$info_buffer = "";
		if(sizeof($this->info_table) > 0) {
			$info_buffer = "{\\info\n";
			foreach($this->info_table AS $name => $value) {
				$info_buffer .= "{\\{$name} {$value}}";
			}
			$info_buffer .= "}\n\n";
		}
		
		return $info_buffer;
	}
	
	// Default font settings
	function getDefaultFont() {
		$font_buffer = "\\f{$this->font_face}\\fs{$this->font_size}\n";
		
		return $font_buffer;
	}
	
	// Page display settings
	function getPageSettings() {
		if($this->page_orientation == 1)
			$page_buffer = "\\paperw{$this->page_width}\\paperh{$this->page_height}\n";
		else
			$page_buffer = "\\paperw{$this->page_height}\\paperh{$this->page_width}\\landscape\n";
			
		$page_buffer .= "\\pgncont\\pgndec\\pgnstarts1\\pgnrestart\n";
		
		return $page_buffer;
	}
	
	// Convert special characters to ASCII
	function specialCharacters($text) {
		$text_buffer = "";
		for($i = 0; $i < strlen($text); $i++)
			$text_buffer .= $this->escapeCharacter($text[$i]);
		
		return $text_buffer;
	}
	
	// Convert special characters to ASCII
	function escapeCharacter($character) {
		$escaped = "";
		if(ord($character) >= 0x00 && ord($character) < 0x20)
			$escaped = "\\'".dechex(ord($character));
		
		if ((ord($character) >= 0x20 && ord($character) < 0x80) || ord($character) == 0x09 || ord($character) == 0x0A)
			$escaped = $character;
		
		if (ord($character) >= 0x80 and ord($character) < 0xFF)
			$escaped = "\\'".dechex(ord($character));

		switch(ord($character)) {
			case 0x5C:
			case 0x7B:
			case 0x7D:
				$escaped = "\\".$character;
				break;
		}
		
		return $escaped;
	}
	
	// Parse the text input to RTF
	function parseDocument() {
		$doc_buffer = $this->specialCharacters($this->document);
		
		if(preg_match("/<UL>(.*?)<\/UL>/mi", $doc_buffer)) {
			$doc_buffer = str_replace("<UL>", "", $doc_buffer);
			$doc_buffer = str_replace("</UL>", "", $doc_buffer);
			$doc_buffer = preg_replace("/<LI>(.*?)<\/LI>/mi", "\\f3\\'B7\\tab\\f{$this->font_face} \\1\\par", $doc_buffer);
		}
		if(preg_match("/<ul>(.*?)<\/ul>/mi", $doc_buffer)) {
			$doc_buffer = str_replace("<ul>", "", $doc_buffer);
			$doc_buffer = str_replace("</ul>", "", $doc_buffer);
			$doc_buffer = preg_replace("/<li>(.*?)<\/li>/mi", "\\f3\\'B7\\tab\\f{$this->font_face} \\1\\par", $doc_buffer);
		}
		$doc_buffer = preg_replace("/<li style=text-align: justify;>(.*?)<\/li>/mi", "\\f3\\'B7\\tab\\f{$this->font_face} \\1\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<li style=\"text-align: justify;\">(.*?)<\/li>/mi", "\\f3\\'B7\\tab\\f{$this->font_face} \\1\\par", $doc_buffer);
		
		$doc_buffer = str_replace("&aacute;","\'e1", $doc_buffer);
		$doc_buffer = str_replace("&agrave;","\'e0", $doc_buffer);
		$doc_buffer = str_replace("&atilde;","\'e3", $doc_buffer);
		$doc_buffer = str_replace("&acirc;","\'e2",  $doc_buffer);
		$doc_buffer = str_replace("&aring;","\'e5", $doc_buffer);
		$doc_buffer = str_replace("&eacute;","\'e9", $doc_buffer);
		$doc_buffer = str_replace("&ecirc;","\'ea", $doc_buffer);
		$doc_buffer = str_replace("&iacute;","\'ed", $doc_buffer);
		$doc_buffer = str_replace("&oacute;","\'f3", $doc_buffer);
		$doc_buffer = str_replace("&otilde;","\'f5", $doc_buffer);
		$doc_buffer = str_replace("&ocirc;","\'f4", $doc_buffer);
		$doc_buffer = str_replace("&uacute;","\'fa", $doc_buffer);
		
		$doc_buffer = str_replace("&Aacute;","\'c1", $doc_buffer);
		$doc_buffer = str_replace("&Agrave;","\'c0", $doc_buffer);
		$doc_buffer = str_replace("&Atilde;","\'c3", $doc_buffer);
		$doc_buffer = str_replace("&Acirc;","\'c2", $doc_buffer);
		$doc_buffer = str_replace("&Aring;","\'c5", $doc_buffer);
		$doc_buffer = str_replace("&Eacute;","\'c9", $doc_buffer);
		$doc_buffer = str_replace("&Ecirc;","\'ca", $doc_buffer);
		$doc_buffer = str_replace("&Iacute;","\'cd", $doc_buffer);
		$doc_buffer = str_replace("&Oacute;","\'d3", $doc_buffer);
		$doc_buffer = str_replace("&Otilde;","\'d5", $doc_buffer);
		$doc_buffer = str_replace("&Ocirc;","\'d4", $doc_buffer);
		$doc_buffer = str_replace("&Uacute;","\'da", $doc_buffer);
		
		$doc_buffer = str_replace("&ccedil;","\'e7", $doc_buffer); 
		$doc_buffer = str_replace("&Ccedil;","\'c7", $doc_buffer);
		
		$doc_buffer = str_replace("&deg;","\'ba", $doc_buffer);
		$doc_buffer = str_replace("&sect;","§", $doc_buffer);
		$doc_buffer = str_replace("&ordm;","º", $doc_buffer);
		$doc_buffer = str_replace("&uuml;","u", $doc_buffer);
		$doc_buffer = str_replace("&Uuml;","U", $doc_buffer);
		$doc_buffer = str_replace("&ndash;","–", $doc_buffer);
		$doc_buffer = str_replace("&ldquo;","''", $doc_buffer);
		$doc_buffer = str_replace("&rdquo;","''", $doc_buffer);
		$doc_buffer = str_replace("&quot;","''", $doc_buffer);
		$doc_buffer = str_replace("&ordf;","\'aa", $doc_buffer);
		$doc_buffer = str_replace("&frasl;","/", $doc_buffer);
		$doc_buffer = str_replace("&lt;","<", $doc_buffer);
		$doc_buffer = str_replace("&gt;",">", $doc_buffer);
		
		$doc_buffer = preg_replace("/<P>(.*?)<\/P>/mi", "\\1\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV>(.*?)<\/DIV>/mi", "\\1\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<STRONG>(.*?)<\/STRONG>/mi", "\\b \\1\\b0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<EM>(.*?)<\/EM>/mi", "\\i \\1\\i0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<I>(.*?)<\/I>/mi", "\\i \\1\\i0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<U>(.*?)<\/U>/mi", "\\ul \\1\\ul0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<STRIKE>(.*?)<\/STRIKE>/mi", "\\strike \\1\\strike0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<SUB>(.*?)<\/SUB>/mi", "{\\sub \\1}", $doc_buffer);
		$doc_buffer = preg_replace("/<SUP>(.*?)<\/SUP>/mi", "{\\super \\1}", $doc_buffer);
		
		$doc_buffer = preg_replace("/<span style=\"text-decoration: underline;\">(.*?)<\/span>/mi", "\\ul \\1\\ul0 ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: justify;>(.*?)<\/p>/mi", "\\qj \\1\\qj0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right;>(.*?)<\/p>/mi", "\\qr \\1\\qr0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left;>(.*?)<\/p>/mi", "\\ql \\1\\ql0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center;>(.*?)<\/p>/mi", "\\qc \\1\\qc0\\par", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: right>(.*?)<\/p>/mi", "\\qr \\1\\qr0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify>(.*?)<\/p>/mi", "\\qj \\1\\qj0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left>(.*?)<\/p>/mi", "\\ql \\1\\ql0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center>(.*?)<\/p>/mi", "\\qc \\1\\qc0\\par", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: justify;\">(.*?)<\/p>/mi", "\\qj \\1\\qj0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right;\">(.*?)<\/p>/mi", "\\qr \\1\\qr0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left;\">(.*?)<\/p>/mi", "\\ql \\1\\ql0\\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center;\">(.*?)<\/p>/mi", "\\qc \\1\\qc0\\par", $doc_buffer);
		
		
		
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 40px;>(.*?)<\/p>/mi", "\\lin400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 80px;>(.*?)<\/p>/mi", "\\lin800 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 120px;>(.*?)<\/p>/mi", "\\lin1200 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 160px;>(.*?)<\/p>/mi", "\\lin1600 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 200px;>(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 240px;>(.*?)<\/p>/mi", "\\lin2400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 280px;>(.*?)<\/p>/mi", "\\lin2800 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 320px;>(.*?)<\/p>/mi", "\\lin3200 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 360px;>(.*?)<\/p>/mi", "\\lin3600 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 400px;>(.*?)<\/p>/mi", "\\lin4000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 440px;>(.*?)<\/p>/mi", "\\lin4400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 480px;>(.*?)<\/p>/mi", "\\lin4800 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 520px;>(.*?)<\/p>/mi", "\\lin5200 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 560px;>(.*?)<\/p>/mi", "\\lin5600 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 40px;\">(.*?)<\/p>/mi", "\\lin400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 80px;\">(.*?)<\/p>/mi", "\\lin800 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 120px;\">(.*?)<\/p>/mi", "\\lin1200 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 160px;\">(.*?)<\/p>/mi", "\\lin1600 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 200px;\">(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 240px;\">(.*?)<\/p>/mi", "\\lin2400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 280px;\">(.*?)<\/p>/mi", "\\lin2800 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 320px;\">(.*?)<\/p>/mi", "\\lin3200 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 360px;\">(.*?)<\/p>/mi", "\\lin3600 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 400px;\">(.*?)<\/p>/mi", "\\lin4000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 440px;\">(.*?)<\/p>/mi", "\\lin4400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 480px;\">(.*?)<\/p>/mi", "\\lin4800 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 520px;\">(.*?)<\/p>/mi", "\\lin5200 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 560px;\">(.*?)<\/p>/mi", "\\lin5600 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 40px;>(.*?)<\/p>/mi", "\\lin400 \\ql \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 80px;>(.*?)<\/p>/mi", "\\lin800 \\ql \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 120px;>(.*?)<\/p>/mi", "\\lin1200 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 160px;>(.*?)<\/p>/mi", "\\lin1600 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 200px;>(.*?)<\/p>/mi", "\\lin2000 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 240px;>(.*?)<\/p>/mi", "\\lin2400 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 280px;>(.*?)<\/p>/mi", "\\lin2800 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 320px;>(.*?)<\/p>/mi", "\\lin3200 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 360px;>(.*?)<\/p>/mi", "\\lin3600 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 400px;>(.*?)<\/p>/mi", "\\lin4000 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 440px;>(.*?)<\/p>/mi", "\\lin4400 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 480px;>(.*?)<\/p>/mi", "\\lin4800 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 520px;>(.*?)<\/p>/mi", "\\lin5200 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 560px;>(.*?)<\/p>/mi", "\\lin5600 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 40px;\">(.*?)<\/p>/mi", "\\lin400 \\ql \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 80px;\">(.*?)<\/p>/mi", "\\lin800 \\ql \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 120px;\">(.*?)<\/p>/mi", "\\lin1200 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 160px;\">(.*?)<\/p>/mi", "\\lin1600 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 200px;\">(.*?)<\/p>/mi", "\\lin2000 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 240px;\">(.*?)<\/p>/mi", "\\lin2400 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 280px;\">(.*?)<\/p>/mi", "\\lin2800 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 320px;\">(.*?)<\/p>/mi", "\\lin3200 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 360px;\">(.*?)<\/p>/mi", "\\lin3600 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 400px;\">(.*?)<\/p>/mi", "\\lin4000 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 440px;\">(.*?)<\/p>/mi", "\\lin4400 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 480px;\">(.*?)<\/p>/mi", "\\lin4800 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 520px;\">(.*?)<\/p>/mi", "\\lin5200 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 560px;\">(.*?)<\/p>/mi", "\\lin5600 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
				
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 40px;>(.*?)<\/p>/mi", "\\lin400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 80px;>(.*?)<\/p>/mi", "\\lin800 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 120px;>(.*?)<\/p>/mi", "\\lin1200 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 160px;>(.*?)<\/p>/mi", "\\lin1600 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 200px;>(.*?)<\/p>/mi", "\\lin2000 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 240px;>(.*?)<\/p>/mi", "\\lin2400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 280px;>(.*?)<\/p>/mi", "\\lin2800 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 320px;>(.*?)<\/p>/mi", "\\lin3200 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 360px;>(.*?)<\/p>/mi", "\\lin3600 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 400px;>(.*?)<\/p>/mi", "\\lin4000 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 440px;>(.*?)<\/p>/mi", "\\lin4400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 480px;>(.*?)<\/p>/mi", "\\lin4800 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 520px;>(.*?)<\/p>/mi", "\\lin5200 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 560px;>(.*?)<\/p>/mi", "\\lin5600 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 40px;\">(.*?)<\/p>/mi", "\\lin400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 80px;\">(.*?)<\/p>/mi", "\\lin800 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 120px;\">(.*?)<\/p>/mi", "\\lin1200 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 160px;\">(.*?)<\/p>/mi", "\\lin1600 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 200px;\">(.*?)<\/p>/mi", "\\lin2000 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 240px;\">(.*?)<\/p>/mi", "\\lin2400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 280px;\">(.*?)<\/p>/mi", "\\lin2800 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 320px;\">(.*?)<\/p>/mi", "\\lin3200 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 360px;\">(.*?)<\/p>/mi", "\\lin3600 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 400px;\">(.*?)<\/p>/mi", "\\lin4000 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 440px;\">(.*?)<\/p>/mi", "\\lin4400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 480px;\">(.*?)<\/p>/mi", "\\lin4800 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 520px;\">(.*?)<\/p>/mi", "\\lin5200 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 560px;\">(.*?)<\/p>/mi", "\\lin5600 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 40px;>(.*?)<\/p>/mi", "\\lin400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 80px;>(.*?)<\/p>/mi", "\\lin800 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 120px;>(.*?)<\/p>/mi", "\\lin1200 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 160px;>(.*?)<\/p>/mi", "\\lin1600 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 200px;>(.*?)<\/p>/mi", "\\lin2000 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 240px;>(.*?)<\/p>/mi", "\\lin2400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 280px;>(.*?)<\/p>/mi", "\\lin2800 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 320px;>(.*?)<\/p>/mi", "\\lin3200 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 360px;>(.*?)<\/p>/mi", "\\lin3600 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 400px;>(.*?)<\/p>/mi", "\\lin4000 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 440px;>(.*?)<\/p>/mi", "\\lin4400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 480px;>(.*?)<\/p>/mi", "\\lin4800 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 520px;>(.*?)<\/p>/mi", "\\lin5200 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 560px;>(.*?)<\/p>/mi", "\\lin5600 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 40px;\">(.*?)<\/p>/mi", "\\lin400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 80px;\">(.*?)<\/p>/mi", "\\lin800 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 120px;\">(.*?)<\/p>/mi", "\\lin1200 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 160px;\">(.*?)<\/p>/mi", "\\lin1600 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 200px;\">(.*?)<\/p>/mi", "\\lin2000 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 240px;\">(.*?)<\/p>/mi", "\\lin2400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 280px;\">(.*?)<\/p>/mi", "\\lin2800 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 320px;\">(.*?)<\/p>/mi", "\\lin3200 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 360px;\">(.*?)<\/p>/mi", "\\lin3600 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 400px;\">(.*?)<\/p>/mi", "\\lin4000 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 440px;\">(.*?)<\/p>/mi", "\\lin4400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 480px;\">(.*?)<\/p>/mi", "\\lin4800 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 520px;\">(.*?)<\/p>/mi", "\\lin5200 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 560px;\">(.*?)<\/p>/mi", "\\lin5600 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
					
		$doc_buffer = preg_replace("/<p style=\"margin-left: 3cm; text-align: justify;\">(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 4cm; text-align: justify;\">(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 5cm; text-align: justify;\">(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 6cm; text-align: justify;\">(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 7cm; text-align: justify;\">(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 8cm; text-align: justify;\">(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"margin-left: 3cm; text-align: left;\">(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 4cm; text-align: left;\">(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 5cm; text-align: left;\">(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 6cm; text-align: left;\">(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 7cm; text-align: left;\">(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 8cm; text-align: left;\">(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"margin-left: 3cm; text-align: right;\">(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 4cm; text-align: right;\">(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 5cm; text-align: right;\">(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 6cm; text-align: right;\">(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 7cm; text-align: right;\">(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 8cm; text-align: right;\">(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"margin-left: 3cm; text-align: center;\">(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 4cm; text-align: center;\">(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 5cm; text-align: center;\">(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 6cm; text-align: center;\">(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 7cm; text-align: center;\">(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 8cm; text-align: center;\">(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		//Sem áspas
		$doc_buffer = preg_replace("/<p style=margin-left: 3cm; text-align: justify;>(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 4cm; text-align: justify;>(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 5cm; text-align: justify;>(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 6cm; text-align: justify;>(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 7cm; text-align: justify;>(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 8cm; text-align: justify;>(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		                                      
		$doc_buffer = preg_replace("/<p style=margin-left: 3cm; text-align: left;>(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 4cm; text-align: left;>(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 5cm; text-align: left;>(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 6cm; text-align: left;>(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 7cm; text-align: left;>(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 8cm; text-align: left;>(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		                                      
		$doc_buffer = preg_replace("/<p style=margin-left: 3cm; text-align: right;>(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 4cm; text-align: right;>(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 5cm; text-align: right;>(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 6cm; text-align: right;>(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 7cm; text-align: right;>(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 8cm; text-align: right;>(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		                                      
		$doc_buffer = preg_replace("/<p style=margin-left: 3cm; text-align: center;>(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 4cm; text-align: center;>(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 5cm; text-align: center;>(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 6cm; text-align: center;>(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 7cm; text-align: center;>(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 8cm; text-align: center;>(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);		
		
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 3cm;>(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 4cm;>(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 5cm;>(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 6cm;>(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 7cm;>(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: justify; margin-left: 8cm;>(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 3cm;\">(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 4cm;\">(.*?)<\/p>/mi", "\\lin1100 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 5cm;\">(.*?)<\/p>/mi", "\\lin1400 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 6cm;\">(.*?)<\/p>/mi", "\\lin1700 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 7cm;\">(.*?)<\/p>/mi", "\\lin2000 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify; margin-left: 8cm;\">(.*?)<\/p>/mi", "\\lin2300 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 3cm;>(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 4cm;>(.*?)<\/p>/mi", "\\lin1100 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 5cm;>(.*?)<\/p>/mi", "\\lin1400 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 6cm;>(.*?)<\/p>/mi", "\\lin1700 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 7cm;>(.*?)<\/p>/mi", "\\lin2000 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: left; margin-left: 8cm;>(.*?)<\/p>/mi", "\\lin2300 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 3cm;\">(.*?)<\/p>/mi", "\\lin900 \\qj \\1 \\qj0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 4cm;\">(.*?)<\/p>/mi", "\\lin1100 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 5cm;\">(.*?)<\/p>/mi", "\\lin1400 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 6cm;\">(.*?)<\/p>/mi", "\\lin1700 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 7cm;\">(.*?)<\/p>/mi", "\\lin2000 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left; margin-left: 8cm;\">(.*?)<\/p>/mi", "\\lin2300 \\ql \\1 \\ql0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 3cm;>(.*?)<\/p>/mi", "\\lin900 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 4cm;>(.*?)<\/p>/mi", "\\lin1100 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 5cm;>(.*?)<\/p>/mi", "\\lin1400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 6cm;>(.*?)<\/p>/mi", "\\lin1700 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 7cm;>(.*?)<\/p>/mi", "\\lin2000 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: right; margin-left: 8cm;>(.*?)<\/p>/mi", "\\lin2300 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 3cm;\">(.*?)<\/p>/mi", "\\lin900 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 4cm;\">(.*?)<\/p>/mi", "\\lin1100 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 5cm;\">(.*?)<\/p>/mi", "\\lin1400 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 6cm;\">(.*?)<\/p>/mi", "\\lin1700 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 7cm;\">(.*?)<\/p>/mi", "\\lin2000 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right; margin-left: 8cm;\">(.*?)<\/p>/mi", "\\lin2300 \\qr \\1 \\qr0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 3cm;>(.*?)<\/p>/mi", "\\lin900 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 4cm;>(.*?)<\/p>/mi", "\\lin1100 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 5cm;>(.*?)<\/p>/mi", "\\lin1400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 6cm;>(.*?)<\/p>/mi", "\\lin1700 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 7cm;>(.*?)<\/p>/mi", "\\lin2000 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=text-align: center; margin-left: 8cm;>(.*?)<\/p>/mi", "\\lin2300 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 3cm;\">(.*?)<\/p>/mi", "\\lin900 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 4cm;\">(.*?)<\/p>/mi", "\\lin1100 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 5cm;\">(.*?)<\/p>/mi", "\\lin1400 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 6cm;\">(.*?)<\/p>/mi", "\\lin1700 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 7cm;\">(.*?)<\/p>/mi", "\\lin2000 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center; margin-left: 8cm;\">(.*?)<\/p>/mi", "\\lin2300 \\qc \\1 \\qc0\\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=\"text-align: justify;\">(.*?)<\/p>/mi", "\\qj \\1\\qj0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: justify\">(.*?)<\/p>/mi", "\\qj \\1\\qj0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right;\">(.*?)<\/p>/mi", "\\qr \\1\\qr0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: right\">(.*?)<\/p>/mi", "\\qr \\1\\qr0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left;\">(.*?)<\/p>/mi", "\\ql \\1\\ql0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: left\">(.*?)<\/p>/mi", "\\ql \\1\\ql0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"text-align: center\">(.*?)<\/p>/mi", "\\qc \\1\\qc0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<P align=justify>(.*?)<\/P>/mi", "\\qj \\1\\qj0 \\par", $doc_buffer);
		$doc_buffer = preg_replace("/<p align=\"justify\">(.*?)<\/p>/mi", "\\qj \\1\\qj0 \\par", $doc_buffer);

		$doc_buffer = preg_replace("/<p style=border: 0px solid rgb(0, 0, 0); margin-left: 0px; class=cls_para align=justify>(.*?)<\/P>/mi", "\qj \\1\\qj0 \\par", $doc_buffer);
		
		$doc_buffer = preg_replace("/<P align=right>(.*?)<\/P>/mi", "\\qr \\1\\qr0 \\par", $doc_buffer);
		$doc_buffer = preg_replace("/<P align=left>(.*?)<\/P>/mi", "\\ql \\1\\ql0 \\par", $doc_buffer);
		$doc_buffer = preg_replace("/<P align=center>(.*?)<\/P>/mi", "\\qc \\1\\qc0 \\par", $doc_buffer);
		
		$doc_buffer = preg_replace("/<P align=\"right\">(.*?)<\/P>/mi", "\\qr \\1\\qr0 \\par", $doc_buffer);
		$doc_buffer = preg_replace("/<P align=\"left\">(.*?)<\/P>/mi", "\\ql \\1\\ql0 \\par", $doc_buffer);
		$doc_buffer = preg_replace("/<P align=\"center\">(.*?)<\/P>/mi", "\\qc \\1\\qc0 \\par", $doc_buffer);
		
		$doc_buffer = preg_replace("/<DIV style=\"text-align: justify;\">(.*?)<\/DIV>/mi", "\\qj \\1\\qj0", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV style=\"text-align: right;\">(.*?)<\/DIV>/mi", "\\qr \\1\\qr0", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV style=\"text-align: left;\">(.*?)<\/DIV>/mi", "\\ql \\1\\ql0", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV style=\"text-align: center;\">(.*?)<\/DIV>/mi", "\\qc \\1\\qc0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<DIV align=justify>(.*?)<\/DIV>/mi", "\\qj \\1\\qj0 \\line", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV align=right>(.*?)<\/DIV>/mi", "\\qr \\1\\qr0 \\line", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV align=\"right\">(.*?)<\/DIV>/mi", "\\qr \\1\\qr0 \\line", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV align=left>(.*?)<\/DIV>/mi", "\\ql \\1\\ql0 \\line", $doc_buffer);
		$doc_buffer = preg_replace("/<DIV align=center>(.*?)<\/DIV>/mi", "\\qc \\1\\qc0 \\line", $doc_buffer);
		
		$doc_buffer = preg_replace("/<div align=\"right\">(.*?)<\/div>/mi", "\\qr \\1\\qr0 \\line", $doc_buffer);
		$doc_buffer = preg_replace("/<div align=\"center\">(.*?)<\/div>/mi", "\\qc \\1\\qc0 \\line", $doc_buffer);
		
		$doc_buffer = preg_replace("/<FONT face=\"times new roman\">(.*?)<\/FONT>/mi", "\\f0 \\1", $doc_buffer);
		
		//--Tabelas-------------------------------------------------------
		$doc_buffer = str_replace("<TABLE border=1 cellSpacing=0 borderColor=#000000 cellPadding=2 width=500>","{", $doc_buffer);
		$doc_buffer = str_replace("</TABLE>","}", $doc_buffer);
		$doc_buffer = str_replace("<TBODY>","", $doc_buffer);
		$doc_buffer = str_replace("</TBODY>","", $doc_buffer);
				
		$doc_buffer = str_replace("<TR height=30>","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh250\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("</TR>","\\row}", $doc_buffer);
		
		$doc_buffer = preg_replace("/<TD align=left>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<TD align=middle>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<TD>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<TD colSpan=5 align=left>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\q0 ", $doc_buffer);
		
		//minusculas
		$doc_buffer = str_replace("<table border=1 cellSpacing=0 borderColor=#000000 cellPadding=2 width=500>","{", $doc_buffer);
		$doc_buffer = str_replace("</table>","}", $doc_buffer);
		$doc_buffer = str_replace("<tbody>","", $doc_buffer);
		$doc_buffer = str_replace("</tbody>","", $doc_buffer);
		
		$doc_buffer = str_replace("<tr height=30>","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh350\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("<tr height=20>","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh250\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("</tr>","\\row}", $doc_buffer);

		$doc_buffer = preg_replace("/<td align=left>(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td align=middle>(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td>(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td colSpan=5 align=left>(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\q0 ", $doc_buffer);
		
		//minusculas com aspas 
		$doc_buffer = str_replace("<table border=\"1\" cellSpacing=\"0\" borderColor=\"#000000\" cellPadding=\"2\" width=\"800\">","{", $doc_buffer);
		$doc_buffer = str_replace("<table border=\"1\" bordercolor=\"#000000\" cellpadding=\"2\" cellspacing=\"0\" width=\"800\">","{", $doc_buffer);
		$doc_buffer = str_replace("</table>","}", $doc_buffer);
		$doc_buffer = str_replace("<tbody>","", $doc_buffer);
		$doc_buffer = str_replace("</tbody>","", $doc_buffer);
		
		$doc_buffer = str_replace("<tr bgcolor=\"#CCCCCC\" height=\"10\">","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh200\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("<tr bgcolor=\"#CCCCCC\" height=\"20\">","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh250\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("<tr height=\"30\">","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh350\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("<tr height=\"20\">","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh250\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("<tr height=\"10\">","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh130\\trleft-70 ", $doc_buffer);
		$doc_buffer = str_replace("</tr>","\\row}", $doc_buffer);

		$doc_buffer = preg_replace("/<td align=\"left\" width=\"600\">(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth7500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td align=\"center\" width=\"600\">(.*?)<\/td>/mi"," \\1 \\qc \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth7500\\cellx\\q0 ", $doc_buffer);

		$doc_buffer = preg_replace("/<td align=\"left\">(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td align=\"center\">(.*?)<\/td>/mi"," \\1 \\qc \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td align=\"middle\">(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td>(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td colSpan=\"5\" align=\"left\">(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\q0 ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<DIV class=titulos[a-zA-Z0-9-\"\=\'\;\:\s]*>(.*?)<\/DIV>/mi","{{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh300\\trleft-70 \\b \\1\\b0 \\qc \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth9000\\cellx\\q0 \\row}}", $doc_buffer);
		$doc_buffer = preg_replace("/<div class=\"titulos\"[a-zA-Z0-9-\"\=\'\;\:\s]*>(.*?)<\/div>/mi","{{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh300\\trleft-70 \\b \\1\\b0 \\qc \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth9000\\cellx\\q0 \\row}}", $doc_buffer);
		
		$doc_buffer = preg_replace("/<B>(.*?)<\/B>/mi", "\\b \\1\\b0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<STRONG>(.*?)<\/B>/mi", "\\b \\1\\b0 ", $doc_buffer);
		
		//=---------------------------------------------------------------
		
		$doc_buffer = preg_replace("/<p>(.*?)<\/p>/mi", "\\1\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<strong>(.*?)<\/strong>/mi", "\\b \\1\\b0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<strong style=\"font-size: 11pt;\">(.*?)<\/strong>/mi", "\\fs22 \\b \\1\\b0\\fs{$this->font_size} ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<em>(.*?)<\/em>/mi", "\\i \\1\\i0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<i>(.*?)<\/i>/mi", "\\i \\1\\i0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<u>(.*?)<\/u>/mi", "\\ul \\1\\ul0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<strike>(.*?)<\/strike>/mi", "\\strike \\1\\strike0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<sub>(.*?)<\/sub>/mi", "{\\sub \\1}", $doc_buffer);
		$doc_buffer = preg_replace("/<sup>(.*?)<\/sup>/mi", "{\\super \\1}", $doc_buffer);
		
	  //$doc_buffer = preg_replace("/<H1>(.*?)<\/H1>/mi", "\\pard\\qc\\fs40 \\1\\par\\pard\\fs{$this->font_size} ", $doc_buffer);
	  //$doc_buffer = preg_replace("/<H2>(.*?)<\/H2>/mi", "\\pard\\qc\\fs32 \\1\\par\\pard\\fs{$this->font_size} ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<H1>(.*?)<\/H1>/mi", "\\fs48\\b \\1\\b0\\fs{$this->font_size}\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<H2>(.*?)<\/H2>/mi", "\\fs36\\b \\1\\b0\\fs{$this->font_size}\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<H3>(.*?)<\/H3>/mi", "\\fs27\\b \\1\\b0\\fs{$this->font_size}\\par ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<h1>(.*?)<\/h1>/mi", "\\fs48\\b \\1\\b0\\fs{$this->font_size}\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h2>(.*?)<\/h2>/mi", "\\fs36\\b \\1\\b0\\fs{$this->font_size}\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h3>(.*?)<\/h3>/mi", "\\fs27\\b \\1\\b0\\fs{$this->font_size}\\par ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<h1 style=\"text-align: justify;\">(.*?)<\/h1>/mi", "\\qj\\fs48\\b \\1\\b0\\fs{$this->font_size}\\qj0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h2 style=\"text-align: justify;\">(.*?)<\/h2>/mi", "\\qj\\fs36\\b \\1\\b0\\fs{$this->font_size}\\qj0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h3 style=\"text-align: justify;\">(.*?)<\/h3>/mi", "\\qj\\fs27\\b \\1\\b0\\fs{$this->font_size}\\qj0\\par ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<h1 style=\"text-align: left;\">(.*?)<\/h1>/mi", "\\ql\\fs48\\b \\1\\b0\\fs{$this->font_size}\\ql0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h2 style=\"text-align: left;\">(.*?)<\/h2>/mi", "\\ql\\fs36\\b \\1\\b0\\fs{$this->font_size}\\ql0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h3 style=\"text-align: left;\">(.*?)<\/h3>/mi", "\\ql\\fs27\\b \\1\\b0\\fs{$this->font_size}\\ql0\\par ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<h1 style=\"text-align: right;\">(.*?)<\/h1>/mi", "\\qr\\fs48\\b \\1\\b0\\fs{$this->font_size}\\qr0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h2 style=\"text-align: right;\">(.*?)<\/h2>/mi", "\\qr\\fs36\\b \\1\\b0\\fs{$this->font_size}\\qr0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h3 style=\"text-align: right;\">(.*?)<\/h3>/mi", "\\qr\\fs27\\b \\1\\b0\\fs{$this->font_size}\\qr0\\par ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<h1 style=\"text-align: center;\">(.*?)<\/h1>/mi", "\\qc\\fs48\\b \\1\\b0\\fs{$this->font_size}\\qc0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h2 style=\"text-align: center;\">(.*?)<\/h2>/mi", "\\qc\\fs36\\b \\1\\b0\\fs{$this->font_size}\\qc0\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<h3 style=\"text-align: center;\">(.*?)<\/h3>/mi", "\\qc\\fs27\\b \\1\\b0\\fs{$this->font_size}\\qc0\\par ", $doc_buffer);
		
		
		//Fontes-------------
		$doc_buffer = preg_replace("/<FONT size=1>(.*?)<\/FONT>/mi", "\\fs16 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<FONT size=2>(.*?)<\/FONT>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<FONT size=3>(.*?)<\/FONT>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<FONT size=4>(.*?)<\/FONT>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<FONT size=5>(.*?)<\/FONT>/mi", "\\fs32 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<FONT size=6>(.*?)<\/FONT>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<span style=font-size: 8px;>(.*?)<\/span>/mi", "\\fs16 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 9px;>(.*?)<\/span>/mi", "\\fs18 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 10px;>(.*?)<\/span>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 11px;>(.*?)<\/span>/mi", "\\fs22 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 12px;>(.*?)<\/span>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 14px;>(.*?)<\/span>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 16px;>(.*?)<\/span>/mi", "\\fs32 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 18px;>(.*?)<\/span>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);		
		$doc_buffer = preg_replace("/<span style=font-size: 20px;>(.*?)<\/span>/mi", "\\fs40 \\1\\fs{$this->font_size} ", $doc_buffer);		
		
		$doc_buffer = preg_replace("/<span style=\"font-size: 8px;\">(.*?)<\/span>/mi", "\\fs16 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 9px;\">(.*?)<\/span>/mi", "\\fs18 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 10px;\">(.*?)<\/span>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 11px;\">(.*?)<\/span>/mi", "\\fs22 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 12pt;\">(.*?)<\/span>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 14px;\">(.*?)<\/span>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 16px;\">(.*?)<\/span>/mi", "\\fs32 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 18px;\">(.*?)<\/span>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);		
		$doc_buffer = preg_replace("/<span style=\"font-size: 20px;\">(.*?)<\/span>/mi", "\\fs40 \\1\\fs{$this->font_size} ", $doc_buffer);		
		//-------------------
		$doc_buffer = preg_replace("/<span style=\"font-size:8px;\">(.*?)<\/span>/mi", "\\fs16 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:9px;\">(.*?)<\/span>/mi", "\\fs18 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:10px;\">(.*?)<\/span>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:11px;\">(.*?)<\/span>/mi", "\\fs22 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:12pt;\">(.*?)<\/span>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:14px;\">(.*?)<\/span>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:16px;\">(.*?)<\/span>/mi", "\\fs32 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:18px;\">(.*?)<\/span>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);		
		$doc_buffer = preg_replace("/<span style=\"font-size:20px;\">(.*?)<\/span>/mi", "\\fs40 \\1\\fs{$this->font_size} ", $doc_buffer);		
		
		
		$doc_buffer = preg_replace("/<HR(.*?)>/i", "\\brdrb\\brdrs\\brdrw30\\brsp20 \\pard\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<hr(.*?)>/i", "\\brdrb\\brdrs\\brdrw30\\brsp20 \\pard\\par ", $doc_buffer);
		
		//$doc_buffer = preg_replace("/<BLOCKQUOTE style=\"MARGIN-RIGHT: 0px\" dir=ltr>(.*?)<\/BLOCKQUOTE>/mi", "\\lin2708\\1 \\lin0 ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<P class=recuo align=justify>(.*?)<\/P>/mi", "\\lin2000 \\1 \\par \lin0", $doc_buffer);
		
		$doc_buffer = preg_replace("/<p style=margin-left: 3cm;>(.*?)<\/p>/mi", "\\lin2000 \\1 \\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 40px;>(.*?)<\/p>/mi", "\\lin1000 \\1 \\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=margin-left: 200px;>(.*?)<\/p>/mi", "\\lin2000 \\1 \\par \lin0", $doc_buffer);
		$doc_buffer = preg_replace("/<p style=\"margin-left: 200px;\">(.*?)<\/p>/mi", "\\lin2000 \\1 \\par \lin0", $doc_buffer);
				
		$doc_buffer = str_replace("<BR>", "\\line ", $doc_buffer);
		$doc_buffer = str_replace("<BR />", "\\line ", $doc_buffer);
		$doc_buffer = str_replace("<br>", "\\line ", $doc_buffer);
		$doc_buffer = str_replace("<br />", "\\line ", $doc_buffer);
		$doc_buffer = str_replace("&nbsp;", " ", $doc_buffer);
				
		$doc_buffer = str_replace("<TAB>", "\\tab ", $doc_buffer);
		$doc_buffer = str_replace("<tab>", "\\tab ", $doc_buffer);
		$doc_buffer = str_replace("<SPAN class=tabs></SPAN>", "\\tab \\tab ", $doc_buffer);
		$doc_buffer = str_replace("<span class=tabs></span>", "\\tab \\tab ", $doc_buffer);
		$doc_buffer = str_replace("<SPAN class=tabs> </SPAN>", "\\tab \\tab ", $doc_buffer);
		$doc_buffer = str_replace("<span class=tabs> </span>", "\\tab \\tab ", $doc_buffer);		
		$doc_buffer = str_replace("<span style=\"margin-left: 40px\"> </span>","\\tab \\tab ", $doc_buffer);
		//$doc_buffer = str_replace("<span style=\"margin-left: 40px\">(.*?)</span>","\\tab \\1\\tab ", $doc_buffer);
		$doc_buffer = str_replace("<span style=\"margin-left: 40px\">&nbsp;</span>","\\tab \\tab ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"margin-left:40px\">(.*?)<\/span>/mi","\\tab \\tab \\1", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"margin-left: 40px\">(.*?)<\/span>/mi","\\tab \\tab \\1", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"margin-left: 40px\"> (.*?)<\/span>/mi","\\tab \\tab \\1", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=margin-left: 40px>(.*?)<\/span>/mi","\\tab \\tab \\1", $doc_buffer);	
		
		$doc_buffer = preg_replace("/<BLOCKQUOTE (.*?)>/i", " \\lin2000 ", $doc_buffer);
		//$doc_buffer = str_replace("<BLOCKQUOTE dir=ltr>", "\\lin2000 ", $doc_buffer);
		//$doc_buffer = str_replace("<BLOCKQUOTE style=\"MARGIN-RIGHT: 0px\" dir=ltr>", "\\lin2000 ", $doc_buffer);
		$doc_buffer = str_replace("</BLOCKQUOTE>", " \\lin0", $doc_buffer);
		$doc_buffer = str_replace("</blockquote>", " \\lin0", $doc_buffer);
		
		//convertendo para imagem rtf 
		//$image2 = preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\;.+\/>.+/i", "\\1", $doc_buffer);
		//$imgw = preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\;.+\/>.+/i", "\\2", $doc_buffer);
		//$imgh = preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\;.+\/>.+/i", "\\3", $doc_buffer);
		//$imgf = strtolower(substr(trim(preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\; float\:(.*?)\;.+\/>.+/i", "\\4", $doc_buffer)),0,1));
		//$image3 = " ".($imgf=='r'||$imgf=='l'||$imgf=='c'||$imgf=='j'?"\q".$imgf:"")." {\\pict\\wmetafile8\\picw5292\\pich1588\\picwgoal".($imgw*15)."\\pichgoal".($imgh*15)." " . bin2hex(@file_get_contents(trim($image2))) . "} ".($imgf=='r'||$imgf=='l'||$imgf=='c'||$imgf=='j'?"\\q0":"")." \\par";
		//$doc_buffer = preg_replace("/<img alt=\"\" src=\"\/(.*?)\" style=.+\/>/i", "\\1 ", $doc_buffer);
		//$doc_buffer = preg_replace(trim($image2),$image3, $doc_buffer);
											
		$assinatura = "{\\pict\\picscalex71\\picscaley75\\piccropl0\\piccropr0\\piccropt0\\piccropb0\\picw7618\\pich3704\\picwgoal4320\\pichgoal2100\\wmetafile8\\bliptag324137395\\blipupi96{\\*\\blipuid 1351f1b319493a27c0fc7eaf495d7e9d} 0100090000030a5100000000e150000000000400000003010800050000000b0200000000050000000c028c002001030000001e0004000000070104000400000007010400e1500000410b2000cc008c002001000000008c0020010000000028000000200100008c0000000100080000000000000000000000000000000000000000000000000000000000ffffff00f6f6f600f7f7f700fafafa00fdfdfd00fbfbfb00ababab006e6e6e00c4c4c400f0f0f000f4f4f400f9f9f900f5f5f500fcfcfc00f8f8f800eaeaea009d9d9d0083838300d4d4d400fefefe008787870099999900e8e8e800bcbcbc0084848400bdbdbd00e6e6e600d1d1d100d6d6d6008c8c8c00a6a6a600ebebeb00c1c1c10086868600f2f2f200acacac0085858500cfcfcf00e2e2e200909090008d8d8d009e9e9e00ededed00f3f3f300bababa0081818100b8b8b800a3a3a30088888800d3d3d300d7d7d70098989800e9e9e900c5c5c50080808000adadad00eeeeee00aeaeae00c9c9c900dbdbdb009c9c9c008e8e8e00e0e0e000cecece00a1a1a100b7b7b700797979009f9f9f007d7d7d00d5d5d5009393930097979700cccccc00b4b4b400b9b9b900cbcbcb00a0a0a0008a8a8a00e4e4e400dddddd00929292006f6f6f00b2b2b200a7a7a7006d6d6d00cacaca0078787800e7e7e700dcdcdc00ececec00757575009b9b9b00b0b0b00096969600bebebe0068686800efefef007f7f7f0065656500e5e5e500a9a9a900c0c0c000afafaf00a8a8a800535353004b4b4b00a4a4a400c7c7c700d0d0d000e3e3e30082828200c2c2c200bfbfbf00b6b6b6009191910047474700545454009a9a9a0094949400dfdfdf005e5e5e00a5a5a500e1e1e100bbbbbb00d8d8d800dedede0059595900777777008b8b8b00959595005a5a5a00c3c3c300f1f1f100a2a2a20069696900aaaaaa00c8c8c80058585800d9d9d9007e7e7e00b3b3b30067676700b1b1b10073737300cdcdcd00dadada0050505000575757005555550089898900727272008f8f8f00b5b5b5005b5b5b007b7b7b0076767600707070007a7a7a006262620066666600c6c6c600616161006a6a6a004e4e4e005f5f5f003e3e3e003b3b3b002222220020202000252525002d2d2d002f2f2f00646464006c6c6c00717171004a4a4a0034343400373737002b2b2b0074747400484848002e2e2e00313131007c7c7c005d5d5d00d2d2d200565656005252520063636300424242006b6b6b00464646005151510060606000282828003f3f3f00444444004f4f4f003a3a3a004d4d4d00454545004141410021212100363636003333330010101000171717005c5c5c00262626003d3d3d002a2a2a00353535004c4c4c0038383800272727002c2c2c003939390049494900404040000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141405010105140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140505010105140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140e0e14010e05010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414060614140e05010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101050501010c050423010f040114140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414010101062c264c0c0c04050e010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e010105010e5a0748580f0c0e140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010101010e858fb42604060601010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114140101040e568e8d05010c010501010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401050e01140c011d87982c010c01050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114140101050e1b12af50010601010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401010501010d146bad491405010501010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140101010101010101011405050501010114140101140e0114010105140d0426978f0e010106050101141401010501141414011414010101011405140101010101010101010101141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e0e0501010505010101010514050501010101010105140105050c0e1b4e732b04050114010114050101010101010101140101141414010101010501010101010101010101010101010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141401010101010101010101050e14010101010101050105011401011414050d4d9b7e011414010101010105140114010101140e0e0e010101140e05010101010101010101010101010101011414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141414140101010101010506051414140514010105050e0c022c0a2b20106110352b1b4f1b275cae18390a5a610a0b0414050e0e0114060605010101010101010101010101010101141414011414140101010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141414050505050e0505040f061401140e040f0b61176e277e463b5f7267077a540741447a113434419cda375488688d4a18561d597e784f35612302040604010e040614010504050514140505051405051401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141405050e0e0e06060606060e0e0d2b501d40897042652a7747984e15314e4e962998964e47513e127357c7a5124e4e5128735e5c3d76342a684a1a897d17230d0c140114050501060e05050e0e05140e0514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505050e0606040404060c854f325f383482283e299828735e111f8f1a3bba338b50507e7b4f3517787b91afaf5f8b3cba6d56091a993a68304176283e16658d702627610d0304050c060e0e060605140514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010105050e0e0604040404031b094129968147414a66a14c46501761020e1401050614010601010f060c060e0328a21d0506020614050e060f0d35648b3b7c5d7a3d1e98163a3b7b85030c040e0e060605140514010101140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141401010101050101010101011401050e010114140101050e01011414010e0101010101011405050102011403062049484577652fba5920020405060f0c0506061414060605050404010c0e06040401614ba57202050e061414040c0e0e1406060603614f8b4c8f1677443a5f4c92230f2c04140405010104010104010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010114010101011401010114010105010114141414140101140501010e05010e050101140c0f1b68126f654958030d0c0e0e0e14140e0e0e0e06060e0e060c1401140e0c0e050601060564ae251001050f0414050e010105060606060e050e0b39643c133b4254543a56610c0f010e0b0101050101011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010101140514010105051414051401140e050101140501010114010114011414010101010101010e0c0c148588af3d565a02040105010c0f060514050c0601010e040e14140314010505010e04140f050d31ae2704050406050e0e0e060505140114050604040505040c02203f4b769b16130a03030106040e01010e05010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505051405050e0e060e050514010101050e14010106040e0e141405050114040e1414050505040204040f0206018bb41559060c060c2385356420236110647e501b106450504f20030105140614020c0e14040499c25f140f060105061401060114040c0e050e0e140e0606040f03035a926b60528d170f0f0401010c060104141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e050505050e06060e05060c0c04060406051405040c0101050e040c06140e0c060e0e060e060f2c357b7e32567134d519547647983e4e8c439b25812562459eb8124e962eb82e4e294781345e6b416772841a1867d04e3f612c0c04020305010405050e01140e010c0c0c040c0c06050103397031a0313f0c140f14050c010f0514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010105141414050e0e0e01140e0c0c040e0e0c0e060f0601062c04022c2c611b507d40702f8f1f5e813182823481311e9cd6975e4e5151285e82448668656b3068245465388f3a681f653434295e29471e5e3d41772e19d5db29159648658f42897d1723030f0c0d0b04010114030d0c050e020e01041b30bf196d230c04010606060e050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414050e0e0e05060e050e0606040f2c232b35201733846c531173819677864d763d1f240724674a710970364924d512502720020b0b041401050f0f0f040506060e14140e0f022c0a2b8520354f6e781d266c182ec041246888073076163d4844411f4a21407e352b0a230b0c060601040f0e012366192218230c0201010e060501010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140506060e0506050e0361648b1c7099308298985177688654992d2f5f491d8b7e172c0c060c05050f1401041c8ab8231406060406140614040f01010e060e140504040e0506010c14040506140214060f0e0656ad5d0b03030a587b1d3b1a2f54765c16825c5c11887140337b202b060206050c0b465eb82d610f010f0c06050101011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401140105140e0101050e0505060c0f5a7b1d49422a77486b3a2f181a703b403c272b030e0e040f060e0e05050e0e0605050e0c140f5043a339060e0c0114040e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e060514140e040c020c010435af6f230e0f06140f050e0e025a6e92132691849988544111682d2f13133c20390d033f47b44b23140d01030101010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e050101010506140504025a8ba1243d7348887c6c493c1b2c061414060c030c0e14050e04040e0e050505050e0e140e0e030e0e391f08320e03010c05060606060e0e0e0e0e0e0e0e0e0e0e0e0e050e060e05140e060e020d0405857787100f060e060e010e050e0404060e05140223357e1d40361a4d3077766567091d58464e62460104060f01050505011404010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401010101011405020e0104140e14030c0b1b32218d6586415d84136e8504050e0e0e0e0514140e14141414140101010505141414140505010e010f0614057d15257b010e04010f0e0e0e0e0e050505050505050505050514050e051414050e140f030c140f8495910f0e050f0101050604060e051414140601140605010c2b27324267544876444d4a54ae67390d01010e05050103011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101051401010114050e010406060105060350a16765672d891350350d040e14140e01140e0e1401011414141414141401011414010101011414010e01060514142c09bf777b0f050514050505141414141401010101010101010101141401010114140e140505066eb9760104140c01010501010101010101010101010114050e04140f391027464084169e9edb9d33030e0d020501140f0104010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e05141414050e06020f0f14066126996824184658610d060101140e05010114140514010101011401010101010101140101010101010101010e0105010504063938607735010b14050514141414010101010101010101010101010101010101140101010e05618ca0050c01050101141414141414141401050e1401010514011414050e04040c617b181190ca62533c010123050c010c14010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505141414050e06010504043c1a3872368b390f0f0c05010306010101141401010101140505140114141401010101010101010101010101010e14140114060503172a9f116101060e0e05050514141401010101010101010101010101010101010114010501231fa47804010101140101010101141414010101011401010105140e0505060e0504142c6e1375559c1e1861010204060401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141405050e0e230a463a417c8b1b850f05051401140f0101060c0601011404060501010101010101141414140505010101010101010101011414141414050e0f6e15ad5385140e0e0e0505051414010101010101010114140101010114141401051414010c5695880501050105011401010101010101010114060501010e0505010114050c0c0d04010c37072f658e48170b060f01030101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114140505050a7247861c1b2c0c0e0606050e060f03030501140501010e010101010105050101010101010101010101010101010101140101010e05010606010d8b159d2f0d0e0e050514141414010101010101010114010101010101140601011414050178638c010e0c010e0101010101140505051414010101050501050101040e01140504040d0e3d5c27614bc8bf7b030c0d0101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010105041401011018776d4f2c140103060e0505140505051414050e0e0514140101010101010101010101010101010101010101010101010101010114141414050d050b463e52712c010c0101040105010114010101010101010101010101010101011406140c2029a50d0f0e010514010101010101010101010101010114140e05040414060c140e0c0104441664040b099360580f0f0f010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010501011401052ca1986c39030c0d01050e0e051414140505011414050514140101010101010101010101010101010101010101010101010101010101141414140604060e0e1c2990840a060401140114011414010114140101010101010101011401011405010e0b68bc460105010601010101010101010101010101010114140101050e140e041404012c61765d8505012c6cbb510f0601140101010114140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010501051405052b301f5a0414040114050505141401141414010101010101010101010101010101010101010101010101010101010101010101010101141414140e01040c01044c6f555f2c0c0e140e14050e0e050e0e050101010101010101010501011414140e05338344050f010101010101010101010101010101010114140101140e05060c0e0f010f6431560d05050e236545230f041414010114141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010e140e051029882b02040b0106010514140101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140c010e1404060d2696b468200f060106050e060404040e14010101010101010114010105010e0c0520625b02050101050101010101010101010101010101141401140e0606040c04010c0d073e1b03060f01614612890201051414011414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010501140114051729480b010e0f0f0e0514141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101060401050c040d8b3197305006040e050e040f0c060514010101010101010101011405010e03046153d078010401010101010101010101010101010101141405050e06040406040f0c329b182c04040c0c0b84904c04010e051414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141405011405235dbf530a060405011414141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e04060e04050c92826048502c06060606040c060501010101010101010101011414011404040f13698d010401010101010101010101010101010101141401051405040e14060c7b28486e03050c040f105c1e0f040d0e05141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101050e05140514783e3d58010e050c061414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010c050114060e050e0b04103a4331840a230f0e060c040e14010101010101010101140101141405040c78a3290101010401010101010101010101010101011414011401050c06050c0a655b6c39011403030699526c0e0f0e0e051401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e14010106010b5009a13c0b0f0101051414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e0305040c010c0e060e06ba155b86100b0e0e0f030405010101010101010101140101060501040e1b4e97200e0101010101010101010101010101010114140105140e030c0602538007350f05020f037c9a2839040e0c0e0514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505050101050403022b566b483b0b0104010501010e0106010101010101010e05141401010101140101010101010101010101010101010101010101010101010606060606060606010e0f04038b3d8e2a78850205010605050105010e011401010101011414050501393a633b0101010101010101010101010114141414010101010e0506040fba2e76590e0c0b05052ca51e641414010614140101010101010101010101010101010101010101010101010101010101010101011414141405010c01011401060101050e01010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414010114060c0f0e026d96905ea12c0514040f05010e010f0e0105010105010101010114141401010101010101010101010101010101010101010101010105050505050505050501010501057e2d433e6d040e0406010e14010e01010e010101010114140505040c099a070c01010101010101010101051401010114050e0106010c01015981987d06010c011461529710040c140d01141401010101010101010101010101010101010101010101010101010101010101141414140506040c010603041401140e05140e041401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101050e060405057b422e7f8c662c0f03060c060501040f14020b010f040e0505140101010101010101010101010101010101010101010101010101010101010101010101050e050101140d1cb890543f0f050203040102020c1401010101011414050505017dae290b0605050514010101141401010105060406060e01050e016e169d4c0e06010e0d847fb9400c14040e0e01141401010101010101010101010101010101010101010101010101010101010101050501010e0c0c06040d060402060c020d0e01010e14010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010101140501010f0d0c01358982b9ad7a500f0406030e050605010502060e051401011414141414141414141401010101010101010101010101010101010101010101010101011401010e0e010c3f47bd1e490f0e0c2c050104030106010101011414050501141b3d577b0f0e060e0514011414141401010e0c0f0f0f0e05060f5a2945721006040f2028d04527010e850106050514140101010101010101010101010101010101010101010101010101010101010114140114040f06010d0f040b03060d0d0f020f0101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141414140505060f060101060c0435503863bb8109200d0c04040e140603050e060e051414051414141414141414010101010101010101010101010101011414141414141414050101141401010603022b0997085f0f140c5a0e1404010e0101011414050505050c02218e7c1414060e051414141405061401140e060f020c040e0b77ae2d2c0103026e45692d2714640e0101040e061414010101010101010101010101010101010101010101010101010101010101010101010e030c1423040c206b69c6ae1f27010e14010e0101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141405050e0e14050105050f0e01010f2c612619b5742e5f352c0f0f0f01050e06060e140101010101010101010101010101010101010101010101010101010101010101010105050e051401140e04010d037e81d04d3c010c0203040501010101141405050e0e030e6ca334010e0e0e05141414141414011406060e0e041461649c8756010e045836a0c2918501010101010d02010114140101010101010101010101010101010101010101010101010101010101010101010106030c14145a5db5da2512515b212c0401010601010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114011414050e0e060e0105230c14140d050f060c0b141788bbd8d912704f2323020c0e141405051401010101010101010101010101010101010101010101010101010101010101010114050101050e01050e010c0f611f8a5cba030406010514141414141405050e140f061c312e35040505051414141401010114040c0e060f0d6c9dae840f030f5098c9317b0f0c0e0201010d01010e0f1414010101010101010101010101010101010101010101010101010101010101140101140e0606055a19ac576403020e5a04050e01010e01010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e06060c0c1405010f04140c010c020e0c061402277245bcbb258dba59200d040e140101010101010101010101010101010101010101010101010101010101010101010601011414010106140f0c0e140f0b1cb82e661b03141401141414141405050e140606581f975601140505141414010105010101140e0d2b7297347b0c61508d63ad990b010e0e0d01060f010f01060114140101010101010101010101010101010101010101010101010101010101010101011401011406060d0f1405140106020c0e0e0e010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414050e040c0f0f1414141414141414010e0605855628ada59d296749785a0d0c0514050e14010114050101050501010114060501010e1401010105051401010114050101140e0e050e0e0514140e04854d6319462c0c05010101050e01010e0514040f189d07040504010c0e061401010e1401035a6c12c28d5a395930a29448270c050c0c017d0406061405060601141401010101010101010101010101010101010101010101010101010101010101010101141414050604060e060c040e01010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414050e0e0e14141414141414140101010101140c0d3f4a25af5b224d424c8b170a0d0c040f140e0e0e060e0506040e050505040f0c0e060e0101010e0601010101011405141414051401140e060c012645457c580f060504040101140505050f062645770a060c010e0e030f02050102235041579b701d33249c7931330201060d01012cb47d010c040f011405141401010101010101010101010101010101010101010101010101010101010101010101141414141414140114050501010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141414141414140101010506040e1401010c1b897a31576f96488871ba275a39850f0e0404060c0e1414050e06040406040406040f0601060e14010114010101011401010105060e04032088435c2639040506060f0f1414050f0c64349b33010203051403061403027b4a318047a1a11143975e262306060c05040e0f2ba59e0b05040c0e0105141401010101010101010101010101010101010101010101010101010101010101010101010114140101010101141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140e0606060c060101237e5f54473e98731630652f915927178503030f040c0f0605060e05050e0c030f0c0e0514050e060e140101140101140e06030d0c050d7e1f7365262c060f0414140c05140f614a907106030305010e042c4f727362512f99349c9734ba850c04060401061406010599d789010f010c010e1414010101010101010101010101010101010101010101010101010101010101141414010101010101140101011414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114040f040505060e140e0c0c05042c395a7836654812153e734744535f923f200a85031414060e060c04050e0f0101010101010101010101011414050e140e02010f05858448342f272c0f1414020c010e856c9b240b01010e040d6e897345373488485757671c2c010e0601010101030202010c0ad3450a0d0105050614140101010101010101010101010101010101010101010101010101010101011414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101061401010e04040604060e14140506060405040b0d040e0fba6c2f7a73222e8c4315414a1a49597b202c060e06060e0e051414141414140e010101010114050505020c0f0c0e0b01353a1e2a3610060301031414038b9698580e03233f7c3e52af513d255722677b2b06040c01010503040601140e05030660d6260c0e010c0514140101010101010101010101010101010101010101010101010101010101011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101050e0e06040405050c0414140c0d06140604051404010e0e0f5a504971727a8222436298821f53361d7e27358504050101010101010101010114140505030414050f0f010f042cba869888330b0504040e0e175c9c912b505f299f7980969e9e48185923020201060614040e05140d1404040e0f0b3ab32a0c0605040114140101010101010101010101010101010101010101010101010101010101010101010101010101010101141401011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140101060601010e0501140e0e0114040c0e010e06060c06050e0410277ebaa12d885e22451229515c7aa1268b7b35230c050505140505050e0e0506050d0e0e04021401026e2d4e9866100e0106046124af111f80bdae97aea345821a170f06010604140f0e0104060c050f04011414050e7bc0b40a050c0114141401010101010101010101010101010101010101010101010101010101010101010101010101010101011401010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414141414141414141414141414141414141414141414141401011406030b583f1309673419805b4319514407426649267d4f5a230c010501040501050e010404010485595445441d02040d925c69d5a3089060a543387d350c0606050105010e0606050506040e0514141414010105a0bc09010d14141401010101010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141414141414141414141414141414141414141414141414140101140e060105060f0b391b7bba365341736f5b08b48c1e5e115472703b403335230b0e01010e040e0506175619286766074379ca93b4afa04507462c04010101050e0e05060e050e06060501060501010114050e0141d457590f050f0505141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140101010101060604060e0e040f040c03020b39277da118075e259e909d5baf9e983486538d2d40925a030f010e176bc4cad294af95d369222d10020301060c0f0c040605140e05050e0606051405140101010101141433b0bd3d040e01010114141414010101011414141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114140101010101011405050e0604020c060406050506050505050e2c7b6d2f41968cafa0a0a2bf906f7377412d713a80d1acc4a2c6beb05c200f0c0f0f0f0404060514050604140e0404050101140e0e0e0e0e0e05140523b4c2bd6c0301141414141414010101141414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050514141414141414050101010c020414010405050e0e1405040114030c0f1b1d91187673199d80aec9cdcecb95c7cfc5d075b8713250176185390b0f0c06050e0414140e06061405060101011405051401060e34c663220f0e0e0e0514141401010101141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050505050505050e060f040e05140114051401010e0f0f06050f140f0401060c05050c140f2b8997abcbcabf439b9c9f75ccb255221e3d866b2f71a14c13781b58645a8585850d061406051414141414140e0c6c9a638e9206010101010114050e011414141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101050514010101010101050e0e050e06041405050101141401140106061414010506041726538cc8c99c4b1d2c2320462d90caa21531281525819631815148345e1f5d4b7c1870566d59786e1b102b0a8506025a1e7fbf1f0c040e051414140e061414141414141414010101010101010101010101010101011414141414141414010101010101010101010101010101011414141414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414010101011414141414141414050e0514140e050114051401010604061406040114140458594b1125c6c757424f050306010f010d18adae3066351b591c367c428d685c475c44413d5e828277475e5c41658f4b1a1826403d7f6008335a612c0f0e1414011414050505051414141414141414141401010101010101011414141414141414010101010101010101010101010101010e0e0e0e0e0e0e0e0505050505050505010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010106010106050601140e0406010e0f0f0c0514060b271a3d4e9b5b19a752ba0e04030f06050505067837a2477399200f0e040e0504060220174f501d2689662d8f24541f1f6b3d5e8177291969c69351992d1a8449406d7d7d591b352c0e0c140e0505051405050501140e0e05140e0405051414140e0604050e0e0e051414140514140101010101050401040f06030e140201050606140406051401010101010101010101010114010101010101010114141414141401010101010101010101010101010101010101010101010101010101010101141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140614060e0101010e0601010404140e0c062c4665195b9c2988916d8096500e0e01060f05050c045a2a973a88773a27610205050c0f0601040406051414141406020a201b3f7dba09705307afbb79a05c5d4d1f1148116b88684a4b72214c4c46926e5a0b0c060e06060e05141405051405050e060e1401060e05050e0e1401050514010105040f050f0c0c0c040f0405060c2b2c0d0306051401010101010101011414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010414010e0501040604230c01040f060f58093443bf803d667b2b0261388c4d496114050f14050306031a9c677ea188671335030414010e0c040e0514050505140e06060e1414050e060c61106baf51608450ba3389264c56707c380788113d1f54075d727c5f717166a1267d7b172b850f0405140e04030206040404060e051414050606050101010e060414060f060c0b8b6ca14f0f040d060e0514141401010114141414140101010101010101010101010101010101141414141414141414010101010101010114141414141414140101010101010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140114040e0101010e0606060e0b1d538157af22889135040c0106061b68129b5c721c1b0d040c0e023b5b160a0d3c09882d92030e03040105141414050e0e0e0e0606060e0e0e06040e14042f5b68b42f0b01042c2b3b84565a203f781d32336d49097c5307547a6b548838675d5d5d538d425f6c6d1d8b7b352c040e06060e0f0c0c04060e051406140405040c0e0a913d3d211b030e0205140101011414140514010114141414141414141414141401010101011414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010504050101050e01040f614c77af9d9e5e5f3f0d050603040c03050d4f3b303e196f15475d13350f469b9c78050c6432243a92230306140f0f0406140101140303030f0605050e0e04140f496f7a221161030c7812a5bf62677e0e0106140f0c0d0a20586464647e9246263b707c42535d3a675d67246830244a4a6738534b913364230c0501010e01050e0605028b16732d35060d04010f040514010101011401010114141401141414141414141414141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101040e0101140614040c0c923d609f2238463504060f04050501060c010e0f0d392770883425124e3112257fbb09850c040b1c2f993610010c0604040605050e060114050514050e04140614037e7734414392036144c5193d513e766c850e060c01010101140e04040504030d0d2c0a39103535351b7e1c89367018728f67678f073867673a3a5d8f3b3c5a0b03061766987085020101040d14010101011405050101011405140101010101010101010101010101010101011414141414141414010101010101010101010101010101011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101051401010f0e01034f4d609f122f4f2305010504061414040c01140401060e0101020b0a78569930165e97c48357293832586470544b27060c040e05050505050f040e05050505140e05140c2b684721a03a352494905861643b482e67100e0e0f0c040e05051414011405051414050e0101140505060c03030b2c0b022c5a64787d40095f181a5f717c4b076b5429bd5b2a07342f580e140c04060e0e0514010c06140114051401141414141414141401011414140505050505050505050505141414141414141414141414141414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101050604050404390997949e6627230114050514011405140101040101010601141401010e040f030f231b66bf9f4d7a3e573777678d6b2f35030405050e0e0501040501011405140101010c032c9973264e60169a79260e140e2340292e66850c0e040f02030c0e1414010101141414140505140506040406060e1405060614010e040c040604022c353c8b6d6c4a9dc3c3b2a49ab8347a542a3007728426923f85020e01010506040505050505050505141405050e060604060606060606060605050505050505050e0e0e0e0e0e0e0e14140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010101140101140f0e1402024015c19d38782c0e0f020114141414141414140101010101010101010101141405050505027e379c200a8b218d48b8909c22416d030e0d060e051406040114060101010106010c0b49516618c2ad8c56020c01040c0b49229e2f5a0c06030206050e0514141414011414140501010f01141404050404010e010c0e050605010505011405050604065a717315168f704c494c3b6c6c6c36664b533a5d3a076868672d70325010230b0d0304020f04060404060e0e0e0e05050e060614050e0e0505050e0e06040614140e0c05051414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050c040e0f7087c0316d2b0e0e05060c01010101010101010101010101010101010101141405050505140123348e890e04042c586d3daeb943966c2b0f060f0b0f050f140101010e14140e050c0f7e983a85714b92030e04140e040d0d6d96378f7b8506040f040e1405140101010101010101011414020214060a0e0e060f050505060501010114040e0504030c042c35208503040e05050e050e04020a356e78923313ba1c4089097c2d728f2424678d709150172b2b2b2b0b0b0b0d03040501010101140e06060e01140e0404040e0e0505141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140101011405140f0d71a3ae68170c0101040605140101010101010101010101010101010101010114141405050503050171bf68010e05060c06354c826345821a35060102010105011414011401140105040f61475e612c3914010c040c06050c0e0346309e28400f04040101140e140101011401010105010114030b01040f852727100e0f0c040c030c14010e0506040606060e1405050e060e141405141405050e040c0c0105060501050323201b3f3c8b92597e4691091a7c4b728d2d841c785835176435390d0f0f0c06050e05050e0c0c06050505141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114140101141401140532128053200b0f0c0606040e14140101010101010101010101010101010101010114141405050506041c55310c0304050c03020e4f516f2d30714f0b140b0205010e0e01010105010105030265155906010f0c0306040f0f1405030558885b6f4b850e0c0514140101141414141414011405060e03030b4f07152588460a0f061401010c0c05010e0c040e060404050e060c040e06040e0e0e0e05050505060e0e0e0e0e051414050e0e06040c030c0a647b4f581b6e783c324c362166715f3b7d6e173517172b85030406060e0505051414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141401010f3c989c4b170605140e050504041401010101010101010101010101010101010101141414050505010e067e5bae64060e0e0e010c0f014a253b6d1a5d1c0a0f0b06060e0101140514141401060c3b373b0f050e140f0e050f060614050c030a5f81903058010e050101140e05010105010114060f060e2b70909a371e9e525c6c270d0c0614140e0505050e0606050101010e0c0c0e140105050e0e0e0e06060605010105060e050101011414050e0e040e1401140e040c0e0403232b176e7b6e50325636a1562692506e35392c020f050514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050514140e0a38455d10140e0414060404040501010101010101010101141414141414141414140505050e0e0e0c05010a48bd09050d0c0e05060d8524154961107c676d2001010c0e010e0614010e0e01140f5062880b0214040c010604010c0c010e0605037d28ae16320a011401050e0e010105011405010e04017e63941f136e91819da34e993f0e140405041401140e06040f0605050606050101050514010101010114050e0e1401011406060e0e0e05010101010101140514140e0e06060e0514010e0c0d85612b5a204f6e274f35610d0f0514140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401140e1401060b2698473c0c0c0b0101011405140114050101010101010101141414141414141414140505050e0e0e0e1404024bbd4105010e0304146454082a27010d2b1c245f20040c04050c061401050e0514043577163901140c1401060e0e14010e0c0105042c5968802e2d6403060506040e1405050e0e01010f048dbe736105040c6e4c65b47f29500105140105040405010101010101010101050e010114140505050501010e060e14050e010101010101140514050605010101010101010114050505141414010101140e0504030d030405010514140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401140e01010c2b1f6f3603020601060e010114050505011414141414141414050505050505050501010114140505050c060c0ebaad19020e0c060e8b5cbd1e46230405140a7d5d2d1b0f0f0c0c06030e14010501010d4a1eba010601050f01050101010101010204010d6e211257670a030e060f0c060e04140e04010d2037bb400d0114030502206d5108167b010406040e01011405011401140514010101011405051401010105140101010101140101141401011414140101011405010105051401010114140e05051401141405140101011414050505141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050e061401061d9648270e01060c0101010101010101010101010101141414140501011414011401140501140e0605010b010417b8a58b0104235f8079296d0a0601060c0c5a3c2488640a0e0e0f0306010c010606056d9e8405140106040e010114010114050e0c0e0e0e2391345b8d610f0506030614010b01010c0c6dbc37640414048514060c027b7a432a7b04060e14010101011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010514141414024c768d5a0c0405010e01010101010101010101010101141414050e14140e05010101050501010404140401040d0441699985715779962d8b0402060e060e14040b324165612c06040314140e050c05057e808f020101010105010114010114050e14140c0d040446865551920c0b010c060601030601101ea4710e06140106010305010d6e1a372e84020f06050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e010114053999446d0c0e030c010601010101010101010101010101141414050605140514010e0c0101040401010601140c140e72bb8057799c998b2b0c020404060514050505061d3d1a0b050e011405010e0c01062b734d3906040e0505010114010114050e040401050f140c270752632f850d0c0103010e0e1466b59e6e140b0f0604050503010c030a4c7c330b020c0e1401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010601140505643d487e06040f020e050101010101010101010101010114141401050e060c060e0401040c14050f0c010e025813447fb6b7451a925802050f0405060e050e0c0c060d0d4f5d562c0b060e0514050514042c1fb83f140505050e010114010101140505020401040306062c5619b94eba0a010e020c05201e951802140c060f0f0c0105060501040b23230f0c06050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010514060e053f984e500f0b0604061401010101010101010101010101141414010e040f0f0e0106060e04857ba144379ab1b2b3a4627fa38b0c06010404050414140e040c040e0114060f6e77560e0306010c140104060b70633b0e0106050101011414010114050e0e0406050e0e140c03617cb46962910314010c1fb5627814140e140e0104060e0c0c14140d0b140e0e0514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011404060e4f2a454906040e0606050101010101010101010101010114141406010da16f93a6a7a8a9aaababac74ad31389164610b86ae890e0c0e0e051405050101050e051414010c1406337a7d8505140305140f0e037baf3a2c06020401010114140101141404010105140e0e0101010e85054657b062183f3a75ae32010f0406140e011414010101060501050e050514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011405060e06233625246105060c060601010101010101010101010101141414040e062b7d897c534299997c5650355a0301030502064a8a8f011404011406010514010101140e060e01050685565c64050406050606050e641e7361140e0e0e0101141401010114010e0614011405050c0606010302237a9a6aa4a562400a0c0c01010106010501060c0101060e0114051414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e0e05050e01584d73322c030f05040101010101010101010101010101141401140e0c03040e0f06060e0514141405040c230b0e04a1a35c03040c1401010e01140e05050e140101050201060318760e0d010106010e015a07454f0f0514010101141401010114010e0101061401060104010e010e060b2b8b3a712b140404030504050e011401010e0501010505050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505050505050e06a12e983f01020c010e01010101010101050114050114060514050e0e0e0e05050e0e0e0e0e0e06060404010506066e9e9b7e1401050e01010101010114141414050e140e140e05767c01030f0e0d01140f91a2a103050605141414141401010101010101010101010505141414050e06050c0c0514040c0e141414141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505050505050e06616b37246e0d03060114040e01050e010501050e011406141405050e0e0e05050505050505050e0e010e010614015a28a0700e01011405010101010101141414010405041406018b9c58010f01010601016e454d0a0501141414141414010101010101010101010114141414050e060605040405140e0e050e05051414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050e0e05050e0e010a6b573864230e0301140601010e011401140e14050e1405050505050505051414141405050e0e05050106050e0d2f9f41020501010e010101010101010101010c050c14040601713d20010101050d0e231f8c850514061414141414010101010101010101010101010101140e06040e0604040e050e06060e0e051401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050e050505050e0e35659e6b350e010f06010604140e010101011405051405050505050505050505050505050e0e061401060e0c06504325350e140105141414010101010101060e050f0e060b140a2e1005050306010e0f70601b0c0f01141414141401010101010101010101010101010114050e0614140e0406140e0c0e0e0505140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011405050505050501040310535b15402b05140f1401140f05140101141414140505051414140505141414141405050e0101060405060e395e9046050606050614141414141401011401060f0c05050503414b85140101040e043255260e0b011414141414010101010101010101010101010101141405050101010e0501010e1414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141414050e06030403912855077e0f0d060e0f14040405140e0501140505051414141414010101010101141414010506040d030d5d9d2d06050f140e05050514141401010101040e0c0c011401919d040b0b010c05057b1965010f041414141414010101010101010101010114010101010101011401010514010114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414060c050e0d856e4962222d61020d1405060c0e0e0c0601141414050505141401010101010101141406010105032c020c499b765a05040114050505141401010114050e050e0f0e060c358a0205050e0c0101205c9c0b011414141414140101010101010101010101010101010101010105140101140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101140f14040c01140d0d582f6f225f6485040106050e030c011414140505051401010101010101141414010105050e0606067b31377d0605141405051414010101010114010f14140e0e0458800d060e0b0e03060d999a1b140514141414140101010101010101010101010101010101010114010101011414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e141405060c04040e0d503008575d1d61050d06050e0e010106050114140114010501010501011414010101010106031b28797106141401141414050514010105010e0105060102036898020e0c010c04010532086c040114141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010e050e060c0f0c062c0603050c18977f8256100f230e01050c14140e05050e010505010101011406051414050505040f502895440a010e010101010101140514010c14020f01060323875003060605010501145896340c0414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010505060c0f0f040e01040e050c06208915903766493c0a2304010104010105010e050e060505140101010101140e0606922995814f010c0c010514010114051404011401010e0b239681140d010e0106010404856b63020414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414050604060514040614050c05140c0c35815b90254a91331b23030c0c0e010101011401050414010e0e0505040c04922e9380490c0101010e0e0505051401051404060c010b5f941b2c040104010c010c0f2c429378061414141414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414140505140101010e0e01040b010c0104593b8d5b3711414b137b5a02020c050e040e040f050604060505040c060a448e081a2c040c0e06060e0505141414010103854f421618010401060401140506140c56798f0614141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414010101141414040e01010c01010c14040c06040517265d474e3e4788896d6e2085020406061406060e05050604063971578a652c060e030c0606050504020f85588b6641866c01060114010e0105010e0114598c2501141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141401010114140101050e1401140505010e0601040406145a3c8441735151767a4b4c337b102b230b02030f060505046448753e4f060e0e0105032c853578132d7a41512420040f010e0c0114010401060e0539868723141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010101010e01011414050501010314051404010c0d020404237b4c7c7a4d443d5c1f425f5f706c566d7d7e3f503c4d7f80561b78595046364b537a3481298254130c04060104010503010e011406040e142d8313141414140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141414141414141405050e06040c0f0c0c0406060e0e0258136c70712d724173251529284844114d317475766b4d7329152e622577415336782c0606040c06051401010101010501040f0140797a06140601060101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414141414141405050505050e0606040406060e0e0505140e0606040d5a4f135609096642673a686534696a3e1f30116b5d1a6c6d5964020f0c0c0303040e0e051401010101010104140e056e6f190e060614050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010105051414141405050e050505051414140e0e05050e0e0e0e010e0e05060d615a5a391d62634a5864106102060e06060501011414050e06060514010101141401010c0114050b656039040e140105011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414140101010114141414141414140101011405050101051414050514010106010b5d5e321404060e0514140514010e0e0e051401011414010101011414140114050e01035f6056050e011406011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010514010101141414140101010505050e04060658595a0c0e1401010101010114010101141414140101010101011414140101060e010d265b5c040e0106060501010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401011414010101010e1401011401010501010e0604040614040e14141414140506050101010114050101010101141401010e0e14010c4f3157350e01040e0e01010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401010101010101010101010114140101010105060114050e051414050e010e040c040502140101140505141401010114140101010101010101140101010614140505235455560e05061406140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141414140101010101010101010101010114141401010114050e0501011405011405010c0c01060c05010114010101011405140101010101010101010101010514140614050b0952530e0605010414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050e0e0e0e14141414010101010101010101010101010101010101010101010401140504501e510c06041414010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050e0e0e0e0e14141414010101010101010101010101010101010101010101010e010505051b4d4e4f0f0e061401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114050505051414141414010101010101010101010101010101010101010101011401050e012c4b154c03060c0105010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141401010101010101010101010101010101010101010101010101140e0106491e4a020c040105010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011401010501053c47482b0605011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140101140505174445461405060101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141401010101010101010101010101010101010101010101010101010101011401011401010514234243420e040501010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114010105140105010240124102030114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140e3c3d3e3f141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140e393a123b050114010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140614363738031401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414140614332934350e010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141405041b30313206010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141401042c2d2e2f0f0e01010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414140e0e1c222a2b0e01010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414050e1427282927140101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141401020520242526050605140106011401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010114141401010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101011414141401010103212218230e0505010e01050101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141414140c01040c1d1e1f200e140414050105010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010104041b16151c04010c0514010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101141401011414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141402020c060c18191a0b01040e0e011401141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141401010114051401010e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0505050f060c041315161701060604060e0e06060606060606060e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e0e060605010f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0c0c0c04040c0d030203101112130f0c0c02030c0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0c0f0f0c0614020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202030303030405050106030708090a02030b020c0302020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020d0202020d020c0e040000002701ffff030000000000}";
		$doc_buffer = preg_replace("/<img(.*?)>/i", $assinatura, $doc_buffer); 
		//$image3 = " ".($imgf=='r'||$imgf=='l'||$imgf=='c'||$imgf=='j'?"\q".$imgf:"")." {\\pict\\wmetafile8\\picw5292\\pich1588\\picwgoal4320\\pichgoal2100 " . bin2hex(@file_get_contents(trim($image2))) . "} ".($imgf=='r'||$imgf=='l'||$imgf=='c'||$imgf=='j'?"\\q0":"")." \\par";
		
		$doc_buffer = $this->nl2par($doc_buffer);
		
		return $doc_buffer;
	}
}

?>