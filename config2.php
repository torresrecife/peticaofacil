<table align="center" class="sub_title_tb_cfg">
	<tr>
		<td align="left">Editando os parágrafos de:&nbsp;&nbsp;<?php echo $tw[0]; ?></td>
		<td align="right"><?php echo $cntdo; ?></td>
	</tr>
</table>

<?php

$campos = "";
$inputs = array();
if (class_exists(\App\Services\InputService::class)) {
	$inputService = new \App\Services\InputService($conexao1);
	$inputs = $inputService->listFullByTipo($_POST['TIPOPET']);
}
foreach ($inputs as $idx => $w) {
	$campos .= $idx > 0 ? "|_|" : "";
	$campos .= $w['input_title'];
	$campos .= "_|_";
	$campos .= "@campo" . $w['id_input'] . "@";
}

function normalize_utf8($value)
{
    if (!is_string($value) || $value === '') {
        return $value;
    }
    return preg_match('//u', $value) ? $value : utf8_encode($value);
}

?>
<div align="center" class="content_form_cfg">
	<div align="left" id="accordion" style="width:800px;" >
		<br>
		<?php
		$tipo_tb = $_POST['TIPOPET'] ? $_POST['TIPOPET'] : "''";
		$rows = array();
		if (class_exists(\App\Services\ParagrafoService::class)) {
			$parService = new \App\Services\ParagrafoService($conexao1);
			$rows = $parService->listByTipoWithArquivo($tipo_tb);
		}
		$obj_text = explode("_|_",$_POST['obj_text']);
		$n=0;
		$cod_rodap="";
		foreach ($rows as $wtext){
			if($n==0 && $_POST['TIPOPET']!=""){
				?>
				<br>
				<div class="group">
					<h3>
						<a href="#" style="cursor: move;" ><i>CABEÇALHO</i></a>
					</h3>
					<div align="center">
						<textarea class="cls_text" id="input_cabec" name="input_cabec"><?php echo $wtext['cod_cabec']; ?></textarea>
						<div align="right" style="padding:0 65px 5px 0;" >
							<button type="button" value="<?php echo $_POST['TIPOPET']; ?>" class="input-default" onclick="save_parag(<?php echo $_POST['TIPOPET']; ?>,'#input_cabec','C')" style="height:25px">Salvar</button>
						</div>
					</div>
				</div>
				<?php
				$cod_rodap = $wtext['cod_rodap'];
			}
			?>
			<input type='hidden' name='#dados<?php echo $n; ?>' id='#dados<?php echo $n; ?>' value='' />
			<div class="group">
				<h3>
					<a href="#" style="cursor: move;" ><?php echo normalize_utf8($wtext['fund_titulo']); ?></a>
				</h3>
				<div align="center">
					<textarea class="cls_text" id="input<?php echo $n; ?>" name="input<?php echo $n; ?>"><?php echo urldecode($wtext['fund_text']); ?></textarea>
					<div align="right" style="padding:0 65px 5px 0;" >
						<button type="button" value="<?php echo $wtext['fund_id']; ?>" class="input-default" onclick="save_parag(<?php echo $wtext['fund_id']; ?>,'#input<?php echo $n; ?>','S') " style="height:25px">Salvar</button>&nbsp;
						<button type="button" value="<?php echo $wtext['fund_id']; ?>" class="input-default" onclick="del_parag(<?php echo $wtext['fund_id']; ?>) " style="height:25px">Excluir</button>&nbsp;
					</div>
				</div>
			</div>
			<?php
			$n++;
		}if($n!=0 && $_POST['TIPOPET']!=""){
			?>
			<div class="group">
				<h3>
					<a href="#" style="cursor: move;" ><i>RODAPÉ</i></a>
				</h3>
				<div align="center">
					<textarea class="cls_text" id="input_rodape" name="input_rodape"><?php echo $cod_rodap; ?></textarea>
					<div align="right" style="padding:0 65px 5px 0;" >
						<button type="button" value="<?php echo $_POST['TIPOPET']; ?>" class="input-default" onclick="save_parag(<?php echo $_POST['TIPOPET']; ?>,'#input_rodape','R')" style="height:25px">Salvar</button>
					</div>
				</div>
			</div>
			<?php
		}
		?>
	</div>
	<div align="center">
		<br/>
		<div align="center" id="bottomSpace" style="width:790px;"></div>
		<br/>
		<?php 
			echo $_POST['TIPOPET']!=""?"<button type='button' class='input-default' onclick='novo_parag();' style='height:25px;'>Novo Tópico</button>":"";
		?>
		<br/>
	</div>
	<br><br>
	
	<input type="hidden" id="tipo_id" value="<?php echo $tipo_tb; ?>" >
	<input type="hidden" name="name_text" id="name_text" >
	<input type="hidden" name="act_parag" id="act_parag" value="<?php echo $_POST['act_parag'] ? $_POST['act_parag'] : 0; ?>" >
	<input type="hidden" id="str_retorno_ajax" value="<?php echo $campos; ?>">
</div>
<div id="dialog_parag" title="Novo Tópico" style="display:none">
	<div style="height:80px">
		<center>
			<br/>
			<table>
				<tr height="30px">
					<td align="left" class="td_title"><b>Título do Tópico <br /><input type="text" id="TOPTITLE" style="width:220px"/></td>
				</tr>
			</table> 
		</center>	
	</div>
</div>


