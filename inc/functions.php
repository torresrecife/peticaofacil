<?php
function formata_data_extenso($strDate){
	$arrMonthsOfYear = array(1 => 'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro');
	$intDayOfMonth = date("d");
	$intMonthOfYear = date("n");
	$intYear = date("Y");
	return $intDayOfMonth . ' de ' . $arrMonthsOfYear[$intMonthOfYear] . ' de ' . $intYear. '.';
}

function fc_select($p_tb,$p_id,$val_id,$val_nome,$usu,$conex,$p_setor=""){
	if (!class_exists(\App\Repositories\TipoRepository::class)) {
		echo "<option></option>";
		return;
	}
	$repo = new \App\Repositories\TipoRepository($conex);
	$q = $repo->listForSelect($usu, $p_setor);
	echo "<option></option>";
	
	foreach($q as $w){
		$label = function_exists('app_to_utf8') ? app_to_utf8($w[$val_nome]) : $w[$val_nome];
		echo "<option value='" . $w[$val_id] . "' " . ($w[$val_id] == "$p_id" ? "selected" : "") . ">" . $label . "</option>";
	}
}
function fc_select_li($p_tb,$p_id,$val_id,$val_nome,$usu_cliente,$conex,$p_setor=""){
	$r=0;
	if (!class_exists(\App\Repositories\TipoRepository::class)) {
		return;
	}
	$repo = new \App\Repositories\TipoRepository($conex);
	$q = $repo->listForSelect($usu_cliente, $p_setor);
	$n = count($q);
	$li = array();
	$t=18;
	foreach($q as $w){
		$r++;
		if($r%18==0){
			$t=18+$t;
		}
		$label = function_exists('app_to_utf8') ? app_to_utf8($w[$val_nome]) : $w[$val_nome];
		$li[$r] = "<li style='width:280px'><a class='icon-16-copy' href='#' onclick='return EnviarDados(\"index.php\",\"$p_id\",".$w[$val_id].");'>" . $label . "</a></li>";
		//str_pad($w[$val_id], 3, '0', STR_PAD_LEFT);
	}
	$s=0;
	echo "<ul style='width:280px'>";
	//echo "SELECT $val_id , $val_nome FROM " . $p_tb. "  where 1=1 " . ($usu_cliente!=0 ? "and id_cliente in (0," . $usu_cliente . ")" : "") . " " . ($p_setor!=0 ? "and id_setor = " . $p_setor : "") . " GROUP BY " . $val_nome . " ORDER BY " . $val_nome. " ";
	for($i=0;$i<$t;$i++){
		echo $li[$i];
		if($i>=$n){
			echo "<li style='width:280px'><a class='icon-16-copy' href='#'></a></li>";
		}
		if($i%18==0){
			echo "</ul>";
			echo "<ul style='margin-left:".$s."px;width:280px'>";
			$s=280+$s;
		}
	}
	echo "</ul>";
}
function fc_select_div($p_tb,$p_id,$val_id,$val_nome,$usu,$se,$conex,$p_setor=""){
	//$SETOR_1 = "";
	if (!class_exists(\App\Repositories\TipoRepository::class)) {
		return;
	}
	$cacheDir = __DIR__ . '/../storage/cache';
	$cacheTtl = 300;
	$cacheKey = 'tipos_div_' . $p_id . '_' . $usu . '_' . $se . '_' . (int) $p_setor;
	$cacheFile = $cacheDir . '/' . $cacheKey . '.html';
	if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
		echo file_get_contents($cacheFile);
		return;
	}
	$repo = new \App\Repositories\TipoRepository($conex);
	$q = $repo->listWithRelations($usu, $p_setor);
	$n=0;
	$setorBlocks = array();
	foreach($q as $w){
		$n++;
		$setorId = $w['id_setor'];
		if (!isset($setorBlocks[$setorId])) {
		$setorName = $w['nome_setor'] ? $w['nome_setor'] : 'Setor';
		if (function_exists('app_to_utf8')) {
			$setorName = app_to_utf8($setorName);
		}
			$setorBlocks[$setorId] = "<div class='setor-group' style='clear:both;padding-top:10px;'>";
			$setorBlocks[$setorId] .= "<div class='setor-title' style='font-weight:bold;margin:6px 0;color:#444;'>" . $setorName . "</div>";
		}
		$setorBlocks[$setorId] .= "<div class='icon-wrapper'>";
		$setorBlocks[$setorId] .= "<div class='icon_pecas'>";
		if($se=="E"){
			$setorBlocks[$setorId] .= "<a href='#' onclick='return mark_active(this)' class='clspet' grupo='0' numpet='" . $w[$val_id] . "'>";
		}elseif($se=="S"){
			if($w['id_cliente']==0){
				$bgcor = "#FFFFFF";
			}else{
				$bgcor = "#F0F4FF";
			}
			$setorBlocks[$setorId] .= "<a href='#' onclick='return EnviarDados(\"index.php\",\"$p_id\",".$w[$val_id].");' style='background:$bgcor'>";
		}
		$setorBlocks[$setorId] .= "<table width='100%'>";
		$setorBlocks[$setorId] .= "<tr height='20px'>";
		$setorBlocks[$setorId] .= "<td colspan='1' align='left' style='font-size:7pt;padding:2px;color:#999'>"  . $n . " </td>";
		$clientLabel = ($w['cliente_name']?$w['cliente_name']:$w['nome_setor']);
		if (function_exists('app_to_utf8')) {
			$clientLabel = app_to_utf8($clientLabel);
		}
		$setorBlocks[$setorId] .= "<td colspan='8' align='left' style='font-size:7pt;padding:2px;color:#999'>"  . $clientLabel . "</td>";
		$setorBlocks[$setorId] .= "</tr>";
		$setorBlocks[$setorId] .= "<tr height='20px'>";
		$setorBlocks[$setorId] .= "<td colspan='3' align='left' style='width: 40px !important;'><img src='css/images/header/icon-48-article-edit.png' alt='' style='width:35px;padding:0px 0;' /></td>";
		$title = trim($w[$val_nome]);
		if (function_exists('app_to_utf8')) {
			$title = app_to_utf8($title);
		}
		$setorBlocks[$setorId] .= "<td colspan='6' align='left' style='width: 169px !important; font-size: 10px'>" . $title . "</td>";
		$setorBlocks[$setorId] .= "</tr>";
		$setorBlocks[$setorId] .= "</table>";
		$setorBlocks[$setorId] .= "</a>";
		$setorBlocks[$setorId] .= "</div>";
		$setorBlocks[$setorId] .= "</div>";
	}
	$rendered = '';
	foreach($setorBlocks as $block){
		$rendered .= $block . "</div>";
	}
	if ($rendered !== '' && is_dir($cacheDir)) {
		file_put_contents($cacheFile, $rendered);
	}
	echo $rendered;
}
function fc_select_dados($id_input,$conex,$p_setor=""){
	if (!class_exists(\App\Services\DadosService::class)) {
		echo "<option></option>";
		return;
	}
	$service = new \App\Services\DadosService($conex);
	$q = $service->listByInput($id_input, $p_setor);
	echo "<option></option>";
	
	foreach($q as $w){
		$label = function_exists('app_to_utf8') ? app_to_utf8($w['nome_dados']) : $w['nome_dados'];
		echo "<option value='" . $w['id_dados'] . "'>" . $label . "</option>";
	}
}

function fc_select_name($cond,$where,$col,$banco,$conex){
	if($where!='' && $col !='' && $banco =='tp_tipo_tb' && $cond == 'tipo_id' && $col == 'tipo_nome'){
		if (!class_exists(\App\Repositories\TipoRepository::class)) {
			return '';
		}
		$repo = new \App\Repositories\TipoRepository($conex);
		$row = $repo->findWithClienteById($where);
		$label = $row['tipo_nome'] ?? '';
		return function_exists('app_to_utf8') ? app_to_utf8($label) : $label;
	}
	return '';
}

//Maiúscula
function upwords($str){
	$valor = strtolower($str);
	//$valor = preg_replace('#\s(como?|d[aeo]s?|desde|para|por|que|sem|sob|sobre|trás)\s#ie', '" ".strtolower("\1")." "', ucwords($str));
	$valor = ucwords($valor);
	$valor = str_replace(" E "," e ",$valor);
	$valor = str_replace(" E "," e ",$valor);
	$valor = str_replace(" DE "," de ",$valor);
	$valor = str_replace("S.a","S.A",$valor);
	$valor = str_replace(" O "," o ",$valor);
	$valor = str_replace(" No "," no ",$valor);
	$valor = str_replace(" N&ordm;"," n&ordm;",$valor);
	$valor = str_replace(" Nº"," nº",$valor);
	$valor = str_replace(" Cpf"," CPF",$valor);
	return $valor;
}

function convertemin($term) {
    $palavra = strtr(strtolower($term),"ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß","àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ");
    return $palavra;
}

function limita_caracteres($texto, $limite, $quebra = true){
   $tamanho = strlen($texto);
   if($tamanho <= $limite){ //Verifica se o tamanho do texto é menor ou igual ao limite
      $novo_texto = $texto;
   }else{ // Se o tamanho do texto for maior que o limite
      if($quebra == true){ // Verifica a opção de quebrar o texto
         $novo_texto = trim(substr($texto, 0, $limite))."...";
      }else{ // Se não, corta $texto na última palavra antes do limite
         $ultimo_espaco = strrpos(substr($texto, 0, $limite), " "); // Localiza o útlimo espaço antes de $limite
         $novo_texto = trim(substr($texto, 0, $ultimo_espaco)).""; // Corta o $texto até a posição localizada
      }
   }
   return $novo_texto; // Retorna o valor formatado
}
function fc_botoes($valor,$displ){
	return "<div id='module-status' style='display:" . $displ . ";'>
				<span class='editar'><a href='javascript:fc_inputs(\"U\",\"" . $valor . "\");' class='button_del' title='Editar Campo'>Editar</a></span>
				<span class='excluir'><a href='javascript:fc_del_input(\"" . $valor . "\");' class='button_del' title='Excluir Campo'>Excluir</a></span>
			</div>";
}
function fc_botoes_usu($id_usu,$displ,$nome=""){
	return "<div id='module-status' style='display:" . $displ . ";'>
				<span class='editar'><a href='javascript:fc_edit_usu(\"$id_usu\",\"U\");' class='button_del' title='Editar Usuário'>Editar</a></span>
				<span class='excluir'><a href='javascript:fc_del_usu(\"$id_usu\",\"$nome\");' class='button_del' title='Excluir Usuário'>Excluir</a></span>
			</div>";
}
function fc_botoes_sql($id_sql,$displ,$nome=""){
	return "<div id='module-status' style='display:" . $displ . ";'>
				<span class='editar'><a href='javascript:fc_edit_sql(\"$id_sql\",\"U\");' class='button_del' title='Editar Servidor'>Editar</a></span>
				<span class='excluir'><a href='javascript:fc_del_sql(\"$id_sql\",\"$nome\");' class='button_del' title='Excluir Servidor'>Excluir</a></span>
			</div>";
}
function fc_botoes_grp($id_list,$displ,$nome=""){
	return "<div id='module-status' style='display:" . $displ . ";'>
				<span class='editar'><a href='javascript:fc_edit_list(\"$id_list\",\"U\");' class='button_del' title='Editar Servidor'>Editar</a></span>
				<span class='excluir'><a href='javascript:fc_del_list(\"$id_list\",\"$nome\");' class='button_del' title='Excluir Servidor'>Excluir</a></span>
			</div>";
}
function fc_botoes_setor($id_setor,$displ,$nome=""){
	return "<div id='module-status' style='display:" . $displ . ";'>
				<span class='editar'><a href='javascript:fc_edit_setor(\"$id_setor\",\"U\");' class='button_del' title='Editar Setor'>Editar</a></span>
				<span class='excluir'><a href='javascript:fc_del_setor(\"$id_setor\",\"$nome\");' class='button_del' title='Excluir Setor'>Excluir</a></span>
			</div>";
}
function fc_botoes_cliente($cliente_id,$displ,$nome=""){
	return "<div id='module-status' style='display:" . $displ . ";'>
				<span class='editar'><a href='javascript:fc_edit_cliente(\"$cliente_id\",\"U\");' class='button_del' title='Editar Cliente'>Editar</a></span>
				<span class='excluir'><a href='javascript:fc_del_cliente(\"$cliente_id\",\"$nome\");' class='button_del' title='Excluir Cliente'>Excluir</a></span>
			</div>";
}
function cabecalhoerodape($tipoid,$rodcab,$rtfpdf,$conex){
	if($rtfpdf=="rtf"){
		require_once __DIR__ . "/../Html2Rtf/class_rtf_cab.php";
		if (!class_exists(\App\Services\TipoService::class)) {
			return "";
		}
		$tipoService = new \App\Services\TipoService($conex);
		$codigos = $tipoService->getCabecRodapById($tipoid);
		if (!$codigos) {
			return "";
		}
		if($rodcab=="cab"){
			$codcab = new rtf("Html2Rtf/rtf_config.php");
			$codcab->addText($codigos['cod_cabec'] ?? '');
			return $codcab->getDocument();
			
		} elseif($rodcab=="rod"){
			$codrod = new rtf("Html2Rtf/rtf_config.php");
			$codrod->addText($codigos['cod_rodap'] ?? '');
			return $codrod->getDocument();
		}else{
			return "";
		}
	}elseif($rtfpdf=="pdf"){
		require_once __DIR__ . "/seguranca.php";
		if (!class_exists(\App\Services\TipoService::class)) {
			return "";
		}
		$tipoService = new \App\Services\TipoService($conex);
		$whilecr = $tipoService->getCabecRodapById($tipoid);
		if (!$whilecr) {
			return "";
		}
		//$dire = $_SERVER['DOCUMENT_ROOT'];
		$dire = "http://10.81.11.202";
		
		if($rodcab=="cab"){
			return str_replace('src="','src="'.$dire.'',$whilecr['cod_cabec']);
		} elseif($rodcab=="rod"){
			return str_replace('src="','src="'.$dire.'',$whilecr['cod_rodap']);
		}else{
			return "";
		}
	}else{
		return "";
	}
}
//function vernavegador(){
//	$lista_navegadores = array('MSIE', 'Firefox', 'Chrome', 'Safari');
//	$navegador_usado = $_SERVER['HTTP_USER_AGENT'];
//	foreach($lista_navegadores as $valor_verificar){
//		if(strrpos($navegador_usado, $valor_verificar)){
//			$navegador = $valor_verificar;
//		}
//	}
//	return $navegador;
//}
?>

