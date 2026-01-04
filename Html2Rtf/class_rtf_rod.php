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
	
		if(isset($_POST['ger_rtf']))
		{
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
		
			include("seguranca.php");
			include("functions.php");
			protegePagina();

			$dir_cont = $_POST['url_dir'];
			$tipo_id = $_POST['tipo_id'];
			$nomtipo = fc_select_name('tipo_id',$tipo_id,'tipo_nome','tp_tipo_tb',$conexao1);
			$nomtipo = limita_caracteres($nomtipo,20,false);
			 
			$nomecli = ereg_replace("[^a-zA-Z0-9_]", "", strtr($_POST['nomecli'], "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));
			$nomtipo = ereg_replace("[^a-zA-Z0-9_]", "", strtr($nomtipo, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ= ", "aaaaeeiooouucAAAAEEIOOOUUC-_"));

			//$query_doc = mysql_query("INSERT INTO documentos_tb SET st=1, pasta='', dossie='', subpasta='Corpo Principal', tpdoc='CONTESTAÃƒÆ’Ã¢â‚¬Â¡ÃƒÆ’Ã†â€™O', url='" . $dir_cont . '/DEFESA_' . $nomecli . '_' . date('YmdHis') . '.rtf' . "', arquivo='" . 'DEFESA_' . $nomecli . '_' . date('YmdHis') . '.rtf' . "', data_hora_st1='" . date('Y-m-d H:i:s') . "', data_hora_st2='" . date('Y-m-d H:i:s') . "', data_hora_st3='" . date('Y-m-d H:i:s') . "' ");
			//if(!file_exists($dir_cont))
			//{
			//	mkdir("$dir_cont", 0755);
			//}
			//
			//$fopen = fopen("$dir_cont/" . $nomtipo . "-" . $nomecli . "_" . date('YmdHis') . ".rtf", "w");
			//fwrite($fopen,$this->buffer);
			//fclose($fopen);
		
			header("Content-Type: text/enriched\n");
			header("Content-Disposition: attachment; filename=$nomtipo-$nomecli.rtf");
			echo $this->buffer;
		}
		else
		{
			$this->buffer  = " ";
			$this->buffer .= $this->parseDocument();
			return $this->buffer;
		}
	}
	
	// Header
	function getHeader() {
		$header_buffer  = "\\rtf{$this->rtf_version}\\ansi\\deff0\\deftab{$this->tab_width} \\paperw11906\\paperh16838\\margl1701\\margr1134\\margt1701\\margb1134\\gutter0\\ltrsect \n";
		$header_buffer .= "{\headerr " . $_POST['cod_cabec'] . "}";
		return $header_buffer;
	}
	function getFooter() {
		$footer_buffer  = "\\ltrpar \\sectd \\ltrsect\\psz6\\sbknone\\linex0\\headery539\\footery0\\colsx708\\endnhere\\pgbrdropt32\\sectlinegrid360\\sectdefaultcl\\sectrsid609040\\sftnbj \n";
		$footer_buffer .= "{\\footerr {". $_POST['cod_rodap'] . "}\\par \\pard{\\qr {\\field {\\fldinst{\\f23  PAGE }}}\\par\\q0 \\par}}";
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
		
		$doc_buffer = str_replace("&aacute;","\\'e1", $doc_buffer);
		$doc_buffer = str_replace("&agrave;","\\'e0", $doc_buffer);
		$doc_buffer = str_replace("&atilde;","\\'e3", $doc_buffer);
		$doc_buffer = str_replace("&acirc;","\\'e2",  $doc_buffer);
		$doc_buffer = str_replace("&aring;","\\'e5", $doc_buffer);
		$doc_buffer = str_replace("&eacute;","\\'e9", $doc_buffer);
		$doc_buffer = str_replace("&ecirc;","\\'ea", $doc_buffer);
		$doc_buffer = str_replace("&iacute;","\\'ed", $doc_buffer);
		$doc_buffer = str_replace("&oacute;","\\'f3", $doc_buffer);
		$doc_buffer = str_replace("&otilde;","\\'f5", $doc_buffer);
		$doc_buffer = str_replace("&ocirc;","\\'f4", $doc_buffer);
		$doc_buffer = str_replace("&uacute;","\\'fa", $doc_buffer);
		
		$doc_buffer = str_replace("&Aacute;","\\'c1", $doc_buffer);
		$doc_buffer = str_replace("&Agrave;","\\'c0", $doc_buffer);
		$doc_buffer = str_replace("&Atilde;","\\'c3", $doc_buffer);
		$doc_buffer = str_replace("&Acirc;","\\'c2", $doc_buffer);
		$doc_buffer = str_replace("&Aring;","\\'c5", $doc_buffer);
		$doc_buffer = str_replace("&Eacute;","\\'c9", $doc_buffer);
		$doc_buffer = str_replace("&Ecirc;","\\'ca", $doc_buffer);
		$doc_buffer = str_replace("&Iacute;","\\'cd", $doc_buffer);
		$doc_buffer = str_replace("&Oacute;","\\'d3", $doc_buffer);
		$doc_buffer = str_replace("&Otilde;","\\'d5", $doc_buffer);
		$doc_buffer = str_replace("&Ocirc;","\\'d4", $doc_buffer);
		$doc_buffer = str_replace("&Uacute;","\\'da", $doc_buffer);
		
		$doc_buffer = str_replace("&ccedil;","\\'e7", $doc_buffer); 
		$doc_buffer = str_replace("&Ccedil;","\\'c7", $doc_buffer);
		
		$doc_buffer = str_replace("&deg;","\\'ba", $doc_buffer);
		$doc_buffer = str_replace("&sect;","§", $doc_buffer);
		$doc_buffer = str_replace("&ordm;","º", $doc_buffer);
		$doc_buffer = str_replace("&uuml;","\\'fc", $doc_buffer);
		$doc_buffer = str_replace("&Uuml;","\\'dc", $doc_buffer);
		$doc_buffer = str_replace("&ndash;","—", $doc_buffer);
		$doc_buffer = str_replace("&ldquo;","''", $doc_buffer);
		$doc_buffer = str_replace("&rdquo;","''", $doc_buffer);
		$doc_buffer = str_replace("&quot;","''", $doc_buffer);
		$doc_buffer = str_replace("&ordf;","\\'aa", $doc_buffer);
		$doc_buffer = str_replace("&frasl;","/", $doc_buffer);
		$doc_buffer = str_replace("&lt;","<", $doc_buffer);
		$doc_buffer = str_replace("&gt;",">", $doc_buffer);
		
		//$doc_buffer = preg_replace("/<P>(.*?)<\/P>/mi", "\\1\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<P>(.*?)<\/P>/mi", "\\ql \\1\\ql0 \\par", $doc_buffer);
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
		$doc_buffer = preg_replace("/<p style=\"text-align: center;\">(.*?)<\/p>/mi","\\qc\\1\\qc0\\par", $doc_buffer);
		
		
		
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
		
		//Sem ÃƒÆ’Ã‚Â¡spas
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
		
		$doc_buffer = preg_replace("/<FONT face=\"times new roman\">(.*?)<\/FONT>/mi", "\\f0 \\1", $doc_buffer);
		
		//--Tabelas-------------------------------------------------------
		//$doc_buffer = str_replace("<TABLE border=1 cellSpacing=0 borderColor=#000000 cellPadding=2 width=500>","{", $doc_buffer);
		//$doc_buffer = str_replace("<TABLE>","{", $doc_buffer);
		//$doc_buffer = str_replace("</TABLE>","}", $doc_buffer);
		//$doc_buffer = str_replace("<TBODY>","", $doc_buffer);
		//$doc_buffer = str_replace("</TBODY>","", $doc_buffer);
		//$doc_buffer = str_replace("<tbody>","", $doc_buffer);
		//$doc_buffer = str_replace("</tbody>","", $doc_buffer);
		
		//$doc_buffer = str_replace("<tr height=30>","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh450\\trleft-70 ", $doc_buffer);
		//$doc_buffer = str_replace("</tr>","\\row}", $doc_buffer);
		//$doc_buffer = str_replace("<TR height=30>","{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh450\\trleft-70 ", $doc_buffer);
		//$doc_buffer = str_replace("</TR>","\\row}", $doc_buffer);
		
		//$doc_buffer = preg_replace("/<TD align=left>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		//$doc_buffer = preg_replace("/<TD align=\"left\">(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		//$doc_buffer = preg_replace("/<TD align=middle>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		//$doc_buffer = preg_replace("/<TD align=\"middle\">(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		//$doc_buffer = preg_replace("/<TD>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\q0 ", $doc_buffer);
		//$doc_buffer = preg_replace("/<TD colSpan=5 align=left>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\q0 ", $doc_buffer);
		//$doc_buffer = preg_replace("/<TD colSpan=\"5 align=left>(.*?)<\/TD>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clftsWidth3\\clwWidth1500\\cellx\\ql0 \\q0 ", $doc_buffer);
		
		
		$doc_buffer = preg_replace("/<table border=\"(.*?)\" cellpadding=\"(.*?)\" cellspacing=\"(.*?)\" style=\"width\: (.*?)px\;\">(.*?)<\/table>/mi","{\\5 }", $doc_buffer);
		$doc_buffer = preg_replace("/<table align=\"(.*?)\" border=\"(.*?)\" cellpadding=\"(.*?)\" cellspacing=\"(.*?)\" style=\"width\: (.*?)px\;\">(.*?)<\/table>/mi","{\\6 }", $doc_buffer);
		$doc_buffer = preg_replace("/<table align=\"(.*?)\" border=\"(.*?)\" cellpadding=\"(.*?)\" cellspacing=\"(.*?)\" height=\"(.*?)\" width=\"(.*?)\">(.*?)<\/table>/mi","{\\7 }", $doc_buffer);
				
		$doc_buffer = preg_replace("/<tbody>(.*?)<\/tbody>/mi","\\1", $doc_buffer);
		
		$doc_buffer = preg_replace("/<tr>(.*?)<\/tr>/mi","\\ql\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh90\\trleft-180 \\1 \\row", $doc_buffer);
	
		$doc_buffer = preg_replace("/<td>(.*?)<\/td>/mi"," \\1 \\ql \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth2500\\cellx\\q0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<td style=\"text-align\: (.*?)(eft|ight|enter)\;\">(.*?)<\/td>/mi"," \\3 \\q\\1 \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth2500\\cellx\\q0 ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<DIV class=titulos[a-zA-Z0-9-\"\=\'\;\:\s]*>(.*?)<\/DIV>/mi","{{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh300\\trleft-70 \\b \\1\\b0 \\qc \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth9000\\cellx\\q0 \\row}}", $doc_buffer);
		$doc_buffer = preg_replace("/<div class=\"titulos\"[a-zA-Z0-9-\"\=\'\;\:\s]*>(.*?)<\/div>/mi","{{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh300\\trleft-70 \\b \\1\\b0 \\qc \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth9000\\cellx\\q0 \\row}}", $doc_buffer);

		//$doc_buffer = preg_replace("/<div style=\"margin-left: 400px; text-align: justify; border: 1px solid #000000\">(.*?)<\/div>/mi","{{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh300\\trleft-70 \\b \\1\\b0 \\qr \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth3800\\cellx\\q0 \\row}}", $doc_buffer);
		//$doc_buffer = preg_replace("/<div style=margin-left: 400px; text-align: justify; border: 1px solid #000000>(.*?)<\/div>/mi","{{\\qc\\trowd\\irow0\\irowband0\\lastrow\\ltrrow\\ts11\\trqc\\trgaph70\\trrh300\\trleft-70 \\b \\1\\b0 \\qr \\cell \\clvertalc\\clbrdrt\\brdrs\\brdrw10\\clbrdrl\\brdrs\\brdrw10\\clbrdrb\\brdrs\\brdrw10\\clbrdrr\\brdrs\\brdrw10\\clftsWidth3\\clwWidth3800\\cellx\\q0 \\row}}", $doc_buffer);		
		$doc_buffer = preg_replace("/<div style=\"margin-left: 400px; text-align: justify; border: 1px solid #000000\">(.*?)<\/div>/mi","{{\\viewkind4\\uc1\\trowd\\trgaph70\\trleft-70\\trqr\\trrh300\\trpaddl70\\trpaddr70\\trpaddfl3\\trpaddfr3 \\clvertalc\\clbrdrl\\brdrw10\\brdrs\\clbrdrt\\brdrw10\\brdrs\\clbrdrr\\brdrw10\\brdrs\\clbrdrb\\brdrw10\\brdrs \\cellx3730\\pard\\intbl\\nowidctlpar\\qj\\f0\\fs24 \\1 \\cell\\row\\pard\\sa200\\sl276\\slmult1\\lang22\\f1\\fs22\\par}}", $doc_buffer);
		$doc_buffer = preg_replace("/<div style=margin-left: 400px; text-align: justify; border: 1px solid #000000>(.*?)<\/div>/mi","{{\\viewkind4\\uc1\\trowd\\trgaph70\\trleft-70\\trqr\\trrh300\\trpaddl70\\trpaddr70\\trpaddfl3\\trpaddfr3 \\clvertalc\\clbrdrl\\brdrw10\\brdrs\\clbrdrt\\brdrw10\\brdrs\\clbrdrr\\brdrw10\\brdrs\\clbrdrb\\brdrw10\\brdrs \\cellx3730\\pard\\intbl\\nowidctlpar\\qj\\f0\\fs24 \\1 \\cell\\row\\pard\\sa200\\sl276\\slmult1\\lang22\\f1\\fs22\\par}}", $doc_buffer);
		
		$doc_buffer = preg_replace("/<B>(.*?)<\/B>/mi", "\\b \\1\\b0 ", $doc_buffer);
		$doc_buffer = preg_replace("/<STRONG>(.*?)<\/B>/mi", "\\b \\1\\b0 ", $doc_buffer);
		
		//=---------------------------------------------------------------
		
		//$doc_buffer = preg_replace("/<p>(.*?)<\/p>/mi", "\\1\\par ", $doc_buffer);
		$doc_buffer = preg_replace("/<p>(.*?)<\/p>/mi", "\\ql \\1\\ql0 \\par", $doc_buffer);
		$doc_buffer = preg_replace("/<strong>(.*?)<\/strong>/mi", "\\b \\1\\b0 ", $doc_buffer);
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
		$doc_buffer = preg_replace("/<FONT size=5>(.*?)<\/FONT>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<FONT size=6>(.*?)<\/FONT>/mi", "\\fs48 \\1\\fs{$this->font_size} ", $doc_buffer);
		
		$doc_buffer = preg_replace("/<span style=font-size: 8px;>(.*?)<\/span>/mi", "\\fs16 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 9px;>(.*?)<\/span>/mi", "\\fs18 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 10px;>(.*?)<\/span>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 11px;>(.*?)<\/span>/mi", "\\fs22 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 12px;>(.*?)<\/span>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 14px;>(.*?)<\/span>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 16px;>(.*?)<\/span>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=font-size: 18px;>(.*?)<\/span>/mi", "\\fs48 \\1\\fs{$this->font_size} ", $doc_buffer);		
		
		$doc_buffer = preg_replace("/<span style=\"font-size: 8px;\">(.*?)<\/span>/mi", "\\fs16 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 9px;\">(.*?)<\/span>/mi", "\\fs18 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 10px;\">(.*?)<\/span>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 12px;\">(.*?)<\/span>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 14px;\">(.*?)<\/span>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 16px;\">(.*?)<\/span>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size: 18px;\">(.*?)<\/span>/mi", "\\fs48 \\1\\fs{$this->font_size} ", $doc_buffer);	

		$doc_buffer = preg_replace("/<span style=\"font-size:8px;\">(.*?)<\/span>/mi", "\\fs16\\1\\fs{$this->font_size}", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:9px;\">(.*?)<\/span>/mi", "\\fs18 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:10px;\">(.*?)<\/span>/mi", "\\fs20 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:12px;\">(.*?)<\/span>/mi", "\\fs24 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:14px;\">(.*?)<\/span>/mi", "\\fs28 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:16px;\">(.*?)<\/span>/mi", "\\fs36 \\1\\fs{$this->font_size} ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"font-size:18px;\">(.*?)<\/span>/mi", "\\fs48 \\1\\fs{$this->font_size} ", $doc_buffer);			
		//-------------------
		$doc_buffer = preg_replace("/<span style=\"font-family: (.*?),.+\;\">(.*?)<\/span>/mi", "\\f{$this->font_face} \\2 ", $doc_buffer);			
		
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
		$doc_buffer = str_replace("<span style=\"margin-left:40px\"> </span>", "\\tab \\tab ", $doc_buffer);
		$doc_buffer = str_replace("<span style=\"margin-left: 40px\">&nbsp;</span>", "\\tab \\tab ", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"margin-left:40px\">(.*?)<\/span>/mi", "\\tab \\tab \\1", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=\"margin-left: 40px\">(.*?)<\/span>/mi", "\\tab \\tab \\1", $doc_buffer);
		$doc_buffer = preg_replace("/<span style=margin-left: 40px>(.*?)<\/span>/mi", "\\tab \\tab \\1", $doc_buffer);		
		
		$doc_buffer = preg_replace("/<BLOCKQUOTE (.*?)>/i", " \\lin2000 ", $doc_buffer);
		//$doc_buffer = str_replace("<BLOCKQUOTE dir=ltr>", "\\lin2000 ", $doc_buffer);
		//$doc_buffer = str_replace("<BLOCKQUOTE style=\"MARGIN-RIGHT: 0px\" dir=ltr>", "\\lin2000 ", $doc_buffer);
		$doc_buffer = str_replace("</BLOCKQUOTE>", " \\lin0", $doc_buffer);
		$doc_buffer = str_replace("</blockquote>", " \\lin0", $doc_buffer);
		
		//convertendo para imagem rtf
		$image2 = preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\;.+\/>.+/i", "\\1", $doc_buffer);
		$imgw = preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\;.+\/>.+/i", "\\2", $doc_buffer);
		$imgh = preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\;.+\/>.+/i", "\\3", $doc_buffer);
		$imgf = strtolower(substr(trim(preg_replace("/.*?<img alt=\"\" src=\"\/(.*?)\" style=\"width\:(.*?)px\; height\:(.*?)px\; float\:(.*?)\;.+\/>.+/i", "\\4", $doc_buffer)),0,1));
		$image3 = " ".($imgf=='r'||$imgf=='l'||$imgf=='c'||$imgf=='j'?"\q".$imgf:"")." {\pict\wmetafile8\picw5292\pich1588\picwgoal".($imgw*15)."\pichgoal".($imgh*15)." " . bin2hex(@file_get_contents("../".trim($image2))) . "} ".($imgf=='r'||$imgf=='l'||$imgf=='c'||$imgf=='j'?"\q0":"")." \par ";
		$doc_buffer = preg_replace("/<img alt=\"\" src=\"\/(.*?)\" style=.+\/>/i", "\\1 ", $doc_buffer);
		//$doc_buffer = str_replace(trim($image2), $image3, $doc_buffer);
		
		$doc_buffer = $this->nl2par($doc_buffer);
		
		return $doc_buffer;
	}
}
?>