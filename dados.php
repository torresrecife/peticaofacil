
<table align="center" class="sub_title_tb">
	<tr>
		<td class="sub_title_tb_l">
			<div style="background:#FFF;float:left"><?php echo "Cod: ".$tw['tipo_id']." - ".$tw['tipo_nome']; ?></div>
			<div style="background:#FFF;float:right"><?php echo $tw['cliente_name']?$tw['cliente_name']:'GERAL'; ?></div>
		</td>
	</tr>
</table>

<script>
var rand = 0;
var config3 = {
	extraPlugins: 'autogrow,myplugin,sharedspace,uploadimage',
	removePlugins: 'floatingspace,resize',
	sharedSpaces: {
		top: 'topSpace',
		bottom: 'bottomSpace'
	},
	language: 'pt_BR',
	contentsCss : 'css/texto.css'
	};
	CKEDITOR.config.skin = 'moono-lisa';
	CKEDITOR.config.tabSpaces = 4;
	CKEDITOR.config.removePlugins = 'elementspath';
	// CKEDITOR.config.width = 618.7;
	CKEDITOR.config.defaultLanguage = 'pt_BR';
	CKEDITOR.config.toolbarGroups = [
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
		{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
		{ name: 'insert', groups: [ 'insert' ] },
		{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
		{ name: 'styles', groups: [ 'styles' ] },
		{ name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'colors', groups: [ 'colors' ] },
		{ name: 'tools', groups: [ 'tools' ] },
		{ name: 'links', groups: [ 'links' ] },
		{ name: 'forms', groups: [ 'forms' ] },
		{ name: 'others', groups: [ 'others' ] },
		{ name: 'about', groups: [ 'about' ] }
	];
	CKEDITOR.config.removeButtons = 'Save,NewPage,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Image,Flash,Smiley,SpecialChar,Iframe,About,ShowBlocks,Templates,Anchor,Unlink,Link,Language,BidiRtl,BidiLtr,Styles,Blockquote,CreateDiv,PageBreak,Print,Preview';
	
	function fc_textarea(valor,texto,editor){
		rand = parseInt(rand) + 1;
		var $dialog = $('<div></div>')
			.html(
				"<textarea id='id_text_"+rand+"' style='width:99%;height:200px'>" + valor.value + "</textarea>"
				)
			.dialog({
				position: ["60%",145],
				width: "700px",
				modal: true,
				autoOpen: true,
				close: function(){
					///apaga o editor de texto CKE
					$("#topSpace").html("");
				},
				buttons: {
					Sim: function() {
						$( this ).dialog( "close" );
						$('#'+valor.id).val($('#id_text_'+rand).val());
					},	
					"Não": function() {
						$( this ).dialog( "close" );
					}
				},
				title: texto,
			});
		if(editor==2){
			$('#id_text_'+rand).ckeditor(config3);
		}
	}
	
	function validate_peticao(args=""){
		//alert(1);
		var dd = 0;
		$('.new_required').each(function(index,object) {
			
			if(args!=""){
				if($(object).val()=="AUSENTE" || $(object).val()=="MUDOU-SE" || $(object).val()=="DESCONHECIDO"  ){
					dd=1;
				}
			}else{
				if($(object).val()=="NÃO" && $(object).attr("type")!="radio"){
					dd=1;	
				}else if($(object).attr("type")=="radio"){
					setTimeout(function(){
						//console.log($(object).is(":checked"));
						if($(object).is(":checked")==true && $(object).val()=="NÃO"){
							dd=1;
						}
						
					},200);
				}
			}
			
		});
		
		setTimeout(function(){		
			if(dd==1){
				$("#bt-enviar-dados").attr("disabled",true).css("border","1px solid red");
				$(".content_form").css("border","1px solid red");
				$("#msg-enviar-dados").show();
			}else{
				$("#bt-enviar-dados").attr("disabled",false).css("border","1px solid #d3d3d3");
				$(".content_form").css("border","0");
				$("#msg-enviar-dados").hide();
			}
		},500);
	}
</script>

<table align="center" class="content_form">
<?php

if($TIPOPET!=""){
	$displ = $_POST['hid_enviar']==7?'block':'none';
	$q = mysqli_query($conexao1,"SELECT * FROM tp_inputs_tb where tipo_id = '" . $TIPOPET . "' ORDER BY input_order, id_input");
	if(mysqli_num_rows($q)>0) {
		echo "<tr>";
		$n = 0;
		$onFuncoes = "";
		$nomepet = "";
		while($w = mysqli_fetch_array($q)){
			
			$onFuncoes .= ($w['input_focu']!='' ? (" onfocus='" . $w['input_focu'] . "' ") : "");
			$onFuncoes .= ($w['input_load']!='' ? (" onload='"  . $w['input_load'] . "' ") : "");
			$onFuncoes .= ($w['input_blur']!='' ? (" onblur='"  . $w['input_blur'] . "' ") : "");
			
			($w['input_tipo']=='HIDDEN'?"":$n++);
			$tag = "campo" . $w['id_input'];
			$dd = $w['input_val'];			
			if($w['input_tipo']=='SELECT') {
				echo "<td colspan='" . $w['input_cols'] . "' class='td_title dis_" . $tag . "' style='display:" . $w['hide'] . "'><label>" . $w['input_title'] . "</label><label style='float:right;margin-right:30px;display:" . $displ . "'>" . $w['input_order'] . " - Campo" . $w['id_input'] . "</label><br>";					  
				echo "<select type='text' id='" . $tag . "' name='" . $tag . "' class='input-default " . $w['add_class'] . "' style='width:" . $w['input_width'] . "px' " . $onFuncoes . " obrigatorio='" . $w['input_req'] . "' descricao='" . strtoupper($w['input_title']) . "' >";
				
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
						///passado algum tempo deve remover esse ajax e deletar o arquivo ajax_horiz.php
						echo "<script>$(function() { 
								$.ajax({
									type: 'POST',
									url:  'inc/ajax_horiz.php',
									data: 'flag=H&dd_input=$dd_input&hinput='+$('#campo$inputdb_5').val()+'&inputdb_0=".$inputdb_0."&inputdb_1=".$inputdb_1."&inputdb_2=".$inputdb_2."&inputdb_3=".$inputdb_3."&inputdb_4=".$inputdb_4."&inputdb_5=".$inputdb_5."',
									success: function(retorno_ajax){
										//alert(retorno_ajax);
										$('#" . $tag . "').html(retorno_ajax);
									}
								});
							});
						  </script>";
					}else if($inputdb_4=="vert"){
						$where = $inputdb_3 ? $inputdb_3 : '1=1';
						$qsel = mysqli_query($conexao1,"SELECT * FROM " . $inputdb_0 . " WHERE $where $and ORDER BY '" . ($inputdb_5?$inputdb_5:$inputdb_1) . "' asc ");
						//echo "SELECT $conca FROM " . $inputdb_0 . " WHERE $where $and ORDER BY " . $inputdb_1 . " asc ";
						echo "<option></option>";
						while($wsel = mysqli_fetch_array($qsel)){
							echo "<option value='" . $wsel[2] . "' ident='" . $wsel[0] . "' " . ( trim(str_replace(" ","",$dd_input))==trim(str_replace(" ","",$wsel[$inputdb_1])) ? 'selected' : '') . " >" . $wsel[$inputdb_1] . "</option>";
						}
					}
				}else{
					$qsel = mysqli_query($conexao1,"SELECT * FROM tp_dados_tb where id_input = '" . $w['id_input'] . "' ORDER BY nome_dados asc ");
					$option = "";
					$select = "";
					while($wsel = mysqli_fetch_array($qsel)){
						if($dados[$dd]==$wsel['nome_dados']){
							$select = "selected";
						}
						$option .= "<option value='" . $wsel['nome_dados'] . "' ident='" . $wsel['id_dados'] . "' $select >" . $wsel['nome_dados'] . "</option>";
					}
					echo ($select!="selected"?("<option>" . ( $dados[$dd]!="" ?  $dados[$dd] : "" ) . "</option>"):"");
					echo $option;
				}
				echo "</select><br>" . fc_botoes($w['id_input'],$displ) . "</td>";
				echo "<script>$(function() { $('#$tag').combobox(); });</script>";
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
						$qsel = mysqli_query($conexao1,"SELECT * FROM tp_dados_tb where id_input = '" . $w['id_input'] . "' ORDER BY nome_dados asc ");
						$option = "<div>";
						$select = "";
						while($wsel = mysqli_fetch_array($qsel)){
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
		<?php if(mysqli_num_rows($q)>0) { ?>
		<td height="30px" align="center">
			<button type="button" onclick="EnviarDados('index.php','2','<?php echo $_POST['TIPOPET']; ?>')" style="height:25px" id="bt-enviar-dados" class="input-default">Enviar Dados</button>
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

