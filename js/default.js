	
//var config = {
//	sharedSpaces :
//	{
//		top : 'topSpace',
//		bottom : 'bottomSpace'
//	},
//	skin:'v2',
//	removePlugins : 'maximize,resize',
//	extraPlugins : 'autogrow,insertTab',
//	removePlugins : 'resize',
//	scayt_autoStartup : true,
//	scayt_sLang : "pt_BR",
//	language: 'pt_BR',
//	contentsCss : 'css/texto.css',
//	toolbar:
//	[
//		[ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ],
//		[ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','insertTab','-','BidiLtr','BidiRtl' ],
//		[ 'Source','-','Save','NewPage','DocProps','Preview','Print' ],
//		[ 'Maximize', 'ShowBlocks' ],
//		[ 'Styles','Format','Font','FontSize' ],
//		[ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ],
//		[ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ],
//		[ 'TextColor','BGColor','showtime', 'myplugin' ]
//	]
//};

//Demo
	$(function() {
		
		$('input:text').setMask();
		//Autocomplete
		$("#BANCO").combobox({ source: "handler.ashx" });
		$("#FILIAL").combobox({ source: "handler.ashx" });
		$("#TCONTRATO").combobox({ source: "handler.ashx" });
		$("#TIPOACAO").combobox({ source: "handler.ashx" });
		$("#ADVOGADO").combobox({ source: "handler.ashx" });
		$("#PUBADV").combobox({ source: "handler.ashx" });
		//$("#TIPOPET").combobox();
		$("#sel_chave").combobox();
		
		//$('select').each... Para ativar as funções dos selects
		$('select').each(function(index,object) {
			$(object).load();
		});
		//$('select').each... Para ativar as funções dos selects
		$('input:text').each(function(index,object) {
			if($(this).attr("carregar")!=0){
				$(object).focus();
			}
			$(object).load();
		});
		
		$(".INPCHECK").click(function(){
			inputs_checkeds($(this).val());
		});
		
		$(".CKRETURN").click(function(){
			if($(this).val()=="Textarea"){
				$('#tdInputs').hide();
				$('#inputs').html('<div class="pInputs"><input type="text" class="slInputs input-default" value="SIM" style="width:203px;margin-top:5px;" my_val="1" readonly />&nbsp;<input type="text" class="slTextarea input-default" id="slTextarea_1" onfocus="fc_textarea(this,\'\',2);" style="width:203px"/></div>'+
								  '<div class="pInputs"><input type="text" class="slInputs input-default" value="NÃO" style="width:203px;margin-top:5px;" my_val="2" readonly />&nbsp;<input type="text" class="slTextarea input-default" id="slTextarea_2" onfocus="fc_textarea(this,\'\',2);" style="width:203px"/></div>').animate({ opacity: "show" }, "slow");
			}else{
				$('#inputs').html("");
				$('#tdInputs').show();
			}
		});
	});
	
function inputs_checkeds(valor){
	if(valor=="date"){
		$("#div_InFocu").html('<select id="inputFocu" onchange="$(\'#hinputFocu\').val(this.value);" class="input-default" style="width:195px; height:20px; border:1px solid #ccc;background:#fff"><option value=""></option><option value="data_atual(this);">Data atual</option><option value="data_extenso_out(this);">Data por extenso</option><option value="diasemana(this);">Dia da semana</option></select>');
		$("#div_InLoad").html('<select id="inputLoad" onchange="$(\'#hinputLoad\').val(this.value);" class="input-default" style="width:195px; height:20px; border:1px solid #ccc;background:#fff"><option value=""></option><option value="data_atual(this);">Data atual</option><option value="data_extenso_out(this);">Data por extenso</option><option value="diasemana(this);">Dia da semana</option></select>');
		$("#div_InBlur").html('<select id="inputBlur" onchange="$(\'#hinputBlur\').val(this.value);" class="input-default" style="width:195px; height:20px; border:1px solid #ccc;background:#fff"><option value=""></option><option value="data_atual(this);">Data atual</option><option value="data_extenso_out(this);">Data por extenso</option><option value="diasemana(this);">Dia da semana</option></select>');
	}else if(valor=="decimal"){
		$("#div_InFocu").html('<select id="inputFocu" onchange="$(\'#hinputFocu\').val(this.value);" class="input-default" style="width:195px; height:20px; border:1px solid #ccc;background:#fff"><option value=""></option><option value="fc_newstring(this);">Valor por extenso</option></select>');
		$("#div_InLoad").html('<select id="inputLoad" onchange="$(\'#hinputLoad\').val(this.value);" class="input-default" style="width:195px; height:20px; border:1px solid #ccc;background:#fff"><option value=""></option><option value="fc_newstring(this);">Valor por extenso</option></select>');
		$("#div_InBlur").html('<select id="inputBlur" onchange="$(\'#hinputBlur\').val(this.value);" class="input-default" style="width:195px; height:20px; border:1px solid #ccc;background:#fff"><option value=""></option><option value="fc_newstring(this);">Valor por extenso</option></select>');
	}else{
		$("#div_InFocu").html('<input type="text" id="inputFocu" onBlur="$(\'#hinputFocu\').val(this.value);" class="input-default" style="width:191px; height:16px; border:1px solid #ccc;background:#fff"/>');
		$("#div_InLoad").html('<input type="text" id="inputLoad" onBlur="$(\'#hinputLoad\').val(this.value);" class="input-default" style="width:191px; height:16px; border:1px solid #ccc;background:#fff"/>');
		$("#div_InBlur").html('<input type="text" id="inputBlur" onBlur="$(\'#hinputBlur\').val(this.value);" class="input-default" style="width:191px; height:16px; border:1px solid #ccc;background:#fff"/>');
	}
}
	//Autocomplete
	(function( $ ) {
		$.widget( "ui.combobox", {
			_create: function() {
				var input,
					self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "",
					wrapper = $( "<span>" )
						.addClass( "ui-combobox" )
						.insertAfter( select );

				input = $( "<input>" )
					.appendTo( wrapper )
					.val( value )
					.addClass( "ui-state-default" )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function( request, response ) {
							var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
							response( select.children( "option" ).map(function() {
								var text = $( this ).text();
								if ( this.value && ( !request.term || matcher.test(text) ) )
									return {
										label: text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												$.ui.autocomplete.escapeRegex(request.term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<strong>$1</strong>" ),
										value: text,
										option: this
									};
							}) );
						},
						select: function( event, ui ) {
							ui.item.option.selected = true;
							self._trigger( "selected", event, {
								item: ui.item.option
							});
							//Saindo do campo para atualizar
							$(this).blur();
							select.focus();
						},
						change: function( event, ui ) {
						//select.focus();
							if ( !ui.item ) {
								var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
									valid = false;
								select.children( "option" ).each(function() {
									if ( $( this ).text().match( matcher ) ) {
										this.selected = valid = true;
										return false;
									}
								});
								if ( !valid ) {
									// remove invalid value, as it didn't match anything
									$( this ).val( "" );
									select.val( "" );
									input.data( "autocomplete" ).term = "";
									return false;
								} 
							}
						}
					})
					.addClass( "ui-widget ui-widget-content ui-corner-left" );

				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label + "</a>" )
						.appendTo( ul );
				};

				$( "<a class='ui-button2'>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Todos os ítens" )
					.appendTo( wrapper )
					.button({
						icons: {
							primary: "ui-icon-triangle-1-s"
						},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.addClass( "ui-corner-right ui-button-icon" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						$( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},

			destroy: function() {
				this.wrapper.remove();
				this.element.show();
				$.Widget.prototype.destroy.call( this );
			}
		});
	})( jQuery );
	
	var cls_text = "";
	var fund_id = "";
	
//////////////////////////////////////////////

//Demo2
	function EnviarDados(form,hid,pet){
			$("#hid_enviar").val(hid);
			$("#TIPOPET").val(pet);
			
			var sim = '';
			if(hid==2){		
				$('.input-default').each(function(index,object) {
					if($(object).attr("obrigatorio")==2 && $(object).val()==""){

						msgbox("<br><table align='center'><tr><td> Campo <b>" + $(object).attr("descricao") + "</b> é obrigatório</td></tr></table>", {
							Fechar: function() {
								$( this ).dialog( "close" );
								$(object).focus();
							}
						});
						sim = 1;
						return false;
					}
				});
			}
			
			if(sim==''){
				document.form_iniciais.action=form;	
				document.form_iniciais.submit();
			}
	}
	
	function PetiDados(valor1,valor2,valor3,valor4,valor5,valor6){
		
		$("#id_pecas").val(valor3);
		$("#tipo_id").val(valor4);
		$("#nomepet").val(valor5);
		$("#nomecli").val(valor6);

		EnviarDados(valor1,valor2,valor4);
	}
	
	function mark_edit(valor1,valor2){
			
		$('.clspet').each(function(index,object) {
			if($(object).attr("grupo")==1){
				if(valor2==1){
					msgbox("<br><table align='center'><tr><td style='font-size:8pt'>Deseja deletar o modelo: <b>" + $(object).text() + "</b> ?</td></tr></table><br>",{
						"Sim": function(){
							$.ajax({
								type: "POST",
								//url:  "inc/ajax_parag.php",
								url:  "inc/ajax_tipo.php",
								data: "flag=DT&tipoid=" + $(object).attr("numpet"),
								success: function(retorno_ajax){
									$( this ).dialog( "close" );
									if(retorno_ajax=="OK"){
										msgbox("<br><table align='center'><tr><td>Modelo deletado com sucesso !</td></tr></table><br>",{
											Fechar: function(){
												$( this ).dialog( "close" );
												EnviarDados('index.php','5','');
											}
										});
									}else{
										alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
									}
								}
							});
						},
						"Não": function(){
							$( this ).dialog( "close" );
						}
					});
					
				} else {	
					EnviarDados('index.php',valor1,$(object).attr("numpet"));
				}
			}
		});
	}
	function mark_active(valor){
		
		$('.clspet').each(function(index,object) {
			if($(object).attr("numpet")==$(valor).attr("numpet")){
				if($(object).attr("grupo")==0){
					$(object).attr("grupo",1);
					$(".cpanel-right-sub").show();				
					$(valor).css("border","1px solid red");
					mark_css(object,1);
				}else{
					$(object).attr("grupo",0);
					mark_css(object,0);
					$(valor).css("border","1px solid #ccc");
					$(".cpanel-right-sub").hide();
				}
			} else {
				$(object).attr("grupo",0);
				mark_css(object,0);
			}
			
		});
		
	}
	
	function mark_css(valor,valor2){
		if(valor2==1){
		$(valor).css("background-position", 0);
			$(valor).css("-webkit-border-bottom-left-radius","50% 20px");
			$(valor).css("-moz-border-radius-bottomleft","50% 20px");
			$(valor).css("border-bottom-left-radius","50% 20px");
			$(valor).css("-webkit-box-shadow","-5px 10px 15px rgba(0, 0, 0, 0.25)");
			$(valor).css("-moz-box-shadow","-5px 10px 15px rgba(0, 0, 0, 0.25)");
			$(valor).css("box-shadow","-5px 10px 15px rgba(0, 0, 0, 0.25)");
			$(valor).css("position","relative");
			$(valor).css("z-index","10");
		} else {
			$(valor).css("background-color"," #fff");
			$(valor).css("background-position"," -30px");
			$(valor).css("display"," block");
			$(valor).css("float"," left");
			//$(valor).css("height"," 97px");
			//$(valor).css("width"," 108px");
			$(valor).css("color"," #565656");
			$(valor).css("vertical-align"," middle");
			$(valor).css("text-decoration"," none");
			$(valor).css("border"," 1px solid #CCC");
			$(valor).css("-webkit-border-radius"," 5px");
			$(valor).css("-moz-border-radius"," 5px");
			$(valor).css("border-radius"," 5px");
			$(valor).css("-webkit-transition-property","background-position,-webkit-border-bottom-left-radius,-webkit-box-shadow");
			$(valor).css("-moz-transition-property","background-position,-moz-border-radius-bottomleft,-moz-box-shadow");
			$(valor).css("-webkit-transition-duration","0.8s");
			$(valor).css("-moz-transition-duration","0.8s");
			$(valor).css("box-shadow","");
			$(valor).css("-webkit-box-shadow","");
			$(valor).css("-moz-box-shadow","");
			$(valor).css("box-shadow","");
		}
	}
	
	function fc_ajax_comp(tabela,campo0,input0,unir,id_ref,id_val,conex){
		var str = "";
		
		$(id_val).each(function(){
			str = $(this).find('option:selected').attr('ident');
		});
		
		$.ajax({
			type: "POST",
			url : "inc/ajax_comp.php",
			data: "flag=y" + 
				  "&tabela=" + tabela +
			      "&campo0=" + campo0 +
			      "&id_ref=" + id_ref +
			      "&id_val=" + str	  +
			      "&conex="  + conex,
				  
			success: function(x){
			
				var quebra="";
				var iSinput="";
				var iSunir="";
				var a = "";
				var b = "";
				quebra=x.split("_|_");
				iSinput=input0.split("|_|");
				for(a in quebra){
					if(quebra[a] && unir != 'unir'){
						$("#"+iSinput[a]).val(quebra[a]);
					} else {
						//iSunir += (quebra[a] ? quebra[a] + ', ' : '');
						iSunir += (quebra[a] ? quebra[a] + '' : '');
					}
				}
				if(unir=='unir'){
					for(b in iSinput){
						$("#"+iSinput[b]).val(iSunir);
						//$("#"+iSinput[b]).val($("#"+iSinput[b]).val().replace(", , undefined, ",""));
						$("#"+iSinput[b]).val($("#"+iSinput[b]).val().replace(", , ",""));
					}
				}
			}
		});
	}
	
	function fc_ajax_dado_1(id_val){
		var str = "";
		$(id_val).each(function(){
			str = $(this).find('option:selected').attr('ident');
		});
		return (str);
	}
	
	//Valor1 = I p/ Novo Campo OU valor1= E p/Editar Campo 
	function fc_inputs(valor1,valor2,valor3){
		
		var campoId="";
		valor1=="U"?campoId=valor2:"";
		$( "#dialog_inputs" ).dialog({
			title: valor1=="I"?"Novo Campo":valor1=="U"?"Editar Campo": "",
			modal: true,
			position:['middle',145],
			autoOpen: true,
			height: 440,
			width: 500,
			close: function() {
				
			},
			buttons: {
				Salvar: function() {
					var dadInp = '';
					var dadI = '';
					if($(".SELEINPUT:checked").val()=='TIPOINP'){
						dadInp = "&inpcheck=" + $(".INPCHECK:checked").val();
					}else if($(".SELEINPUT:checked").val()=='TIPOSEL'){
						$('.slInputs').each(function() {
							dadI += $(this).val() + "-|-" + $("#slTextarea_"+$(this).attr("my_val")).val() + "_|_";
						});
						dadInp = "&dadI=" + escape(dadI) +"&ckreturn="+$(".CKRETURN:checked").val();
					}
					
					$.ajax({
					   type: "POST",
					   url:  "inc/ajax_input.php",
					   data: "flag=" 	  	+ valor1 +
							 "&inptitle=" 	+ escape($("#INPTITLE").val()) 	+ 
							 "&inppre="   	+ escape($("#INPTITLE_PRE").val()) 	+ 
							 "&inppos="   	+ escape($("#INPTITLE_POS").val()) 	+ 
							 "&tipopet="  	+ escape($("#TIPOPET").val())		+ 
							 "&db_col="	  	+ escape($("#db_col").val())		+ 
							 "&inputcol=" 	+ escape($("#inputcol").val())	+ 
							 "&inputrol=" 	+ escape($("#inputrol").val())	+ 
							 "&inputReq=" 	+ escape($("#inputReq").val())	+ 
							 "&inputLoad=" 	+ encodeURIComponent($("#inputLoad").val())	+ 
							 "&inputFocu=" 	+ encodeURIComponent($("#inputFocu").val())	+ 
							 "&inputBlur=" 	+ encodeURIComponent($("#inputBlur").val())	+ 
							 "&inputOrdn=" 	+ encodeURIComponent($("#inputOrdn").val())	+ 
							 "&tbBase="   	+ escape($("#tbBase").val())		+ 
							 "&inputArqui="	+ $("#inputArqui").attr("checked")	+ 
							 "&dadSel="   	+ $(".SELEINPUT:checked").val()	+ dadInp +
							 "&campoId="  	+ campoId,
							 
					   success: function(retorno_ajax){
							if(retorno_ajax==1){
								$( "#dialog_inputs" ).dialog( "close" );
								if(valor3==1){
									$.ajax({
										type: "POST",
										url:  "inc/val_ajax.php",
										data: "flag=1",
										success: function(retorno_inp){
											$(".cke_button__smiley").each(function(){
												if($(this).is(":visible")==true){
													$(this).click();
													parseInt($("#inputOrdn").val())+1;
												}
											});
											var dd_sel=$(".cke_dialog_ui_select select").html()+"<option  value=\""+retorno_inp+"\" selected>"+$("#INPTITLE").val()+"</option>";
											$(".cke_dialog_ui_select select").html(dd_sel);
											
											//RESETANDO OS DADOS 
											$("#INPTITLE").val("");
											$("#INPTITLE_PRE").val(""); 
											$("#INPTITLE_POS").val(""); 
											$("#db_col").val(""); 
											$("#inputcol:checked").val(1); 
											$("#inputrol:checked").val(0); 
											$("#inputReq:checked").val(); 
											$("#inputLoad").val("");
											$("#inputFocu").val("");
											$("#inputBlur").val("");
											$("#tbBase:checked").val("");
											$(".SELEINPUT:checked").val("TIPOINP");
											$(".INPCHECK:checked").val("");
											$("#inputArqui").attr("checked",false);
										}
									});
								}else{
									msgbox(valor1=="I"?"<br><table align='center'><tr><td>Campo criado com sucesso !</td></tr></table><br>":"<br><table align='center'><tr><td>Campo editado com sucesso !</td></tr></table><br>", {
										Fechar: function(){
											$( this ).dialog( "close" );
											EnviarDados('index.php','7',$('#TIPOPET').val());
										}
									});
								}
							}else if(retorno_ajax==2){
								alert("Campo já existente!");
							}else{
								alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
							}
						}
					});
					
				},
				Sair: function() {
					$( this ).dialog( "close" );
				}
			}			
		});
		//carregar campos existentes para editar inputs
		$.ajax({
				   type: "POST",
				   url:  "inc/ajax_input.php",
				   data: "flag=E&campoId="  + campoId,
			success: function(retorno_ajax){
				var ret = retorno_ajax.split("-|-");
				$("#INPTITLE_PRE").val(ret[2]);
				$("#INPTITLE_POS").val(ret[3]);
				$("#INPTITLE").val(ret[4]);
				$("#db_col").val(ret[7]);
				$(".SELEINPUT[TIPO="+ret[5]+"]").attr("checked","true");
				$(".SELEINPUT[TIPO="+ret[5]+"]").click();
				if(ret[5]=="SELECT"){
					$.ajax({
						type: "POST",
						url:  "inc/ajax_select.php",
						data: "flag=S&campoId="  + campoId,
						success: function(retorno_sel){
							var nsel = retorno_sel.split("-|-");
							var nhtml = "";
							for(a in nsel){
								if(nsel[a]!=""){
									nhtml += '<p style="display: block;"><input type="text" class="slInputs input-default" value="'+nsel[a]+'" style="width:220px"></p>';
								}
							}
							$("#inputs").html(nhtml);
						}
					});
				}
				$(".INPCHECK[INALT="+ret[8]+"]").attr("checked","true");
				$(".INPCHECK[INALT="+ret[8]+"]").click();
				$("#inputcol").val(ret[10]);
				$("#inputrol").val(ret[11]);
				$("#inputReq").val(ret[16]);
				$("#inputLoad").val(ret[13]);
				$("#inputFocu ").val(ret[12]);
				$("#inputBlur").val(ret[14]);
				$("#inputOrdn").val(ret[17]);
				if(ret[19]=="Y"){
					$("#inputArqui").attr("checked",true);
				}else{
					$("#inputArqui").attr("checked",false);
				}
			}
		});
	}
	//função editar usuário
	function fc_edit_usu(valor1,valor2){
		//alert(1);
		
		var tt = "";
		var tu = "";
		if(valor2=="I"){
			tt="Novo Usuário";
			tu="criado";
			$(".validateTips").text("Crie Um " + tt);
		}else if(valor2=="U"){
			tt="Editar Usuário";
			tu="editado";
			$(".validateTips").text("Edite o Usuário Abaixo");
		}
	
		$.ajax({
			type: "POST",
			url:  "inc/ajax_usu.php",
			data: "flag=E&id_usu=" + valor1,
			success: function(retorno_ajax){
				var ret = retorno_ajax.split("-|-");
				
				$("#id_usu").val(ret[0]);
				$("#nome_usu").val(ret[2]);
				$("#login_usu").val(ret[3]);
				$("#email_usu").val(ret[5]);
				$("#nivel_usu").val(ret[6]);
				$("#setor_usu option[value="+ret[9]+"]").attr("selected","selected");
				//$("#cliente_id option[value="+ret[10]+"]").attr("selected","selected");
				$("#status_usu").val(ret[11]);
				$("#dialog-edit-usu").dialog({
					title: tt,
					modal: true,
					autoOpen: true,
					height: 440,
					width: 450,
					close: function(){ 
						$('.cls_usu').each(function() {
							$(this).val("");
						});
					},
					buttons: {
						Salvar: function() {
							var mdados="";
							$('.cls_usu').each(function(){
								if($(this).val()=="" && $(this).attr("obrigatorio")=="1"){
									alert("O campo " + $(this).attr("title") + " é obrigatório ");
									$(this).focus();
									return false;
								}
								mdados += $(this).attr("name")+"="+escape($(this).val())+"&";
							});
							///pega os clientes///////////
							var usus="";
							var numes=0;
							$('.cls_usu2').each(function(){
								if((numes++)>0){
									usus += ",";
								}
								usus += escape($(this).val());
							});
							var dado_email = validaEmail($("#email_usu").val());
							var dado_senha = fc_teste_senha($("#senha_usu1").val(),$("#senha_usu2").val(),valor2);
							if(dado_email!=""){
								alert(dado_email);
							}else if(dado_senha!=""){
								alert(dado_senha);
							}else{
								$.ajax({
								   type: "POST",
								   url:  "inc/ajax_usu.php",
								   data: "flag=" + valor2 + "&" + mdados + "&banco_neo=" + usus,
								   success: function(retorno_ajax){
										if(retorno_ajax==1){
											$( "#dialog-edit-usu" ).dialog( "close" );
											msgbox(valor2=="I"?"<br><table align='center'><tr><td>Usuário " + tu + " com sucesso !</td></tr></table><br>":"<br><table align='center'><tr><td>Campo editado com sucesso !</td></tr></table><br>", {
												Fechar: function(){
													$( this ).dialog( "close" );
													EnviarDados('index.php','8','');
												}
											});
										}else if(retorno_ajax==2){
											alert("Usuário já cadastrado!");
										}else{
											alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
										}
									}
								});
							}
						},
						Sair: function() {
							$( this ).dialog( "close" );
							$('.cls_usu').each(function() {
								$(this).val("");
							});
						}
					}
				});
				
				//alert($("#nivel_usu").find("option[value='USU']").attr("selected","selected"));
			}
		});
	}
	//Editar os servidores
	function fc_edit_sql(valor1,valor2){
		
		var tt = "";
		var tu = "";
		if(valor2=="I"){
			tt="Novo Servidor";
			tu="criado";
			$(".validateTips").text("Crie Um " + tt);
		}else if(valor2=="U"){
			tt="Editar Servidor";
			tu="editado";
			$(".validateTips").text("Edite o Servidor Abaixo");
		}
	
		$.ajax({
			type: "POST",
			url:  "inc/ajax_sql.php",
			data: "flag=E&id_db=" + valor1,
			success: function(retorno_ajax){
				var ret = retorno_ajax.split("-|-");
				//alert(ret[1]);
				$("#id_db").val(ret[0]);
				$("#nome_db").val(ret[1]);
				$("#ip_db").val(ret[2]);
				$("#data_db").val(ret[3]);
				$("#usu_db").val(ret[4]);
				$("#senha_db").val(ret[5]);
				$("#table_db").val(ret[6]);
				$("#chave_db").val(ret[7]);
				$("#query_db").val(ret[8]);
				$("#where_db").val(ret[9]);
				$("#stt").val(ret[10]);
				$( "#dialog-edit-sql" ).dialog({
					title: tt,
					modal: true,
					autoOpen: true,
					height: 420,
					width: 460,
					close: function(){ 
						$('.cls_usu').each(function() {
							$(this).val("");
						});
					},
					buttons: {
						Salvar: function() {
							var mdados="";
							$('.cls_usu').each(function(){
								if($(this).val()=="" && $(this).attr("obrigatorio")=="1"){
									alert("O campo " + $(this).attr("title") + " é obrigatório ");
									$(this).focus();
									return false;
								}
								mdados += $(this).attr("name")+"="+escape($(this).val())+"&";
							});
							
							$.ajax({
							   type: "POST",
							   url:  "inc/ajax_sql.php",
							   data: "flag=" + valor2 + "&" + mdados,
							   success: function(retorno_ajax){
									if(retorno_ajax==1){
										$( "#dialog-edit-sql" ).dialog( "close" );
										msgbox(valor2=="I"?"<br><table align='center'><tr><td>Servidor " + tu + " com sucesso !</td></tr></table><br>":"<br><table align='center'><tr><td>Servidor editado com sucesso !</td></tr></table><br>", {
											Fechar: function(){
												$( this ).dialog( "close" );
												EnviarDados('index.php','11','');
											}
										});
									}else if(retorno_ajax==2){
										alert("Servidor já cadastrado!");
									}else{
										alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
									}
								}
							});
							
						},
						Sair: function() {
							$( this ).dialog( "close" );
							$('.cls_usu').each(function() {
								$(this).val("");
							});
						}
					}
				});
				
				//alert($("#nivel_usu").find("option[value='USU']").attr("selected","selected"));
			}
		});
	}
	//Editar as listas
	function fc_edit_list(valor1,valor2){
		
		var tt = "";
		var tu = "";
		if(valor2=="I"){
			tt="Nova Lista";
			tu="criado";
			$(".validateTips").text("Crie Um " + tt);
		}else if(valor2=="U"){
			tt="Editar Lista";
			tu="editado";
			$(".validateTips").text("Edite a lista abaixo");
		}
	
		$.ajax({
			type: "POST",
			url:  "inc/ajax_list.php",
			data: "flag=E&id_lista=" + valor1,
			success: function(retorno_ajax){
				
				$("#return_lista").html(retorno_ajax);
				var dh = 440;
				var i = $('input').size() + 1;
				$( "#dialog-edit-list" ).dialog({
					title: tt,
					modal: true,
					autoOpen: true,
					height: 420,
					width: '95%',
					close: function(){ 
						$('.cls_list').each(function() {
							$(this).val("");
						});
					},
					buttons: {
						Add: function(){
							dh = dh + 20;
							$('<tr class="slInputs">'+
								'<td><input type="text" class="cls_list" name="id_grupo" id="id_grupo" value="'+$("#num_grupo").val()+'" title="Id do grupo" style="margin:0;width:30px" /></td>' +
								'<td><input type="text" class="cls_list" name="nome_lista" id="nome_lista" value="" title="Nome da lista" style="margin:0;width:100px" /></td>'+
								'<td><input type="text" class="cls_list" name="return_1" id="return_1" value="" title="return_1" style="margin:0;width:194px" /></td>'+
								'<td><input type="text" class="cls_list" name="return_2" id="return_2" value="" title="return_2" style="margin:0;width:194px" /></td>'+
								'<td><input type="text" class="cls_list" name="return_3" id="return_3" value="" title="return_3" style="margin:0;width:194px"/></td>'+
								'<td><input type="text" class="cls_list" name="return_4" id="return_4" value="" title="return_4" style="margin:0;width:194px"/></td>'+
								'<td><input type="text" class="cls_list" name="return_5" id="return_5" value="" title="return_5" style="margin:0;width:194px"/></td>'+
								'<td><input type="text" class="cls_list" name="return_6" id="return_6" value="" title="return_6" style="margin:0;width:194px"/></td>'+
								'<td><input type="text" class="cls_list" name="id_setor" id="id_setor" value="1" title="Setor" style="margin:0;width:60px;text-align:center"/></td></tr>').animate({ opacity: "show" }, "slow").appendTo('#inputs2');
							i++;
						},
						Remove: function(){
							if(i > 2) {
								dh = dh - (dh>440 ? 20 : 0);
								$('.slInputs:last').animate({opacity:"hide"}, "slow").remove();
								i--;
							}
						},
						Excluir: function(){
							while(i > 2) {
								$('.slInputs:last').remove();
								i--;
							}
						},
						Salvar: function() {
							var mdados="";
							var n = 0;
							
							if($("#nome_grupo").val()==""){
								alert("O campo " + $("#nome_grupo").attr("title") + " é obrigatório ");
								$("#nome_grupo").focus();
								return false;
							}
							$('.cls_list').each(function(){
								n++;
								mdados += "listas_"+n+"="+ $(this).attr("name")+"-|-"+escape($(this).val())+"&";
								
								if($(this).attr("name")=="nome_lista" && $(this).val()==""){
									msgbox("<div style='text-align:center;margin-top:30px;font-size:10pt'>O campo 'Nome' não pode ficar vazio!</div>", {
										Fechar: function(){
											$( this ).dialog( "close" );
										}
									});
									$(this).focus();
									n=10000;
									return false;
								}
							});
							if(n==10000){
								return false;
							}
							if(n==0){
								msgbox("<div style='text-align:center;margin-top:30px;font-size:10pt'>Você tem que ao menos criar uma linha!</div>", {
									Fechar: function(){
										$( this ).dialog( "close" );
									}
								});
								return false;
							}
							$.ajax({
								type: "POST",
								url:  "inc/ajax_list_edit.php",
								data: "flag=" + valor2 + "&num_grupo="+$("#num_grupo").val()+"&novo_grupo="+$("#novo_grupo").val()+"&nome_grupo="+$("#nome_grupo").val()+"&num_list="+n+"&" + mdados,
								success: function(retorno_ajax){
									if(retorno_ajax==1){
										$( "#dialog-edit-list" ).dialog( "close" );
										msgbox(valor2=="I"?"<br><table align='center'><tr><td>Servidor " + tu + " com sucesso !</td></tr></table><br>":"<br><table align='center'><tr><td>Servidor editado com sucesso !</td></tr></table><br>", {
											Fechar: function(){
												$( this ).dialog( "close" );
												EnviarDados('index.php','12','');
											}
										});
									}else if(retorno_ajax!=1){
										msgbox("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)", {
											Fechar: function(){
												$( this ).dialog( "close" );
												EnviarDados('index.php','12','');
											}
										});
									}
								}
							});
						},
						Sair: function() {
							$( this ).dialog( "close" );
							$('.cls_list').each(function() {
								$(this).val("");
							});
						}
					}
				});
			}
		});
	}
	//função editar setores
	function fc_edit_setor(valor1,valor2){
		
		var tt = "";
		var tu = "";
		if(valor2=="I"){
			tt="Novo Setor";
			tu="criado";
			$(".validateTips").text("Crie Um " + tt);
		}else if(valor2=="U"){
			tt="Editar Setor";
			tu="editado";
			$(".validateTips").text("Edite o Setor Abaixo");
		}
	
		$.ajax({
			type: "POST",
			url:  "inc/ajax_setor.php",
			data: "flag=E&id_setor=" + valor1,
			success: function(retorno_ajax){
				var ret = retorno_ajax.split("-|-");
				//alert(ret[1]);
				$("#id_setor").val(ret[0]);
				$("#nome_setor").val(ret[1]);
				
				$( "#dialog-edit-setor" ).dialog({
					title: tt,
					modal: true,
					autoOpen: true,
					height: 440,
					width: 450,
					close: function(){ 
						$('.cls_setor').each(function() {
							$(this).val("");
						});
					},
					buttons: {
						Salvar: function() {
							var mdados="";
							$('.cls_setor').each(function(){
								if($(this).val()=="" && $(this).attr("obrigatorio")=="1"){
									alert("O campo " + $(this).attr("title") + " é obrigatório ");
									$(this).focus();
									return false;
								}
								mdados += $(this).attr("name")+"="+escape($(this).val())+"&";
							});
							
							$.ajax({
							   type: "POST",
							   url:  "inc/ajax_setor.php",
							   data: "flag=" + valor2 + "&" + mdados,
							   success: function(retorno_ajax){
									if(retorno_ajax==1){
										$( "#dialog-edit-setor" ).dialog( "close" );
										msgbox(valor2=="I"?"<br><table align='center'><tr><td>Setor " + tu + " com sucesso !</td></tr></table><br>":"<br><table align='center'><tr><td>Campo editado com sucesso !</td></tr></table><br>", {
											Fechar: function(){
												$( this ).dialog( "close" );
												EnviarDados('index.php','9','');
											}
										});
									}else if(retorno_ajax==2){
										alert("Setor já cadastrado!");
									}else{
										alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
									}
								}
							});
							
						},
						Sair: function() {
							$( this ).dialog( "close" );
							$('.cls_setor').each(function() {
								$(this).val("");
							});
						}
					}
				});
				
				//alert($("#nivel_usu").find("option[value='USU']").attr("selected","selected"));
			}
		});
	}
	
	function fc_edit_cliente(valor1,valor2){
		
		var tt = "";
		var tu = "";
		if(valor2=="I"){
			tt="Novo Cliente";
			tu="criado";
			$(".validateTips").text("Crie Um " + tt);
		}else if(valor2=="U"){
			tt="Editar Cliente";
			tu="editado";
			$(".validateTips").text("Edite o Cliente Abaixo");
		}
	
		$.ajax({
			type: "POST",
			url:  "inc/ajax_cliente.php",
			data: "flag=E&cliente_id=" + valor1,
			success: function(retorno_ajax){
				var ret = retorno_ajax.split("-|-");
				//alert(ret[1]);
				//$("#cliente_id").val(ret[0]);
				$("#cliente_name").val(ret[1]);
				$("#cliente_cod").val(ret[2]);
				$("#cliente_area option[value="+ret[4]+"]").attr("selected","selected");
				
				$( "#dialog-edit-cliente" ).dialog({
					title: tt,
					modal: true,
					autoOpen: true,
					height: 440,
					width: 450,
					close: function(){ 
						$('.cls_cliente').each(function() {
							$(this).val("");
						});
					},
					buttons: {
						Salvar: function() {
							var mdados="";
							$('.cls_cliente').each(function(){
								if($(this).val()=="" && $(this).attr("obrigatorio")=="1"){
									alert("O campo " + $(this).attr("title") + " é obrigatório ");
									$(this).focus();
									return false;
								}
								mdados += $(this).attr("name")+"="+escape($(this).val())+"&";
							});
							
							$.ajax({
							   type: "POST",
							   url:  "inc/ajax_cliente.php",
							   data: "flag=" + valor2 + "&" + mdados,
							   success: function(retorno_ajax){
									if(retorno_ajax==1){
										$( "#dialog-edit-cliente" ).dialog( "close" );
										msgbox(valor2=="I"?"<br><table align='center'><tr><td>Cliente " + tu + " com sucesso !</td></tr></table><br>":"<br><table align='center'><tr><td>Campo editado com sucesso !</td></tr></table><br>", {
											Fechar: function(){
												$( this ).dialog( "close" );
												EnviarDados('index.php','13','');
											}
										});
									}else if(retorno_ajax==2){
										alert("Cliente já cadastrado!");
									}else{
										alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
									}
								}
							});
							
						},
						Sair: function() {
							$( this ).dialog( "close" );
							$('.cls_cliente').each(function() {
								$(this).val("");
							});
						}
					}
				});
				
				//alert($("#nivel_usu").find("option[value='USU']").attr("selected","selected"));
			}
		});
	}
	
	function fc_del_usu(valor1,valor2){
		msgbox("<br><table align='center'><tr><td style='font-size:8pt'>Deseja realmente deletar o usuário <b>" + valor2 + "</b> ?</td></tr></table><br>",{
			"Sim": function(){
				$.ajax({
					type: "POST",
					url:  "inc/ajax_usu.php",
					data: "flag=D&id_usu=" + valor1,
					success: function(retorno_ajax){
						$( this ).dialog( "close" );
						if(retorno_ajax==1){
							msgbox("<br><table align='center'><tr><td>Usuário deletado com sucesso !</td></tr></table><br>",{
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','8','');
								}
							});
						}else{
							alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
				//EnviarDados('index.php','8','');
			},
			"Não": function(){
				$( this ).dialog( "close" );
			}
		});
	}
	//deletar dados do servidor
	function fc_del_sql(valor1,valor2){
		msgbox("<br><table align='center'><tr><td style='font-size:8pt'>Deseja realmente deletar o servidor <b>" + valor2 + "</b> ?</td></tr></table><br>",{
			"Sim": function(){
				$.ajax({
					type: "POST",
					url:  "inc/ajax_sql.php",
					data: "flag=D&id_db=" + valor1,
					success: function(retorno_ajax){
						$( this ).dialog( "close" );
						if(retorno_ajax==1){
							msgbox("<br><table align='center'><tr><td>Servidor deletado com sucesso !</td></tr></table><br>",{
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','11','');
								}
							});
						}else{
							alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
				//EnviarDados('index.php','11','');
			},
			"Não": function(){
				$( this ).dialog( "close" );
			}
		});
	}
	function fc_del_list(valor1,valor2){
		msgbox("<br><table align='center'><tr><td style='font-size:8pt'>Deseja realmente deletar a lista <b>" + valor2 + "</b> ?</td></tr></table><br>",{
			"Sim": function(){
				$.ajax({
					type: "POST",
					url:  "inc/ajax_list_edit.php",
					data: "flag=D&num_grupo=" + valor1,
					success: function(retorno_ajax){
						$( this ).dialog( "close" );
						if(retorno_ajax==1){
							msgbox("<br><table align='center'><tr><td>Lista deletada com sucesso !</td></tr></table><br>",{
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','12','');
								}
							});
						}else{
							alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
			},
			"Não": function(){
				$( this ).dialog( "close" );
			}
		});
	}
	function fc_del_setor(valor1,valor2){
		msgbox("<br><table align='center'><tr><td style='font-size:8pt'>Deseja realmente deletar o setor <b>" + valor2 + "</b> ?</td></tr></table><br>",{
			"Sim": function(){
				$.ajax({
					type: "POST",
					url:  "inc/ajax_setor.php",
					data: "flag=D&id_setor=" + valor1,
					success: function(retorno_ajax){
						$( this ).dialog( "close" );
						if(retorno_ajax==1){
							msgbox("<br><table align='center'><tr><td>Setor deletado com sucesso !</td></tr></table><br>",{
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','9','');
								}
							});
						}else{
							alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
				//EnviarDados('index.php','8','');
			},
			"Não": function(){
				$( this ).dialog( "close" );
			}
		});
	}
	function fc_del_cliente(valor1,valor2){
		msgbox("<br><table align='center'><tr><td style='font-size:8pt'>Deseja realmente deletar o cliente <b>" + valor2 + "</b> ?</td></tr></table><br>",{
			"Sim": function(){
				$.ajax({
					type: "POST",
					url:  "inc/ajax_cliente.php",
					data: "flag=D&id_setor=" + valor1,
					success: function(retorno_ajax){
						$( this ).dialog( "close" );
						if(retorno_ajax==1){
							msgbox("<br><table align='center'><tr><td>Cliente deletado com sucesso !</td></tr></table><br>",{
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','13','');
								}
							});
						}else{
							alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
				//EnviarDados('index.php','8','');
			},
			"Não": function(){
				$( this ).dialog( "close" );
			}
		});
	}
	//Abre o editor de texto ao entrar no campo que for input_tipo=TEXTEAREA
	//editor=2 ativo
	function fc_textarea(valor,texto,editor)
	{
		rand = parseInt(rand) + 1;
		var $dialog = $('<div></div>')
			.html(
				"<textarea id='id_text_"+rand+"' style='width:99%;height:200px'>" + valor.value + "</textarea>"
				)
			.dialog({
				position: ["60%",145],
				width: "600px",
				modal: true,
				autoOpen: true,
				buttons: {
					Sim: function() {
						$( this ).dialog( "close" );
						$('#'+valor.id).val($('#id_text_'+rand).val());
					},	
					"Não": function() {
						$( this ).dialog( "close" );
					}},
				title: texto
			});
		if(editor==2){
			$('#id_text_'+rand).ckeditor(config3);
		}
	}
	
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
	
	function fc_del_input(valor){
		msgbox("<br><table align='center'><tr><td>Deseja realmente excluir esse campo?</td></tr></table>", {
			Sim: function() {
				$( this ).dialog( "close" );
				$.ajax({
				   type: "POST",
				   url:  "inc/ajax_input.php",
				   data: "flag=D" + 
						 "&idvalor=" + valor,
				   success: function(retorno_ajax){
						if(retorno_ajax ==1){
							msgbox("<br><table align='center'><tr><td> Campo excluir com sucesso !</td></tr></table>", {
								Fechar: function() {
									$( this ).dialog( "close" );
									EnviarDados('index.php','7',$('#TIPOPET').val());
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
	
	function cpfcnpj(valor){
		$("#"+valor).attr("alt",$("input[@TIPOPES=radioGroup]:checked").val());
		$('input:text').setMask();
	}
	
	function validaCaractaer(pEvent){
		if(navigator.appName.indexOf('Internet Explorer')>0){
			if ((pEvent.keyCode<97 || pEvent.keyCode>122)&&(pEvent.keyCode<48 || pEvent.keyCode>57)){
				alert("Caractere não aceito para esse campo");
				pEvent.keyCode = 0;
			}
		}else{
			if ((pEvent.which<97 || pEvent.which>122)&&(pEvent.which<48 || pEvent.which>57)) {	
				alert("Caractere não aceito para esse campo");
				pEvent.which = 0;
			}
		}
	}
	
	//Inserindo Inputs
	var dh = 440;
	$(function() {
		var i = $('input').size() + 1;
		$('a.add').click(function() {
			dh = dh + 20;
			$( "#dialog_inputs" ).dialog({height:dh});
			if($(".CKRETURN:checked").val()=="Tnenhum"){
				$('<div class="pInputs"><input type="text" class="slInputs input-default" value="Campo '+ i +'" style="width:203px;margin-top:5px;" my_val="'+ i +'" /></div>').animate({ opacity: "show" }, "slow").appendTo('#inputs');
			}else if($(".CKRETURN:checked").val()=="Tsimples"){
				$('<div class="pInputs"><input type="text" class="slInputs input-default" value="Campo '+ i +'" style="width:203px;margin-top:5px;" my_val="'+ i +'" />&nbsp;<input type="text" class="slTextarea input-default" id="slTextarea_'+ i +'" style="width:203px"/></div>').animate({ opacity: "show" }, "slow").appendTo('#inputs');
			}
			
			i++;
		});

		$('a.remove').click(function() {
			if(i > 2) {
			dh = dh - (dh>440 ? 20 : 0);
			$( "#dialog_inputs" ).dialog({height:dh});
			$('.pInputs:last').animate({opacity:"hide"}, "slow").remove();
			i--;
			
		}

		});
		
		$('a.reset').click(function() {
			dh = 440;
			$( "#dialog_inputs" ).dialog({height:dh});
			while(i > 2) {
				$('.pInputs').remove();
				i--;
			}
		});
		
	});
	
	function fc_optTexto(valor){
		if(valor=="TIPOSEL"){
			$("#tb_addText").hide();
			$(".tb_addSel").show();
			$("#tb_addTit").hide();
			$("#tb_addBase").show();
			$( "#dialog_inputs" ).dialog({height:490});
			$("#div_InArqui").hide();
		}else if(valor=="TIPOINP"){
			$("#tb_addText").show();
			$(".tb_addSel").hide();
			$("#tb_addTit").hide();
			$("#tb_addBase").show();
			$( "#dialog_inputs" ).dialog({height:440});
			$("#div_InArqui").show();
		}else if(valor=="TIPOTIT"){
			$("#tb_addText").hide();
			$(".tb_addSel").hide();
			$("#tb_addTit").show();
			$("#tb_addBase").hide();
			$( "#dialog_inputs" ).dialog({height:440});
			$("#div_InArqui").hide();
		}else if(valor=="TIPOOCT"){
			$("#tb_addText").show();
			$(".tb_addSel").hide();
			$("#tb_addTit").hide();
			$("#tb_addBase").show();
			$.ajax({
			   type: "POST",
			   url:  "inc/ajax_input.php",
			   data: "flag=G&idvalor=" + $("#TIPOPET").val(),
			   success: function(retorno_ajax){
				   $("#div_InLoad").html(retorno_ajax);
				}
			});
			$( "#dialog_inputs" ).dialog({height:440});
			$("#div_InArqui").hide();
		}
	}
	
	function fc_edit(valor){
		if(valor=='Editar'){
			$(".button_del").show();
			$("a.cls_edit").text('Cancelar');
			$("a.cls_edit").attr('onclick','fc_edit(\"Cancelar\")');
			$(".cls_campos").show();
			
		}
		else if(valor=='Cancelar'){
			$(".button_del").hide();
			$("a.cls_edit").text('Editar');
			$("a.cls_edit").attr('onclick','fc_edit(\"Editar\")');
			$(".cls_campos").hide();
		}
	}

function data_atual(campo1){
	var currentTime = new Date()
	var month  = currentTime.getMonth() + 1;
	var month2 = month<10?"0"+month:month;
	var day    = currentTime.getDate();
	var day2    = day<10?"0"+day:day;
	var year   = currentTime.getFullYear();

	var date = day2 + "/" + month2 + "/" + year;
	return $(campo1).val(date);
}

function data_extenso_out(campo1){

	var retorno = "";
	var dt 		= "";
	var iSdata 	= "";
	var str = campo1;

	if(str.value.length==10){
		
		dt=$('#'+campo1.id).val();	
		iSdata=dt.split("/");
		//data = new Date();
		dia = iSdata[0];
		mes = iSdata[1]-1;
		ano = iSdata[2];
		meses = new Array(12);
		meses[0] = "janeiro";
		meses[1] = "fevereiro";
		meses[2] = "março";
		meses[3] = "abril";
		meses[4] = "maio";
		meses[5] = "junho";
		meses[6] = "julho";
		meses[7] = "agosto";
		meses[8] = "setembro";
		meses[9] = "outubro";
		meses[10] = "novembro";
		meses[11] = "dezembro";
		
		retorno = dia + " de " + meses[mes] + " de " + ano;
		$("#"+campo1.id).val(retorno);
		$("#"+campo1.id).attr('alt','');
		
		return false;
	}
}
function data_extenso_cur(valor,cidade){
	data = new Date();
	dia = data.getDate();
	mes = data.getMonth();
	ano = data.getFullYear();
	meses = new Array(12);
	meses[0] = "janeiro";
	meses[1] = "fevereiro";
	meses[2] = "março";
	meses[3] = "abril";
	meses[4] = "maio";
	meses[5] = "junho";
	meses[6] = "julho";
	meses[7] = "agosto";
	meses[8] = "setembro";
	meses[9] = "outubro";
	meses[10] = "novembro";
	meses[11] = "dezembro";
	$("#"+valor).val(cidade + ', ' + dia + " de " + meses[mes] + " de " + ano);
}

function diasemana(valor){ 
	if(valor.value.length==10){
		var semana = ["domingo", "segunda-feira", "terça-feira","quarta-feira","quinta-feira","sexta-feira","sábado"];
		var data = $(valor).val();
		var arr = data.split("/").reverse();
		var teste = new Date(arr[0], arr[1] - 1, arr[2]);
		var dia = teste.getDay();
		$(valor).val(data + " (" + semana[dia] +")");
	}
}
//Função Valor por extenso
String.prototype.extenso = function(c){
	var ex = [
		["zero", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove", "dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"],
		["dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"],
		["cem", "cento", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"],
		["mil", "milhão", "bilhão", "trilhão", "quadrilhão", "quintilhão", "sextilhão", "setilhão", "octilhão", "nonilhão", "decilhão", "undecilhão", "dodecilhão", "tredecilhão", "quatrodecilhão", "quindecilhão", "sedecilhão", "septendecilhão", "octencilhão", "nonencilhão"]
	];
	var a, n, v, i, n = this.replace(c ? /[^,\d]/g : /\D/g, "").split(","), e = " e ", $ = "real", d = "centavo", sl;
	for(var f = n.length - 1, l, j = -1, r = [], s = [], t = ""; ++j <= f; s = []){
		j && (n[j] = (("." + n[j]) * 1).toFixed(2).slice(2));
		if(!(a = (v = n[j]).slice((l = v.length) % 3).match(/\d{3}/g), v = l % 3 ? [v.slice(0, l % 3)] : [], v = a ? v.concat(a) : v).length) continue;
		for(a = -1, l = v.length; ++a < l; t = ""){
			if(!(i = v[a] * 1)) continue;
			i % 100 < 20 && (t += ex[0][i % 100]) ||
			i % 100 + 1 && (t += ex[1][(i % 100 / 10 >> 0) - 1] + (i % 10 ? e + ex[0][i % 10] : ""));
			s.push((i < 100 ? t : !(i % 100) ? ex[2][i == 100 ? 0 : i / 100 >> 0] : (ex[2][i / 100 >> 0] + e + t)) +
			((t = l - a - 2) > -1 ? " " + (i > 1 && t > 0 ? ex[3][t].replace("ão", "ões") : ex[3][t]) : ""));
		}
		a = ((sl = s.length) > 1 ? (a = s.pop(), s.join(" ") + e + a) : s.join("") || ((!j && (n[j + 1] * 1 > 0) || r.length) ? "" : ex[0][0]));
		a && r.push(a + (c ? (" " + (v.join("") * 1 > 1 ? j ? d + "s" : (/0{6,}$/.test(n[0]) ? "de " : "") + $.replace("l", "is") : j ? d : $)) : ""));
	}
	return r.join(e);
}

//Função Valor por extenso
String.prototype.extensoNum = function(c){
	var ex = [
		["zero", "uma", "duas", "três", "quatro", "cinco", "seis", "sete", "oito", "nove", "dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"],
		["dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"],
		["cem", "cento", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"],
		["mil", "milhão", "bilhão", "trilhão", "quadrilhão", "quintilhão", "sextilhão", "setilhão", "octilhão", "nonilhão", "decilhão", "undecilhão", "dodecilhão", "tredecilhão", "quatrodecilhão", "quindecilhão", "sedecilhão", "septendecilhão", "octencilhão", "nonencilhão"]
	];
	var a, n, v, i, n = this.replace(c ? /[^,\d]/g : /\D/g, "").split(","), e = " e ", $ = "", d = "", sl;
	for(var f = n.length - 1, l, j = -1, r = [], s = [], t = ""; ++j <= f; s = []){
		j && (n[j] = (("." + n[j]) * 1).toFixed(2).slice(2));
		if(!(a = (v = n[j]).slice((l = v.length) % 3).match(/\d{3}/g), v = l % 3 ? [v.slice(0, l % 3)] : [], v = a ? v.concat(a) : v).length) continue;
		for(a = -1, l = v.length; ++a < l; t = ""){
			if(!(i = v[a] * 1)) continue;
			i % 100 < 20 && (t += ex[0][i % 100]) ||
			i % 100 + 1 && (t += ex[1][(i % 100 / 10 >> 0) - 1] + (i % 10 ? e + ex[0][i % 10] : ""));
			s.push((i < 100 ? t : !(i % 100) ? ex[2][i == 100 ? 0 : i / 100 >> 0] : (ex[2][i / 100 >> 0] + e + t)) +
			((t = l - a - 2) > -1 ? " " + (i > 1 && t > 0 ? ex[3][t].replace("ão", "ões") : ex[3][t]) : ""));
		}
		a = ((sl = s.length) > 1 ? (a = s.pop(), s.join(" ") + e + a) : s.join("") || ((!j && (n[j + 1] * 1 > 0) || r.length) ? "" : ex[0][0]));
		a && r.push(a + (c ? (" " + (v.join("") * 1 > 1 ? j ? d + "s" : (/0{6,}$/.test(n[0]) ? "de " : "") + $.replace("l", "is") : j ? d : $)) : ""));
	}
	return r.join(e);
}
//Função 'fc_newstring', valor por extenso à ser colocado no 'onblur' do input
function fc_newstring(valor){
	var resUlt = "";
	var resIds = "";
	var resVal = "";
	resIds=valor.id;
	resVal=valor.value;
	if($.isNumeric(resVal.replace(",",""))==true){
		resUlt = new String(resVal).extenso(true);
		$("#"+resIds).val(" ");
		return $("#"+resIds).val(resVal + " (" + resUlt + ")");
	}
}
//Função 'fc_newstring', número por extenso à ser colocado no 'onblur' do input
function fc_numextenso(valor1,valor2,txt){
	var resUlt = "";
	var resVal = "";
	var plural = "";
	resVal=$("#campo"+valor1).val();
	plural = (resVal>1?"s":"");
	if($.isNumeric(resVal.replace(",",""))==true){
		resUlt = new String(resVal).extensoNum(true);
		$("#campo"+valor2).val(" ");
		return $("#campo"+valor2).val(resVal + " (" + resUlt + ") " + txt +plural );
	}
}
function fc_verjuizo(valor1,valor2){
	var v = "";
	v = valor2.split(" ");
	if(valor1.value!=''){
		if(v[0]=='JUIZADO' || v[0]=='Juizado' ||  v[0]=='CARTÓRIO'){
			return valor1.value=(valor1.value.replace("º","")).replace("ª","") + 'º';
		} else if(v[0]=='VARA' || v[0]=='Vara'){
			return valor1.value=(valor1.value.replace("º","")).replace("ª","") + 'ª';
		} else {
			return valor1.value=(valor1.value.replace("º","")).replace("ª","") + 'º';
		}
	}
}

function fc_removeCapital(valor){
	return valor.value = valor.value.replace(" DA CAPITAL","");
}

function fc_teste_senha(valor1,valor2,valor3){
	
	if(valor1!=valor2){
		$("#senha_usu1").css("border","1px solid red");
		$("#senha_usu2").css("border","1px solid red");
		return "Senhas não são iguais";
	}else if(valor1=="" && valor3=="I"){
		$("#senha_usu1").css("border","1px solid red");
		$("#senha_usu2").css("border","1px solid red");
		return "Informe sua senha!";
	}else{
		if((valor1!="" && valor3=="U" && valor1.length<4) || (valor1.length<4 && valor3=="I")){
			$("#senha_usu1").css("border","1px solid red");
			$("#senha_usu2").css("border","1px solid red");
			return "Sua senha deve conter no mínimo 4 caracteres!";
		}else{
			var er = /[A-Za-z0-9_\-\.]{4,}/;
			if((er.test(valor1)==false && valor1!="" && valor3=="U") || (er.test(valor1)==false && valor3=="I")){
				$("#senha_usu1").css("border","1px solid red");
				$("#senha_usu2").css("border","1px solid red");
				return "Senha contém caractere inválido!";
			}else{
				return "";
			}
		}
	}
}

function validaEmail(mail){
	var er = /^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/;
	if(mail == ""){
		$("#email_usu").css("border","1px solid red");
		return "Informe seu e-mail!";
	}else if(er.test(mail) == false){
		$("#email_usu").css("border","1px solid red");
		return "E-mail inválido!";
	}else{
		return "";
	}
}

//mostra os campos que estavam ocultos e os tornam obrigatórios
function mcampo(valor){
	var campos = valor.split("_|_");
	if($("#"+campos[0]).val()=="SIM"){
		for(i = 1; i < campos.length; i++) {
			//alert(campos[i]);	
			$(".dis_"+campos[i]).show();
			$(".dis_"+campos[i]+" input").attr("obrigatorio","2");
		}
	}else if($("#"+campos[0]).val()=="NÃO"){
		for(i = 1; i < campos.length; i++) {			
			$(".dis_"+campos[i]).hide();
			$(".dis_"+campos[i]+" input").val("");
			$(".dis_"+campos[i]+" input").attr("obrigatorio","1");
		}
	}else{
		for(i = 1; i < campos.length; i++) {
			$(".dis_"+campos[i]).show();
			$(".dis_"+campos[i]+" input").attr("obrigatorio","1");
		}
	}
}

//mostra os campos que estavam ocultos e os tornam obrigatórios
function nparcelas(valor){
	var campos = valor.split("_|_");
	var num = parseInt($("#"+campos[0]).val()) * 2;
	var nn  = parseInt(num)+1;
	
	for(i=1; i< campos.length; i++) {
		//alert(nn+">"+i);
		if(nn>i){
			$(".dis_"+campos[i]).show();
			$(".dis_"+campos[i]+" input").attr("obrigatorio","2");
		}else{
			$(".dis_"+campos[i]).hide();
			$(".dis_"+campos[i]+" input").val("");
			$(".dis_"+campos[i]+" input").attr("obrigatorio","1");
		}
	}
	
	//if($("#"+campos[0]).val()=="1"){
	//	for(i = 1; i < campos.length; i++) {
	//		$(".dis_"+campos[i]).show();
	//		$(".dis_"+campos[i]+" input").attr("obrigatorio","2");
	//	}
	//}else{
	//	for(i = 1; i < campos.length; i++) {			
	//		$(".dis_"+campos[i]).hide();
	//		$(".dis_"+campos[i]+" input").val("");
	//		$(".dis_"+campos[i]+" input").attr("obrigatorio","1");
	//	}
	//}
}
//mostra os campos que estavam ocultos e os tornam obrigatórios
function nbens(valor,valor2){
	
	var campos = valor.split("_|_");
	var num = parseInt($("#"+campos[0]).val()) * 1;
	var nn  = parseInt(num)+1;
	//	alert($("#"+campos[0]).val());
	
	for(i=1; i< campos.length; i++) {
		if(nn>i){
			$(".dis_"+campos[i]).show();
			$(".dis_"+campos[i]+" "+valor2).attr("obrigatorio","2");
		}else{
			$(".dis_"+campos[i]).hide();
			$(".dis_"+campos[i]+" "+valor2).val("");
			$(".dis_"+campos[i]+" "+valor2).attr("obrigatorio","1");
		}
	}
	
	//if($("#"+campos[0]).val()=="1"){
	//	for(i = 1; i < campos.length; i++) {
	//		$(".dis_"+campos[i]).show();
	//		$(".dis_"+campos[i]+" input").attr("obrigatorio","2");
	//	}
	//}else{
	//	for(i = 1; i < campos.length; i++) {			
	//		$(".dis_"+campos[i]).hide();
	//		$(".dis_"+campos[i]+" input").val("");
	//		$(".dis_"+campos[i]+" input").attr("obrigatorio","1");
	//	}
	//}
}
function addMes(data,mes){
	var minhaData = moment(data,"D/M/YYYY").add('months', mes);
	return moment(minhaData).format('DD/MM/YYYY');
}

function addDataCampo(campo1,campo2,num){
	if($("#campo"+campo2).is(':hidden')==false){
		return $("#campo"+campo2).val(addMes($("#campo"+campo1).val(),1));
	}
}

function repeteValor(campo1,campo2){
	if($("#campo"+campo2).is(':hidden')==false){
		return $("#campo"+campo2).val($("#campo"+campo1).val());
	}
}

function alteraInput(campo1,campo2,campo3,campo4){
	if(fc_ajax_dado_1(campo1)==campo3){
		$(".dis_campo"+campo2+" label").text("CPF");
		$("#campo"+campo2).val("");
		$("#campo"+campo2).attr("alt","cpf");
	}else if(fc_ajax_dado_1(campo1)==campo4){
		$(".dis_campo"+campo2+" label").text("CNPJ");
		$("#campo"+campo2).val("");
		$("#campo"+campo2).attr("alt","cnpj");
	}
	$('input:text').setMask();
}

function estado_ext(valor){
	if(valor.value.length==2){
		var sigla = $(valor).val();
		var estado = new Array();
		estado["AC"]="ACRE";
		estado["AL"]="ALAGOAS";
		estado["AP"]="AMAPÁ";
		estado["AM"]="AMAZONAS";
		estado["BA"]="BAHIA";
		estado["CE"]="CEARÁ";
		estado["DF"]="DISTRITO FEDERAL";
		estado["ES"]="ESPÍRITO SANTO";
		estado["GO"]="GOIÁS";
		estado["MA"]="MARANHÃO";
		estado["MT"]="MATO GROSSO";
		estado["MS"]="MATO GROSSO DO SUL";
		estado["MG"]="MINAS GERAIS";
		estado["PA"]="PARÁ";
		estado["PB"]="PARAÍBA";
		estado["PR"]="PARANÁ";
		estado["PE"]="PERNAMBUCO";
		estado["PI"]="PIAUÍ";
		estado["RJ"]="RIO DE JANEIRO";
		estado["RN"]="RIO GRANDE DO NORTE";
		estado["RS"]="RIO GRANDE DO SUL";
		estado["RO"]="RONDÔNIA";
		estado["RR"]="RORAIMA";
		estado["SC"]="SANTA CATARINA";
		estado["SP"]="SÃO PAULO";
		estado["SE"]="SERGIPE";
		estado["TO"]="TOCANTINS";
		$(valor).val(estado[sigla]);
	}
}
function maiuscula(z){
	v = z.value.toUpperCase();
	z.value = v;
}

function minuscula(z){
	v = z.value.toLowerCase();
	z.value = v;
}
function tipolista(valor1,valor2){

	if(valor1=="hori"){
		$("#spn_rad_assc").show();
		valor2=$("#rad_assc").val();
	}else{
		$("#spn_rad_assc").hide();
	}
	
	var str = "";
	var seleciona = "";
	var vals = "";
	$("#tbBase option").each(function(index,element){
		
		vals = $(this).val().split("_|_");
		if($(this).val()!=""){
			if($(this).attr('selected')=="selected"){ seleciona = "selected='selected'"; } else{ seleciona = "";}
			str += "<option value='"+vals[0]+"_|_"+vals[1]+"_|_"+vals[2]+"_|_"+vals[3]+"_|_"+valor1+"_|_"+valor2+"' "+seleciona+">"+$(this).html()+"</option>";
		}
	});
	//"+$(this).val().replace(valor1,valor2)+
	$("#tbBase").html(str);
	str="";
}

function manter_inp(valor){
	if($(valor).is(":checked")==true){
		
		var inFocu = $("#inputFocu").val();
		var inLoad = $("#inputLoad").val();
		var inBlur = $("#inputBlur").val();
		
		
		$("#div_InFocu").html('<input type="text" id="inputFocu" class="input-default" style="height:18px;width:194px" value="'+inFocu+'" />');
		$("#div_InLoad").html('<input type="text" id="inputLoad" class="input-default" style="height:18px;width:194px" value="'+inLoad+'" />');
		$("#div_InBlur").html('<input type="text" id="inputBlur" class="input-default" style="height:18px;width:194px" value="'+inBlur+'" />');
	}else{
		$(".INPCHECK").each(function(){
			if($(this).is(":checked")==true){
				inputs_checkeds($(this).val());
			}
		});
	}
}

function mucampos(valor1,valor2){
	var myid_sel = $(".cke_dialog_ui_select select").attr("id");
	$("#"+myid_sel+" option").each(function(){
		
		if(valor2==1){
			$(this).val($(this).val().toUpperCase());
		}else if(valor2==2){
			var myCampo = $(this).val().replace('@', '');
			myCampo = myCampo.toLowerCase().replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); })
			$(this).val('@'+myCampo);
		}else if(valor2==0){
			$(this).val($(this).val().toLowerCase());
		}
	});
}

function add_dado(){
	var tipopeti = $("#TIPOPET").val();
	fc_inputs("I",tipopeti,1);
	$.ajax({
		type: "POST",
		url:  "inc/val_ajax.php",
		data: "flag=2&tipoid="+tipopeti,
		success: function(retorno_sel){
			$("#inputOrdn").val(retorno_sel);
		}
	});
}
function Cpadrao(valor){
	$.ajax({
		type: "POST",
		url:  "inc/ajax_cpadrao.php",
		data: "flag=I&tipoid="+valor,
		success: function(retorno_ajax){
			if(retorno_ajax==1){
				msgbox("<br><table align='center'><tr><td>Campos padrão criados com sucesso !</td></tr></table><br>", {
					Fechar: function(){
						$( this ).dialog( "close" );
						EnviarDados('index.php','7',$('#TIPOPET').val());
					}
				});
			}else{
				alert("Erro: " + retorno_ajax + ". (Copie esse erro e informe ao administrador)");
			}
				
		}
	});
}
function inserir_banco(valor,stt){
	var crt = parseFloat($("#banco_num").val());
	var atr = (32 * crt) +260;
	if(stt==1){
		crt = crt+1;
		$("#banco_"+(crt-1)).html(
		"<select class='cls_usu input-default cls_usu2' name='banco_usu_"+crt+"' style='height:22px'>"+valor+"</select>" +
		"<button id='inp1_"+crt+"' class='bts' onclick='inserir_banco($(\"#banco_usu_1\").html(),1);'>+</button>" +
		"<button id='inp0_"+crt+"' class='bts' onclick='inserir_banco($(\"#banco_usu_1\").html(),0);'>-</button>" + 
		"<div id='banco_"+crt+"'></div>");
		$("#inp1_"+(crt-1)).hide();
		$("#inp0_"+(crt-1)).hide();	
	}else if(stt==0){
		crt = crt-1;
		$("#banco_"+crt).html(" ");
		$("#inp1_"+crt).show();
		$("#inp0_"+crt).show();
	}
	$("#tb_dialog").css("height",atr+"px");
	$("#banco_num").val(crt);
}
function sel_tipo(valor1,valor2){
	
	$.ajax({
		type: "POST",
		url:  "inc/ajax_select2.php",
		data: "flag=" + valor2 + "&dados=" + valor1,
		success: function(retorno_ajax){
			if(valor1==0){
				$(".cls_andam").html(retorno_ajax);
				if(valor2==1){
					$("#sel_anda").html("Selecionar Andamentos:");
				}else if(valor2==2){
					$("#sel_anda").html("Selecionar Lançamentos:");
				}	
			}else if(valor1==1){
				$(".cls_usu2").html(retorno_ajax);
				$("#sel_banco").html("Clientes:");
			}
		}
	});
}
function recolher_topo(valor1,valor2){
	if(valor2=="off"){
		$(valor1).hide();
		$("#up_topo").hide();
		$("#dw_topo").show();
		//$("#tb_editor").css("margin-top","20px");
	}else if(valor2=="on"){
		$(valor1).show();
		$("#up_topo").show();
		$("#dw_topo").hide();
	}
}