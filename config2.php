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

?>
<script language="javascript">

var str_retorno_ajax = "<?php echo $campos; ?>";

$(function() {
	$( "#accordion" )
		.accordion({
			autoHeight: false,
			navigation: true,
			header: "> div > h3"
		})
	$('input:text').setMask();
});	

$(document).ready(function(){	
	$(".group .delete").click(function(){
		//$(this).parents(".group").animate({ opacity: 'hide' }, "slow");
		$(this).parents(".group").fadeOut('slow', function(){ $(this).remove();});
		//$j("#item-"+id).fadeOut('slow', function(){ $j(this).remove(); 
	});
});

function msgbox(msg, bts){
	var $dialog = $('<div></div>')
	.html(msg)
	.dialog({
		modal: true,
		autoOpen: true,
		buttons: bts,
		title: 'Alerta'
	});	
}

function del_parag(valor){
	msgbox("<br><table align='center'><tr><td>Deseja realmente deletar esse tópico?</td></tr></table>", {
		Sim: function() {
			$( this ).dialog( "close" );
			$.ajax({
			   type: "POST",
			   url:  "inc/ajax_parag.php",
			   data: "flag=D" + 
					 "&idvalor=" + valor,
					 
			   success: function(retorno_ajax){
					if(retorno_ajax =='OK'){
						msgbox("<br><table align='center'><tr><td> Input deletado com sucesso !</td></tr></table>", {
							Fechar: function() {
								$( this ).dialog( "close" );
								EnviarDados('index.php','6',$('#TIPOPET').val());
							}
						});
					}
				}
			});
		},	
		"Não": function() {
			$( this ).dialog( "close" );
		}
	});
}

function save_parag(id,valor1,valor2){
	// msgbox("<br><table align='center'><tr><td>Deseja realmente salvar esse tópico e parágrafos?</td></tr></table>", {
	// 	Sim: function() {
			$( this ).dialog( "close" );
			$.ajax({
			   type: "POST",
			   url:  "inc/ajax_parag.php",
			   data: "flag=" + valor2 + 
					 "&fund_id=" 	+ id + 
					 "&fund_text=" 	+ escape($(valor1).val()),
					 
			   success: function(retorno_ajax){
					if(retorno_ajax == 'OK'){
						// msgbox("<br><table align='center'><tr><td> Texto salvo com sucesso !</td></tr></table>", {
						// 	Fechar: function() {
						// 		$( this ).dialog( "close" );
						// 		//EnviarDados('index.php','6',$('#TIPOPET').val());
						// 	}
						// });
                        $(".cke_copyformatting_notification").after('<div class="cke_notifications_area" id="cke_notifications_area_input_cabec" style="z-index: 9998; float:left; position: absolute; top: 248px; left: 50%; margin-left: -158px"><div class="cke_notification cke_notification_info" id="cke-e51ba611e23782e3bc366a32ec27899e0" role="alert" aria-label="info"><p class="cke_notification_message">Salvo com sucesso!</p><a class="cke_notification_close" href="javascript:void(0)" onclick="javascript:$(\'#cke_notifications_area_input_cabec\').remove();" title="Fermer" role="button" tabindex="-1"><span class="cke_label">X</span></a></div></div>');
                        setTimeout(function (){
                            $("#cke_notifications_area_input_cabec").remove();
                        },1000);
					}
				}
			});
		// },
		// "Não": function() {
		// 	$( this ).dialog( "close" );
		// }
	//});
	
}


function novo_parag(){
	$( "#dialog_parag" ).dialog({
		modal: true,
		autoOpen: true,
		close: function(){
			
		},
		buttons: {
			Salvar: function() {
				$.ajax({
				   type: "POST",
				   url:  "inc/ajax_parag.php",
				   data: "flag=I" + 
						 "&toptitle=" + escape($("#TOPTITLE").val()) +
						 "&tipo_id=" + escape($("#tipo_id").val()),
				   success: function(retorno_ajax){
						if(retorno_ajax =1){
							$( "#dialog_parag" ).dialog( "close" );
							msgbox("<br> Tópico criado com sucesso !", {
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','6',$('#TIPOPET').val());
								}
							});
						}
					}
				});
			},
			Sair: function() {
				$( this ).dialog( "close" );
			}
		}
	
		
	});
}	

</script>
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
					<a href="#" style="cursor: move;" ><?php echo $wtext['fund_titulo']; ?></a>
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
	<script language="javascript">
		
		$(function(){
			
			var config1 = {
				extraPlugins: 'autogrow,rodape,myplugin,sharedspace,cabecalho,rodape,assinatura',
				removePlugins: 'floatingspace,resize',
				sharedSpaces :
				{
					top : 'topSpace',
					bottom : 'bottomSpace'
				},
				contentsCss : 'css/texto.css',
				toolbar:
				[
					[ 'Bold','Italic','Underline','Strike' ],
					[ 'NumberedList','BulletedList','-','Outdent','Indent','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','insertTab'],
					[ 'Source','-','Save','NewPage','DocProps','Preview','Print' ],
					[ 'Format','Font','FontSize' ],
					[ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ],
					[ 'cabecalho','rodape','assinatura','Image','Table','HorizontalRule'],
					[ '-','-','-','Smiley','-','-','-']
				]
			};
			CKEDITOR.config.tabSpaces = 4;
			CKEDITOR.config.removePlugins = 'elementspath';
			$('.cls_text').ckeditor(config1);
			
			$( "#accordion" ).accordion({
				active: <?php echo $_POST['act_parag'] ? $_POST['act_parag'] : 0; ?> 
			});
		});	
	</script>
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


