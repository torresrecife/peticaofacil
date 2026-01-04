<?php
function formata_data_extenso($strDate){
	$arrMonthsOfYear = array(1 => 'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro');
	$intDayOfMonth = date("d");
	$intMonthOfYear = date("n");
	$intYear = date("Y");
	return $intDayOfMonth . ' de ' . $arrMonthsOfYear[$intMonthOfYear] . ' de ' . $intYear. '.';
}

function fc_select($p_tb,$p_id,$val_id,$val_nome,$usu,$conex,$p_setor=""){
	$q = mysqli_query($conex,"SELECT $val_id , $val_nome FROM " . $p_tb. " where 1=1 " . ($usu!="" ? "and id_cliente in (0," . $usu  . ")" : "") . " " . ($p_setor!=0 ? "and id_setor = " . $p_setor : "") . " GROUP BY " . $val_nome . " ORDER BY " . $val_nome. " ");
	echo "<option></option>";
	
	while($w = mysqli_fetch_array($q)){
		echo "<option value='" . $w[$val_id] . "' " . ($w[$val_id] == "$p_id" ? "selected" : "") . ">" . $w[$val_nome] . "</option>";
	}
}
function fc_select_li($p_tb,$p_id,$val_id,$val_nome,$usu_cliente,$conex,$p_setor=""){
	$r=0;
	$q = mysqli_query($conex,"SELECT $val_id , $val_nome FROM " . $p_tb. "  where 1=1 " . ($usu_cliente!=0 ? "and id_cliente in (0," . $usu_cliente  . ")" : "") . " " . ($p_setor!=0 ? "and id_setor = " . $p_setor : "") . " GROUP BY " . $val_nome . " ORDER BY " . $val_nome. " ");	
	$n = mysqli_num_rows($q);
	$li = array();
	$t=18;
	while($w = mysqli_fetch_array($q)){
		$r++;
		if($r%18==0){
			$t=18+$t;
		}
		$li[$r] = "<li style='width:280px'><a class='icon-16-copy' href='#' onclick='EnviarDados(\"index.php\",\"$p_id\",".$w[$val_id].");'>" . $w[$val_nome] . "</a></li>";
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
	$q = mysqli_query($conex, "	SELECT *, a.$val_id , a.$val_nome, a.nome_pre, a.nome_pos, a.id_setor FROM " . $p_tb. " as a 
						left join tp_clientes_db as c on a.id_cliente=c.cliente_id 
						left join tp_setor_tb AS s ON s.id_setor=a.id_setor 
						where 1=1 " . ($usu!=0 ? "and a.id_cliente in (0," . $usu  . ")" : "") . " " . ($p_setor!=0 ? "and a.id_setor = " . $p_setor : "") . " GROUP BY a." . $val_id . " ORDER BY a.id_setor asc, c.cliente_name, a." . $val_nome. " ");
	$n=0;
	while($w = mysqli_fetch_array($q)){	
			$n++;
        $SETOR[$w['id_setor']] .= "<div class='icon-wrapper'>";
            $SETOR[$w['id_setor']] .= "<div class='icon_pecas'>";
								if($se=="E"){
									$SETOR[$w['id_setor']] .= "<a href='#' onclick='mark_active(this)' class='clspet' grupo='0' numpet='" . $w[$val_id] . "'>";
								}elseif($se=="S"){
									if($w['id_cliente']==0){
										$bgcor = "#FFFFFF";
									}else{
										$bgcor = "#F0F4FF";
									}
									$SETOR[$w['id_setor']] .= "<a href='#' onclick='EnviarDados(\"index.php\",\"$p_id\",".$w[$val_id].");' style='background:$bgcor'>";
								}
                            $SETOR[$w['id_setor']] .= "<table width='100%'>";
                                $SETOR[$w['id_setor']] .= "<tr height='20px'>";
							        $SETOR[$w['id_setor']] .= "<td colspan='1' align='left' style='font-size:7pt;padding:2px;color:#999'>"  . $n . " </td>";
							        $SETOR[$w['id_setor']] .= "<td colspan='8' align='left' style='font-size:7pt;padding:2px;color:#999'>"  . ($w['cliente_name']?$w['cliente_name']:$w['nome_setor']) . "</td>";
                                $SETOR[$w['id_setor']] .= "</tr>";
                                $SETOR[$w['id_setor']] .= "<tr height='20px'>";
                                    $SETOR[$w['id_setor']] .= "<td colspan='3' align='left' style='width: 40px !important;'><img src='css/images/header/icon-48-article-edit.png' alt='' style='width:35px;padding:0px 0;' /></td>";
                                    $SETOR[$w['id_setor']] .= "<td colspan='6' align='left' style='width: 169px !important; font-size: 10px'>" . trim($w[$val_nome]) . "</td>";
                                $SETOR[$w['id_setor']] .= "</tr>";
                            $SETOR[$w['id_setor']] .= "</table>";
                $SETOR[$w['id_setor']] .= "</a>";
		    $SETOR[$w['id_setor']] .= "</div>";
        $SETOR[$w['id_setor']] .= "</div>";
	
	}
	foreach($SETOR as $SET){
		echo $SET;
	}
}
function fc_select_dados($id_input,$conex,$p_setor=""){
	$q = mysqli_query($conex,"SELECT id_dados, nome_dados FROM tp_dados_tb where id_input = '$id_input' " . ($p_setor!=0 ? "and id_setor = " . $p_setor : "") . " ORDER BY nome_dados asc ");
	echo "<option></option>";
	
	while($w = mysqli_fetch_array($q)){
		echo "<option value='" . $w['id_dados'] . "' " . ($w[$val_id] == "$p_id" ? "selected" : "") . ">" . $w['nome_dados'] . "</option>";
	}
}

function fc_select_name($cond,$where,$col,$banco,$conex){
	if($where!='' && $col !='' && $banco !=''){
		$campo = explode("|_|",$col);
		$sel  = " SELECT ";
		
		for($i=0;$i<=count($campo);$i++){
			if($campo[$i] != ''){
				$sel .= ($i> 0 ? (',' . $campo[$i]) : $campo[$i] );
			}
		}
		$sel .= " FROM $banco";
		$sel .= " where $cond = $where";
		$sel .= " limit 1";			
		$q = mysqli_query($conex,$sel);
		$w = mysqli_fetch_array($q);
		return $w[0];
		//return "SELECT $col FROM $banco where $cond = $where limit 1"; //$w[0];
	}else{
		return '';
	}
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
		require_once("Html2Rtf/class_rtf_cab.php");
		if($rodcab=="cab"){
			$codcab = new rtf("Html2Rtf/rtf_config.php");
			$queryc = mysqli_query($conex,"SELECT t.cod_cabec FROM tp_tipo_tb AS t WHERE t.tipo_id = '".$tipoid."' ");
			$whilec = mysqli_fetch_array($queryc);
			$codcab->addText($whilec['cod_cabec']);
			return $codcab->getDocument();
			
		} elseif($rodcab=="rod"){
			$codrod = new rtf("Html2Rtf/rtf_config.php");
			$queryr = mysqli_query($conex,"SELECT t.cod_rodap FROM tp_tipo_tb AS t WHERE t.tipo_id = '".$tipoid."' ");
			$whiler = mysqli_fetch_array($queryr);
			$codrod->addText($whiler['cod_rodap']);
			return $codrod->getDocument();
		}else{
			return "";
		}
	}elseif($rtfpdf=="pdf"){
		require_once("seguranca.php");
		$querycr = mysqli_query($conex,"SELECT t.cod_cabec, t.cod_rodap FROM tp_tipo_tb AS t WHERE t.tipo_id = '".$tipoid."' ");
		$whilecr = mysqli_fetch_array($querycr);
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