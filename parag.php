<?php
$qu = mysqli_query($conexao1,"SELECT id_dados, nome_dados FROM tp_dados_tb ");
while($wl = mysqli_fetch_array($qu)){
	$arr_cp[$wl['id_dados']] = $wl['nome_dados'];
}
?>
<script language="javascript">	
//Demo
$(function() {
	$( "#accordion" )
	.accordion({
		autoHeight: false,
		navigation: true,
		header: "> div > h3"
	})
});
$(document).ready(function(){
	$(".group .delete").click(function(){
		$(this).parents(".group").fadeOut('slow', function(){ $(this).remove();});
	});
});

</script>
<div align="center" class="include_arq">		
	<div align="left" id="accordion" style="width:880px;" >
		<?php	
		$tipo_tb   = $_POST['TIPOPET'] ? $_POST['TIPOPET'] : "''";
		$sel_text  = " SELECT * FROM tp_funda_tb as tf";
		$sel_text .= " JOIN tp_tipo_tb as tt on tt.tipo_id = tf.tipo_id ";
		$sel_text .= " JOIN tp_inputs_tb AS ti ON ti.tipo_id = tt.tipo_id";
		$sel_text .= " WHERE tt.tipo_id = " . $tipo_tb;
		$sel_text .= " GROUP BY tf.fund_id";
		$sel_text .= " ORDER BY tf.fund_order ASC";
		$que_text = mysqli_query($conexao1,$sel_text);
		$n=0;
		while($wtext = mysqli_fetch_array($que_text)){
			if($n==0){
				?>
				<input type="hidden" name="tipo_arq" value="<?php echo $wtext['tipo_arq']; ?>" />
				<?php 
			}
			?>
			<input type="hidden" class="fund_id" value="<?php echo $wtext['fund_id']; ?>" />
			<div class="group">
				<h3><a href="#" style="cursor: move;" ><?php echo $wtext['fund_titulo']; ?></a><img src="css/images/closeButton.png" alt="delete" class="delete" /></h3>
				<div align="center">
					<textarea id="cls_text_<?php echo $n; ?>" name="cls_text_<?php echo $n; ?>" class="cls_text" style="width:690px;" >
						<?php
							$para_text = $wtext['fund_text'];
								//Pegando o valor dos names do POST
								foreach($_POST as $obj => $val){
									//Definindo o valor do name (se existir)
									
									//Definindo quanto a marcação '@CAMPO@' for maiúscula
									if(strpos($para_text, "@" . mb_strtoupper($obj) . "@") != false){
										$i=1;
										while ($i <= substr_count($para_text, "@" . mb_strtoupper($obj) . "@")) {
											$i++;										
											$para_text = str_replace("@" . mb_strtoupper($obj) . "@",($arr_cp[$val] ? $arr_cp[mb_strtoupper($val)] : mb_strtoupper($val)),$para_text);
										}
									}elseif(strpos($para_text, "@" . upwords(convertemin($obj)) . "@") != false){
										//Definindo quanto a marcação '@Campo@' for a primeira letra maiúscula
										$f=1;
										while ($f <= substr_count($para_text, "@" . upwords(convertemin($obj)) . "@")) {
											$f++;										
											$para_text = str_replace("@" . upwords(convertemin($obj)) . "@",($arr_cp[$val] ? $arr_cp[upwords(convertemin($val))] : upwords(convertemin($val))),$para_text);
										}										
									}else{
										//Definindo quanto a marcação '@campo@' mesmo tamanho
										$g=1;
										while ($g <= substr_count($para_text, "@" . $obj . "@")) {
											$g++;										
											$para_text = str_replace("@$obj@",($arr_cp[$val] ? $arr_cp[$val] : $val),$para_text);
										}
									}										
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
	<script language="javascript">
		
		$(function() {			
			var config2 = {
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
			CKEDITOR.config.width = 618.7;
			CKEDITOR.config.defaultLanguage = 'pt_BR';
			CKEDITOR.config.toolbarGroups = [
				{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
				{ name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
				{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
				{ name: 'insert', groups: [ 'insert','Image' ] },
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
			CKEDITOR.config.removeButtons = 'Save,NewPage,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Flash,Smiley,SpecialChar,Iframe,About,ShowBlocks,Templates,Anchor,Unlink,Link,Language,BidiRtl,BidiLtr,Styles,Blockquote,CreateDiv,PageBreak,Print,Preview,Maximize';
			
			$('.cls_text').ckeditor(config2);
		});	
		
	</script>
	<div align="center">
		<br/>
		<!--button type="button" onclick="javascript:history.back();" class="input-default" style="height:25px;">Voltar</button>
		<button type="button" onclick="gerar_texto();" 	class="input-default" style="height:25px;">Gerar</button-->
		<input type="submit" value="Unir Parágrafos" onclick="EnviarDados('index.php','3','');" class="input-default" style="height: 30px; cursor:pointer;" />
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
	$Qcodset =  mysqli_query($conexao1,"SELECT s.cod_setor FROM tp_tipo_tb AS t JOIN tp_setor_tb AS s ON s.id_setor=t.id_setor WHERE t.tipo_id = '$tipo_tb' ");
	$Wcodset = mysqli_fetch_array($Qcodset);
	
	$nomepet = $Wcodset['cod_setor'];
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