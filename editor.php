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
<script language="javascript">	
var myfocus = 0;
var mystart = 0;
$(function (){
	CKEDITOR.config.skin = 'moono-lisa2';
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
    var editor = CKEDITOR.replace( 'name_text', {
		extraPlugins: 'autogrow,myplugin,sharedspace,uploadimage',
		removePlugins: 'floatingspace,resize',
		sharedSpaces: {
			top: 'topSpace',
			bottom: 'bottomSpace'
		},
		//scayt_autoStartup : true,
		language: 'pt_BR',
		contentsCss : 'css/texto.css',	
	});

    CKFinder.setupCKEditor( editor, 'ckfinder/' ) ;
	
	CKEDITOR.on('instanceReady', function(evt) {
		var editor = evt.editor;
		editor.on('focus', function(e) {
			myfocus=1;
		});
		editor.on('blur', function(e) {
			myfocus=0;
		});
	});
	
	$('#scrlBotm').click(function (){
		$('html, body').animate({scrollTop: $(document).height()},1500);
		return false;
	});
	$('#scrlTop').click(function () {
		$('html, body').animate({scrollTop: '0px'},1500);
		return false;
	});
	
	$("#div_bottom").mouseover(function(){
		$("#div_bottom").fadeTo("slow", 1.0);
	});
	$("#div_bottom").mouseout(function(){
		$("#div_bottom").fadeTo("slow", 1.0);
	});
	$("#nomepet").mouseover(function(){
		$("#nomepet").val()=="Nome_do_Arquivo"?$("#nomepet").val(""):$("#nomepet").val();
	});
	$("#nomepet").mouseout(function(){
		$("#nomepet").val()==""?$("#nomepet").val("Nome_do_Arquivo"):$("#nomepet").val();
	});
});

$(document).ready(function(){

	$('#div_bottom').hide();
	
	$("body").css("color","#333");	
	$(function () {
		$(window).scroll(function () {
			if ($(this).scrollTop() > ($("#tb_editor").height()-800)) {
				//$("#div_bottom").fadeTo("slow", 0.8);
				$('#div_bottom').fadeIn();
			} else {
				$('#div_bottom').fadeOut();
			}
		});
	});
	
	fc_salvar_auto();
	
});

function ver_title(){
	var n = 0;
	$('.titulos').each(function(index){
		var n  = parseInt(n)+1;
		$("#topicT").append("<button type='button' id='bt_" + index + "' style='background:url(\"img/topicos.png\")no-repeat;color:#ffffff;width:55px;height:20px;font-size:6pt;margin-top:1px;text-align:left;' onclick='ver_topico(" + $(this).offset().top + "," + index + ");' title='" + $(this).text() + "'>" + $(this).text().substr(0,6) +  "..</button>");
		
		$("#bt_"+ index).fadeTo("slow", 0.6);
		
		$("#bt_"+ index).mouseover(function(){
			$("#bt_"+ index).fadeTo("slow", 1.0);
		});
		
		$("#bt_"+ index).mouseout(function(){
			$("#bt_"+ index).fadeTo("slow", 0.6);
		});
		
		$("#bt_"+ index).click(function(){
			$("#bt_"+ index).css("opacity", 1);
		});
	});
}

function ver_topico(valor,nume){
	$('html,body').animate({scrollTop: (valor)-50},'slow');
	var tags = $(".titulos").length;
	tags2 = (parseInt(tags)-1);
	
	if(nume==tags2){
		$("#botao_next").hide();
	}
	if(nume<tags2){
		$("#botao_next").show();
	}
	if(nume>0){
		$("#botao_prev").show();
	}
	if(nume<1){
		$("#botao_prev").hide();
	}
	$("#id_topicos").val(parseInt(nume)+1);
}

function goToByScroll(id,num,par){
	var tags = $(".titulos").length;
	if(par==1){
		$('.titulos').each(function(index){
			alert(par);
			if(index==num){
				$('html,body').animate({scrollTop: ($(this).offset().top)-50},'slow');
			}
		});
		num = parseInt(num) + 1;
	}
	if(par==0){
		num = parseInt(num) - 1;
		$('.titulos').each(function(index){
			if(index==(parseInt(num) - 1)){
				$('html,body').animate({scrollTop: ($(this).offset().top)-50},'slow');
			}
		});
	}
	
	if(num==tags){
		$("#botao_next").hide();
	}
	if(num<tags){
		$("#botao_next").show();
	}
	if(num>0){
		$("#botao_prev").show();
	}
	if(num==1){
		$("#botao_prev").hide();
	}
	
	$("#id_topicos").val(num);
}

$(window).load(function(){
	ver_title();
	$("#ger_rtf").attr("disabled",true);
	$("#ger_pdf").attr("disabled",true);
	$("#ger_rtf").css("background","url('img/doc-c.png') no-repeat");
	$("#ger_pdf").css("background","url('img/pdf-c.png') no-repeat");
})

function fc_focus(valor){
	$("#"+valor).focus();
}
function replaceAll(string, token, newtoken) {
	while (string.indexOf(token) != -1) {
 		string = string.replace(token, newtoken);
	}
	return string;
}

function fc_salvar_pet(valor){
	
	for ( instance in CKEDITOR.instances )
    CKEDITOR.instances[instance].updateElement();
	
	$("#ger_sav").attr("disabled",true);	
	$("#ger_sav").css("background","url('img/progress.gif') no-repeat");
	var name_text = $("#name_text").val();
	name_text = replaceAll(name_text,"&","_|_");
	// alert($("#name_text").val());
	
	
	
	$.ajax({
	   type: "POST",
	   url:  "inc/getsav.php",
	   data: "flag="+ $("#id_sav").attr("flag") + 
			 "&id_pecas="  + $("#id_sav").val() +
			 "&tipo_id="   + $("#tipo_id").val()+ 
			 "&nomepet="   + $("#nomepet").val()+
			 "&codsav="	   + $("#codsav").val()	+ 
			 "&name_text=" + name_text,
			
	   success: function(retorno_ajax){
			
			$("#ger_sav").css("background","url('img/salvar_ok.png') no-repeat");
			$("#id_sav").val(retorno_ajax);
			$("#id_sav").attr("flag",2);
			$("#ger_sav").attr("disabled",false);
			$("#ger_rtf").attr("disabled",false);
			$("#ger_pdf").attr("disabled",false);
			$("#ger_rtf").css("background","url('img/doc.png') no-repeat");
			$("#ger_pdf").css("background","url('img/pdf.png') no-repeat");
			$("#ger_rtf").css("cursor","pointer");
			$("#ger_pdf").css("cursor","pointer");
		}
	});
	if(valor==1){
		mystart=1;
	}
}


function fc_salvar_auto(){
	setTimeout(function(){ 
		if(myfocus==1){
			if(mystart==1){
				fc_salvar_pet(0);
			}
		}
		fc_salvar_auto(); 
	}, 10000);
}

</script>	
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


