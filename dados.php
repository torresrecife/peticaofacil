
<table align="center" class="sub_title_tb">
	<tr>
		<td class="sub_title_tb_l">
			<?php
				$tipoNome = function_exists('app_to_utf8') ? app_to_utf8($tw['tipo_nome']) : $tw['tipo_nome'];
				$clienteNome = $tw['cliente_name'] ? $tw['cliente_name'] : 'GERAL';
				$clienteNome = function_exists('app_to_utf8') ? app_to_utf8($clienteNome) : $clienteNome;
			?>
			<div style="background:#FFF;float:left"><?php echo "Cod: ".$tw['tipo_id']." - ".$tipoNome; ?></div>
			<div style="background:#FFF;float:right"><?php echo $clienteNome; ?></div>
		</td>
	</tr>
</table>


<table align="center" class="content_form">
<?php
function normalize_select_value($label)
{
	$normalized = strtoupper(trim($label));
	if (function_exists('iconv')) {
		$normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
	}
	$normalized = preg_replace('/[^A-Z]/', '', $normalized);
	if ($normalized === 'SIM' || $normalized === 'NAO') {
		return $normalized;
	}
	return $label;
}

if($TIPOPET!=""){
	$displ = $_POST['hid_enviar']==7?'block':'none';
	$inputs = array();
	if (class_exists(\App\Services\InputService::class)) {
		$inputService = new \App\Services\InputService($conexao1);
		$inputs = $inputService->listFullByTipo($TIPOPET);
	}
	if(count($inputs)>0) {
		echo "<tr>";
		$n = 0;
		$onFuncoes = "";
		$nomepet = "";
		foreach ($inputs as $w){
			
			$onFuncoes .= ($w['input_focu']!='' ? (" onfocus='" . $w['input_focu'] . "' ") : "");
			$onFuncoes .= ($w['input_load']!='' ? (" onload='"  . $w['input_load'] . "' ") : "");
			$onFuncoes .= ($w['input_blur']!='' ? (" onblur='"  . $w['input_blur'] . "' ") : "");
			
			($w['input_tipo']=='HIDDEN'?"":$n++);
			$tag = "campo" . $w['id_input'];
			$dd = $w['input_val'];			
			if($w['input_tipo']=='SELECT') {
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $inputTitle . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>";					  
				$selectClass = "input-default " . $w['add_class'] . " js-combobox";
				$dataAttrs = "";
				
				$optionsHtml = "";
				if($w['input_db']!=""){
					$input_db = explode("_|_",$w['input_db']);
					$inputdb_0 = $input_db[0];
					$inputdb_1 = $input_db[1];
					$inputdb_2 = $input_db[2];
					$inputdb_3 = $input_db[3];
					$inputdb_4 = $input_db[4];
					$inputdb_5 = isset($input_db[5])?$input_db[5]:'';
					$dd_input  = $dados[$w['input_val']];

					if($inputdb_4=="hori"){
						$selectClass .= " js-horiz-select";
						$dataAttrs .= " data-dd-input='" . htmlspecialchars((string) $dd_input, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-inputdb-0='" . htmlspecialchars((string) $inputdb_0, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-inputdb-1='" . htmlspecialchars((string) $inputdb_1, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-inputdb-2='" . htmlspecialchars((string) $inputdb_2, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-inputdb-3='" . htmlspecialchars((string) $inputdb_3, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-inputdb-4='" . htmlspecialchars((string) $inputdb_4, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-inputdb-5='" . htmlspecialchars((string) $inputdb_5, ENT_QUOTES, 'UTF-8') . "'";
						$dataAttrs .= " data-hinput-source='campo" . htmlspecialchars((string) $inputdb_5, ENT_QUOTES, 'UTF-8') . "'";
						$optionsHtml .= "<option></option>";
					}else if($inputdb_4=="vert"){
						$where = $inputdb_3 ? $inputdb_3 : '1=1';
						$andClause = isset($and) ? $and : '';
						$rows = array();
						if (class_exists(\App\Services\SelectService::class)) {
							$selectService = new \App\Services\SelectService($conexao1);
							$rows = $selectService->listRowsByTable(
								$inputdb_0,
								$where,
								$andClause,
								($inputdb_5 ? $inputdb_5 : $inputdb_1)
							);
						}
						$optionsHtml .= "<option></option>";
						foreach($rows as $wsel){
							$optLabel = $wsel[$inputdb_1];
							if (function_exists('app_to_utf8')) {
								$optLabel = app_to_utf8($optLabel);
							}
							$optValue = normalize_select_value($optLabel);
							$optionsHtml .= "<option value='" . $optValue . "' ident='" . $wsel[0] . "' " . ( trim(str_replace(" ","",$dd_input))==trim(str_replace(" ","",$wsel[$inputdb_1])) ? 'selected' : '') . " >" . $optLabel . "</option>";
						}
					}
				}else{
					$dadosRows = array();
					if (class_exists(\App\Services\DadosService::class)) {
						$dadosService = new \App\Services\DadosService($conexao1);
						$dadosRows = $dadosService->listByInput($w['id_input']);
					}
					$option = "";
					$select = "";
					foreach($dadosRows as $wsel){
						if($dados[$dd]==$wsel['nome_dados']){
							$select = "selected";
						}
						$label = function_exists('app_to_utf8') ? app_to_utf8($wsel['nome_dados']) : $wsel['nome_dados'];
						$value = normalize_select_value($label);
						$option .= "<option value='" . $value . "' ident='" . $wsel['id_dados'] . "' $select >" . $label . "</option>";
					}
					$firstOpt = ($dados[$dd]!="" ?  $dados[$dd] : "" );
					if (function_exists('app_to_utf8')) {
						$firstOpt = app_to_utf8($firstOpt);
					}
					$optionsHtml .= ($select!="selected"?("<option>" . $firstOpt . "</option>"):"");
					$optionsHtml .= $option;
				}
				$inputTitleAttr = strtoupper($w['input_title']);
				if (function_exists('app_to_utf8')) {
					$inputTitleAttr = strtoupper(app_to_utf8($w['input_title']));
				}
				echo "<select type='text' id='" . $tag . "' name='" . $tag . "' class='" . $selectClass . "' style='width:" . $w['input_width'] . "px' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . $inputTitleAttr . "'" . $dataAttrs . ">";
				echo $optionsHtml;
				echo "</select><br>" . fc_botoes($w['id_input'],$displ) . "</td>";
			}elseif($w['input_tipo']=='TEXT'){
				$valor_text = ($dados[$dd]!="" ? $dados[$dd] : "");
				$valor_text = str_replace("BUSCA E APREENSÃO EM ALIENAÇÃO FIDUCIÁRIA","BUSCA E APREENSÃO",$valor_text);
				if (function_exists('app_to_utf8')) {
					$valor_text = app_to_utf8($valor_text);
				}
				//if($valor_text!=""){
					$text_pre = $w['input_pre']!=''?$w['input_pre']:"";
					$text_pos = $w['input_pos']!=''?$w['input_pos']:"";
					if (function_exists('app_to_utf8')) {
						$text_pre = app_to_utf8($text_pre);
						$text_pos = app_to_utf8($text_pos);
					}
				//}else{
				//	$text_pre="";
				//}
				
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				$inputAlt = $w['input_alt'];
				if (function_exists('app_to_utf8')) {
					$inputAlt = app_to_utf8($inputAlt);
				}
				$inputTitleAttr = strtoupper($w['input_title']);
				if (function_exists('app_to_utf8')) {
					$inputTitleAttr = strtoupper(app_to_utf8($w['input_title']));
				}
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $inputTitle . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>
								<input type='hidden' id='" . $tag . "_pre' name='" . $tag . "_pre' value='".$text_pre."' />
								<input type='hidden' id='" . $tag . "_pos' name='" . $tag . "_pos' value='".$text_pos."' />
								<input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $valor_text . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px' alt='" . $inputAlt . "' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . $inputTitleAttr . "'/>".($w['input_req']==2?"<span style='float:right;font-size:12pt;margin-right:10px;color:red'>*</span>":"") . "
						<br>" . fc_botoes($w['id_input'],$displ) . "</td>";
				//utilizar para o nome da petição
				if($w['nomepet']=="Y"){
					$nomepet .= $tag ."_|_";
				}
			}elseif($w['input_tipo']=='RADIO'){
				//Exemplo abaixo - tem que ser alterado posteriormente
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title'>
						<label>Tipo Pessoa:	</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>
						<div style='height:23px; width: 200px;text-align:center'>
							<label>Física:&nbsp;</label>
							<input type='radio' name='TIPOPES' value='cpf' class='input-default " . $w['add_class'] . "' " . ($dados[$w['input_val']] == 'F' ? 'checked' : '') . " />
							<label>&nbsp;&nbsp;Jurídica:&nbsp;</label>
							<input type='radio' name='TIPOPES' value='cnpj' class='input-default " . $w['add_class'] . "' " . ($dados[$w['input_val']] == 'J' ? 'checked' : '') . " />
						</div></td>";
			}elseif($w['input_tipo']=='RADIO2'){
				//Exemplo abaixo - tem que ser alterado posteriormente
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title'>
						<label><b>" . $inputTitle . ": </b></label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>";						
						$dadosRows = array();
						if (class_exists(\App\Services\DadosService::class)) {
							$dadosService = new \App\Services\DadosService($conexao1);
							$dadosRows = $dadosService->listByInput($w['id_input']);
						}
						$option = "<div>";
						$select = "";
						foreach($dadosRows as $wsel){
							if($dados[$dd]==$wsel['nome_dados']){
								$select = "selected";
							}
					$label = function_exists('app_to_utf8') ? app_to_utf8($wsel['nome_dados']) : $wsel['nome_dados'];
					$option .= "<label>" . $label . "</label><input type='radio' name='".$tag."' value='" . $wsel['nome_dados'] . "' class='input-default " . $w['add_class'] . "' " . ($dados[$w['input_val']] == 'F' ? 'checked' : '') . " " . $onFuncoes . "/>";
						}
						echo $option;
				echo "</div></td>";
					
			}elseif($w['input_tipo']=='TITLE'){
				echo "</tr><tr>";
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title' >";
                if($w['input_title']==""){
                    echo "<hr style='border-color: #d3d3d3'>";
                }else{
                    echo "<div>&nbsp;</div><p align='center' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px; height:20px; margin-left:0px; padding-top:3px; margin-bottom:0px'><b>" . $inputTitle . "</b>";
                }
                    echo fc_botoes($w['id_input'],$displ) . "</td>";
                    echo "</tr>";
                $n=0;
            }elseif($w['input_tipo']=='BOTTOM'){
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				$inputAlt = $w['input_alt'];
				$inputValue = ($dados[$dd]!="" ? $dados[$dd]:"");
				if (function_exists('app_to_utf8')) {
					$inputAlt = app_to_utf8($inputAlt);
					$inputValue = app_to_utf8($inputValue);
				}
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title' ><label>" . $inputTitle . "</label><br><input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $inputValue . "' class='input-default " . $w['add_class'] . "' style='color:666; width:" . $w['input_width'] . "px' alt='" . $inputAlt . "' " . $onFuncoes . " readonly='readonly' />
						" . fc_botoes($w['id_input'],$displ) . "</td>";
			}elseif($w['input_tipo']=='TEXTAREA'){
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				$inputAlt = $w['input_alt'];
				$inputValue = $w['texto_padrao'] . ($dados[$dd]!='' ? $dados[$dd]:'');
				if (function_exists('app_to_utf8')) {
					$inputAlt = app_to_utf8($inputAlt);
					$inputValue = app_to_utf8($inputValue);
				}
				$inputTitleAttr = strtoupper($w['input_title']);
				if (function_exists('app_to_utf8')) {
					$inputTitleAttr = strtoupper(app_to_utf8($w['input_title']));
				}
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $inputTitle . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>
						<input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $inputValue . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px;' alt='" . $inputAlt . "' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . $inputTitleAttr . "' onfocus='fc_textarea(this,\"" . $inputTitle . "\",2);' carregar='0'/>
						<br>". fc_botoes($w['id_input'],$displ) . "</td>";
			}elseif($w['input_tipo']=='HIDDEN'){
				$inputTitle = function_exists('app_to_utf8') ? app_to_utf8($w['input_title']) : $w['input_title'];
				$inputAlt = $w['input_alt'];
				$inputTitleAttr = strtoupper($w['input_title']);
				$inputTitleRaw = $w['input_title'];
				if (function_exists('app_to_utf8')) {
					$inputAlt = app_to_utf8($inputAlt);
					$inputTitleAttr = strtoupper(app_to_utf8($w['input_title']));
					$inputTitleRaw = app_to_utf8($w['input_title']);
				}
				echo "<div style='float:left;display:" . $displ . "'><label>" . $inputTitle . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br><input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $text_pre . $valor_text . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px; display:".$displ."' alt='" . $inputAlt . "' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . $inputTitleAttr . "' title='" . htmlentities($inputTitleRaw) . "'/><div style='float:left'>" . fc_botoes($w['id_input'],$displ) . "</div></div>";
			}
			$cols = $w['input_rols'];
			for($i=1;$i<=$cols;$i++){
				echo "</tr><tr>";
				$n=0;
			}
			
			if($n==3){
				echo "</tr><tr>";
				$n=0;
			}
			//Limpa as funcões
			$onFuncoes = "";
		}
		echo "</tr>";
		echo "<input type='hidden' name='nomepet' id='nomepet' value='".$nomepet."' />";
		echo "<input type='hidden' name='petnome' id='petnome' value='".$tw[0]."' />";
	}
}
?>
</table>
<table align="center" width="650px" >
	<tr>
		<td height="30px" align="right"><button type="button" value="" class="input-default cls_campos" onclick="fc_inputs('I',this)" style="height:25px; display:<?php echo $displ; ?>">+ Campos</button></td>
	</tr>
	<tr>
	<?php if(count($inputs)>0) { ?>
		<td height="30px" align="center">
			<button type="button" onclick="return EnviarDados('index.php','2','<?php echo $_POST['TIPOPET']; ?>')" style="height:25px" id="bt-enviar-dados" class="input-default">Enviar Dados</button>
			<br/><br/>
			<div id="msg-enviar-dados" style="display:none;color:red">Esse contrato não poderá ser ajuizado!</div>
		</td>
		<?php }else{ ?>
		<td height="30px" align="center"><button type="button" onclick="Cpadrao(<?php echo $_POST['TIPOPET']; ?>)" style="height:25px" class="input-default">Criar campos 'padrão'</button></td>		
		<?php }?>
	</tr>
</table>
<table align="center" class="sub_title_tb_b" >
	<tr>
		<td class="sub_title_tb_r"><?php echo $cntdo; ?></td>
	</tr>
</table>


