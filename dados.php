
<table align="center" class="sub_title_tb">
	<tr>
		<td class="sub_title_tb_l">
			<div style="background:#FFF;float:left"><?php echo "Cod: ".$tw['tipo_id']." - ".$tw['tipo_nome']; ?></div>
			<div style="background:#FFF;float:right"><?php echo $tw['cliente_name']?$tw['cliente_name']:'GERAL'; ?></div>
		</td>
	</tr>
</table>

<script type="text/javascript" src="js/pages/dados.js"></script>

<table align="center" class="content_form">
<?php

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
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $w['input_title'] . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>";					  
				$selectClass = "input-default " . $w['add_class'] . " js-combobox";
				$dataAttrs = "";
				
				echo "<select type='text' id='" . $tag . "' name='" . $tag . "' class='" . $selectClass . "' style='width:" . $w['input_width'] . "px' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . strtoupper($w['input_title']) . "'" . $dataAttrs . ">";
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
							$optionsHtml .= "<option value='" . $wsel[2] . "' ident='" . $wsel[0] . "' " . ( trim(str_replace(" ","",$dd_input))==trim(str_replace(" ","",$wsel[$inputdb_1])) ? 'selected' : '') . " >" . $wsel[$inputdb_1] . "</option>";
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
						$option .= "<option value='" . $wsel['nome_dados'] . "' ident='" . $wsel['id_dados'] . "' $select >" . $wsel['nome_dados'] . "</option>";
					}
					$optionsHtml .= ($select!="selected"?("<option>" . ( $dados[$dd]!="" ?  $dados[$dd] : "" ) . "</option>"):"");
					$optionsHtml .= $option;
				}
				echo "<select type='text' id='" . $tag . "' name='" . $tag . "' class='" . $selectClass . "' style='width:" . $w['input_width'] . "px' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . strtoupper($w['input_title']) . "'" . $dataAttrs . ">";
				echo $optionsHtml;
				echo "</select><br>" . fc_botoes($w['id_input'],$displ) . "</td>";
			}elseif($w['input_tipo']=='TEXT'){
				$valor_text = ($dados[$dd]!="" ? $dados[$dd] : "");
				$valor_text = str_replace("BUSCA E APREENSÃO EM ALIENAÇÃO FIDUCIÁRIA","BUSCA E APREENSÃO",$valor_text);
				//if($valor_text!=""){
					$text_pre = $w['input_pre']!=''?$w['input_pre']:"";
					$text_pos = $w['input_pos']!=''?$w['input_pos']:"";
				//}else{
				//	$text_pre="";
				//}
				
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $w['input_title'] . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>
								<input type='hidden' id='" . $tag . "_pre' name='" . $tag . "_pre' value='".$text_pre."' />
								<input type='hidden' id='" . $tag . "_pos' name='" . $tag . "_pos' value='".$text_pos."' />
								<input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $valor_text . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px' alt='" . $w['input_alt'] . "' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . strtoupper($w['input_title']) . "'/>".($w['input_req']==2?"<span style='float:right;font-size:12pt;margin-right:10px;color:red'>*</span>":"") . "
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
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title'>
						<label><b>" . $w['input_title'] . ": </b></label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>";						
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
							
							$option .= "<label>" . $wsel['nome_dados'] . "</label><input type='radio' name='".$tag."' value='" . $wsel['nome_dados'] . "' class='input-default " . $w['add_class'] . "' " . ($dados[$w['input_val']] == 'F' ? 'checked' : '') . " " . $onFuncoes . "/>";
						}
						echo $option;
				echo "</div></td>";
					
			}elseif($w['input_tipo']=='TITLE'){
				echo "</tr><tr>";
                echo "<td colspan='" . $w['input_cols'] . "' class='td_title' >";
                if($w['input_title']==""){
                    echo "<hr style='border-color: #d3d3d3'>";
                }else{
                    echo "<div>&nbsp;</div><p align='center' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px; height:20px; margin-left:0px; padding-top:3px; margin-bottom:0px'><b>" . $w['input_title'] . "</b>";
                }
                    echo fc_botoes($w['id_input'],$displ) . "</td>";
                    echo "</tr>";
                $n=0;
            }elseif($w['input_tipo']=='BOTTOM'){
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title' ><label>" . $w['input_title'] . "</label><br><input type='text' id='" . $tag . "' name='" . $tag . "' value='" . ($dados[$dd]!="" ? $dados[$dd]:"") . "' class='input-default " . $w['add_class'] . "' style='color:666; width:" . $w['input_width'] . "px' alt='" . $w['input_alt'] . "' " . $onFuncoes . " readonly='readonly' />
						" . fc_botoes($w['id_input'],$displ) . "</td>";
			}elseif($w['input_tipo']=='TEXTAREA'){
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $w['input_title'] . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>
						<input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $w['texto_padrao'] . ($dados[$dd]!='' ? $dados[$dd]:'') . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px;' alt='" . $w['input_alt'] . "' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . strtoupper($w['input_title']) . "' onfocus='fc_textarea(this,\"" . $w['input_title'] . "\",2);' carregar='0'/>
						<br>". fc_botoes($w['id_input'],$displ) . "</td>";
			}elseif($w['input_tipo']=='HIDDEN'){
				echo "<div style='float:left;display:" . $displ . "'><label>" . $w['input_title'] . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br><input type='text' id='" . $tag . "' name='" . $tag . "' value='" . $text_pre . $valor_text . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px; display:".$displ."' alt='" . $w['input_alt'] . "' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . strtoupper($w['input_title']) . "' title='" . htmlentities($w['input_title']) . "'/><div style='float:left'>" . fc_botoes($w['id_input'],$displ) . "</div></div>";
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


