<?php
$arr_cp = array();
if (class_exists(\App\Services\DadosService::class)) {
	$dadosService = new \App\Services\DadosService($conexao1);
	$arr_cp = $dadosService->listDadosMap();
}

function normalize_utf8($value)
{
	if (!is_string($value) || $value === '') {
		return $value;
	}
	return preg_match('//u', $value) ? $value : utf8_encode($value);
}

function normalize_upper($value)
{
	$value = normalize_utf8($value);
	if (function_exists('mb_strtoupper')) {
		return mb_strtoupper($value, 'UTF-8');
	}
	return strtoupper($value);
}

function normalize_title($value)
{
	$value = normalize_utf8($value);
	if (function_exists('mb_convert_case')) {
		return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
	}
	return upwords(convertemin($value));
}
?>
<div align="center" class="include_arq">		
	<div align="left" id="accordion" style="width:880px;" >
		<?php	
		$tipo_tb   = $_POST['TIPOPET'] ?: "''";
		$rows = array();
		if (class_exists(\App\Services\ParagrafoService::class)) {
			$parService = new \App\Services\ParagrafoService($conexao1);
			$rows = $parService->listByTipoWithArquivo($tipo_tb);
		}
		$n=0;
		foreach ($rows as $wtext){
			if($n==0){
				?>
				<input type="hidden" name="tipo_arq" value="<?php echo $wtext['tipo_arq']; ?>" />
				<?php 
			}
			?>
			<input type="hidden" class="fund_id" value="<?php echo $wtext['fund_id']; ?>" />
			<div class="group">
			<h3><a href="#" style="cursor: move;" ><?php echo normalize_utf8($wtext['fund_titulo']); ?></a><img src="css/images/closeButton.png" alt="delete" class="delete" /></h3>
				<div align="center">
					<textarea id="cls_text_<?php echo $n; ?>" name="cls_text_<?php echo $n; ?>" class="cls_text" style="width:690px;" >
						<?php
						$para_text = normalize_utf8($wtext['fund_text']);
								//Pegando o valor dos names do POST
								foreach($_POST as $obj => $val){
								$val_utf = normalize_utf8($val);
								$map_val = isset($arr_cp[$val]) ? normalize_utf8($arr_cp[$val]) : null;
									//Definindo o valor do name (se existir)
									
									//Definindo quanto a marcação '@CAMPO@' for maiúscula
								if(strpos($para_text, "@" . mb_strtoupper($obj) . "@") != false){
										$i=1;
										while ($i <= substr_count($para_text, "@" . mb_strtoupper($obj) . "@")) {
											$i++;										
										$replacement = $map_val ? normalize_upper($map_val) : normalize_upper($val_utf);
										$para_text = str_replace("@" . mb_strtoupper($obj) . "@", $replacement, $para_text);
										}
									}elseif(strpos($para_text, "@" . upwords(convertemin($obj)) . "@") != false){
										//Definindo quanto a marcação '@Campo@' for a primeira letra maiúscula
										$f=1;
										while ($f <= substr_count($para_text, "@" . upwords(convertemin($obj)) . "@")) {
											$f++;										
										$replacement = $map_val ? normalize_title($map_val) : normalize_title($val_utf);
										$para_text = str_replace("@" . upwords(convertemin($obj)) . "@", $replacement, $para_text);
										}										
									}else{
										//Definindo quanto a marcação '@campo@' mesmo tamanho
										$g=1;
										while ($g <= substr_count($para_text, "@" . $obj . "@")) {
											$g++;										
										$replacement = $map_val ? $map_val : $val_utf;
										$para_text = str_replace("@$obj@", $replacement, $para_text);
										}
									}										
								}
							$baseUrl = getenv('APP_URL') ?: '';
							$basePath = $baseUrl ? rtrim(parse_url($baseUrl, PHP_URL_PATH), '/') . '/' : '';
							if ($basePath) {
								$para_text = str_replace("/peticaofacil/", $basePath, $para_text);
								$para_text = str_replace("/bvaa/peticaofacil/", $basePath, $para_text);
							}
							echo str_replace(", ,",",",str_replace(", , ,",", ,",str_replace("972&nbsp;DA","VARA DE FEITOS DE RELAÇÃO DE CONSUMO CÍVEL E COMERCIAIS DA",$para_text)));
						?>
					</textarea>
				</div>
			</div>
			<?php
			$n++;
		}
		?>
	</div>
	<div align="center" id="bottomSpace" style="width:880px;"></div>
	<div align="center">
		<br/>
		<!--button type="button" onclick="javascript:history.back();" class="input-default" style="height:25px;">Voltar</button>
		<button type="button" onclick="gerar_texto();" 	class="input-default" style="height:25px;">Gerar</button-->
		<input type="submit" value="Unir Parágrafos" onclick="return EnviarDados('index.php','3','');" class="input-default" style="height: 30px; cursor:pointer;" />
	</div>
	<input type="hidden" name="tipo_id"   id="tipo_id"   value="<?php echo $tipo_tb; ?>" />
	<input type="hidden" name="name_text" id="name_text">
	<input type="hidden" name="edit_text" id="edit_text" value="<?php echo $n; ?>" />
	<input type='hidden' name='url_dir'   id='url_dir'   value='<?php echo $_POST['url_dir']; ?>' />
	<input type="hidden" name="nomecli"	  id="nomecli"   value="<?php echo $_POST['nomecli']; ?>" />
	<input type="hidden" name="petnome"	  id="petnome"   value="<?php echo $_POST['petnome']; ?>" />
	<input type="hidden" name="codsav"	  id="codsav"    value="<?php echo date("YmdHis"); ?>" />
	
	
	<?php 
	$petnome= $_POST['petnome']?".".$_POST['petnome']:"";
	
	$npet = explode("_|_",$_POST['nomepet']);
	
	//pega o código do setor
	$nomepet = '';
	if (class_exists(\App\Services\TipoService::class)) {
		$tipoService = new \App\Services\TipoService($conexao1);
		$nomepet = (string) $tipoService->getSetorCodeByTipo($tipo_tb);
	}
	foreach($npet as $nm){
		if($nm!=""){
			$nomepet .= ".".$_POST[$nm];
		}
	}
	?>
	<input type="hidden" name="nomepet" id="nomepet" value="<?php echo $nomepet.$petnome.".".date('Ymd'); ?>" />
</div>
<!--Crinado Inputs dinâmicos-->
<div>
	<div id="dialog_inputs" title="Novo Input" style="display:none">
		<div style="height:140px">
			<center>
				<br/>
				<table>
					<tr height="30px">
						<td align="left" class="td_title"><b>Título  <br /><input type="text" id="IMPTITLE" style="width:220px"/></td>
					</tr>
					<tr>
						<td align="left" class="td_title"><b>NAME:   <br /><input type="text" id="IMPNAME"  style="width:220px" onkeypress="validaCaractaer(event)";/></td>
					</tr>
				</table> 
			</center>	
		</div>
	</div>
</div>


