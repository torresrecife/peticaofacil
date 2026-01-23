<?php
$cls_text = "";
for($i=0;$i<=$_POST['edit_text'];$i++){
	$cls_text .= str_replace('\"','"',$_POST['cls_text_' . $i]);
}

if(isset($_POST['is_pecas'])==1){
	if (class_exists(\App\Services\PecaService::class)) {
		$pecaService = new \App\Services\PecaService($conexao1);
		$arr_pecas = $pecaService->getEditInfo($_POST['id_pecas']);
	} else {
		$arr_pecas = null;
	}
	if ($arr_pecas) {
		$cls_text = $arr_pecas['cod_pecas'];
		$flag = 2;
		$id_pecas=$_POST['id_pecas'];
		//VERIFICAR SE É O MESMO USUÁRIO E SE TEM MENOS DE 10 MIN PARA EDITAR A MESMA PEÇA
		if($arr_pecas['minutos']<=10 && $arr_pecas['id_usu']==$_SESSION['usuarioID']){
			$codsav = $arr_pecas["cod_sav"];
		}else{
			$codsav = $_POST["codsav"];
		}
	} else {
		$flag = 1;
		$id_pecas="";
		$codsav = $_POST["codsav"];
	}
}else{ 
	$flag = 1;
	$id_pecas="";
	$codsav = $_POST["codsav"];
}

$wdoc = array();
if (class_exists(\App\Services\TipoService::class)) {
	$tipoService = new \App\Services\TipoService($conexao1);
	$tipoArq = $tipoService->getTipoArquivoById($_POST['tipo_id']);
	$wdoc['tipo_arq'] = $tipoArq;
}

?>
<div class="include_arq">
	<table id="tb_left" align="left" width="60px" height="80%">
		<tr height="30px" valign="top">
			<td>
				<a id="botao_next" href="#" onclick="goToByScroll('titulos',$('#id_topicos').val(),1);" ></a>
			</td>
		</tr>
		<tr>
			<td align="left" valign="middle">
				<div id="topicT" ></div>
			</td>					
		</tr>
		<tr height="30px" valign="bottom">
			<td>
				<a id='botao_prev' href="#" onclick="goToByScroll('titulos',$('#id_topicos').val(),0)" style="display:none" ></a>
			</td>
		</tr>
	</table>
	<table id="tb_right" align="right" width="60px" height="80%">
		<tr>
			<td align="right" valign="top">
				<a id="scrlBotm" href="#"></a>
			</td>
			<input type="hidden" id="id_topicos" value="0" />
		</tr>
		<tr>
			<td align="right" valign="bottom">
				<a id="scrlTop" href="#"></a>
			</td>
		</tr>
	</table>
	<table border="0" id="tb_editor" background="img/fundo.jpg" align="center" width="790px" >
		<tr>
			<td width="100px"></td>
			<td colspan="3" align="center" style="margin-bottom:100px;" width="600px" id="cls_textarea">
				<div style="background: #fff;opacity: 1.0;position: relative;z-index: 99;margin-top:5px;margin-bottom:5px;">
					<?php echo cabecalhoerodape($_POST['tipo_id'],"cab","pdf",$conexao1); ?>
				</div>
				<?php 
					$cls_text2 = $cls_text;
					if (class_exists(\App\Services\PecaService::class)) {
						$pecaService = new \App\Services\PecaService($conexao1);
						$Wcdsv = $pecaService->findByCodSavOrId($_POST['codsav'] ?? '', $_POST['id_pecas'] ?? '');
						if ($Wcdsv) {
							$id_pecas = $Wcdsv['id_pecas'];
							$cls_text2 = $Wcdsv['cod_pecas'];
						}
					}
					if (is_string($cls_text2) && $cls_text2 !== '' && !preg_match('//u', $cls_text2)) {
						$cls_text2 = utf8_encode($cls_text2);
					}
				?>
				<textarea id="name_text" name="name_text" border="0" ><?php echo $cls_text2; ?></textarea>
				<div style="background: #fff;opacity: 0.2;position: relative;z-index: 99;margin-top:5px;margin-bottom:5px;"><?php echo cabecalhoerodape($_POST['tipo_id'],"rod","pdf",$conexao1); ?></div>
				<input type="hidden" name="tipo_id" id="tipo_id" value="<?php echo $_POST['tipo_id']; ?>" />
			</td>
			<td width="70px"></td>
		</tr>
	</table>
	<table width="800px" align="center" >
		<tr height="40px" >
			<td align="center">
				<div align="center" id="bottomSpace" style="width:790px"></div>
			</td>
		</tr>
	</table>
	<br>
	<table id="div_bottom" align="center">
		<tr>
			<td>
				<?php 
					$_POST['nomepet'] = $_POST['nomecli']?$_POST['nomecli']:$_POST['nomepet'];
				?>
				<br><span id="spn_nom">Nome do Arquivo: &nbsp;</span>
				<input type="text" name="nomepet" id="nomepet" value="<?php echo $_POST['nomepet']==""?"Nome_do_Arquivo":$_POST['nomepet']; ?>" />
				<?php 
				if($wdoc['tipo_arq']=='pdf'){
				?>
					<input type="submit" id="ger_pdf" name="ger_pdf" value="" onclick="return EnviarDados('inc/getpdf.php','','');" >
				<?php
				}elseif($wdoc['tipo_arq']=='word' || $wdoc['tipo_arq']=='pdf,word'){ 
				?>
					<textarea name='cod_cabec' id='cod_cabec' style='float:left;position:relative;margin-left:-1000px' ><?php echo cabecalhoerodape($_POST['tipo_id'],"cab","rtf",$conexao1); ?></textarea>
					<textarea name='cod_rodap' id='cod_rodap' style='float:left;position:relative;margin-left:-1000px' ><?php echo str_replace(" \qc","\qc",cabecalhoerodape(($_POST['tipo_id']),"rod","rtf",$conexao1)); ?></textarea>
					<input type="submit" id="ger_rtf" name="ger_rtf" value="" onclick="return EnviarDados('inc/getrtf.php','','');" >
					<?php
					if($wdoc['tipo_arq']=='pdf,word'){
					?>
						<input type="submit" id="ger_pdf" name="ger_pdf" value="" onclick="return EnviarDados('inc/getpdf.php','','');" >
					<?php
					}
				}
				
				if($_SESSION['usuarioID']==2){
					?>
					<input type="submit" id="ger_pdf_2" name="ger_pdf_2" value="" onclick="return EnviarDados('inc/getpdf_2.php','','');" >
					<input type="submit" id="ger_wor_2" name="ger_wor_2" value="" onclick="return EnviarDados('inc/getwor_2.php','','');" >
					<?php	
				}
				?>
				<input type="button" id="ger_sav" name="ger_sav" value="" onclick="fc_salvar_pet(1);" >
				<input type="hidden" id="id_sav" value="<?php echo $id_pecas; ?>" flag="<?php echo $flag; ?>"/>
				<input type="hidden" id="codsav" name="codsav" value="<?php echo $codsav; ?>" />
			</td>
		</tr>
	</table>
</div>


