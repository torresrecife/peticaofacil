var str_retorno_ajax = $("#str_retorno_ajax").val() || "";

$(function() {
	var actParag = parseInt($("#act_parag").val(), 10);
	if (isNaN(actParag)) {
		actParag = 0;
	}

	$("#accordion").accordion({
		autoHeight: false,
		navigation: true,
		header: "> div > h3",
		active: actParag
	});

	$("input:text").setMask();

	$(".group .delete").click(function() {
		$(this).parents(".group").fadeOut('slow', function() {
			$(this).remove();
		});
	});

	var config1 = {
		extraPlugins: 'autogrow,rodape,myplugin,sharedspace,cabecalho,rodape,assinatura',
		removePlugins: 'floatingspace,resize',
		sharedSpaces: {
			top: 'topSpace',
			bottom: 'bottomSpace'
		},
		contentsCss: 'css/texto.css',
		toolbar: [
			['Bold', 'Italic', 'Underline', 'Strike'],
			['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'insertTab'],
			['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print'],
			['Format', 'Font', 'FontSize'],
			['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
			['cabecalho', 'rodape', 'assinatura', 'Image', 'Table', 'HorizontalRule'],
			['-', '-', '-', 'Smiley', '-', '-', '-']
		]
	};

	CKEDITOR.config.tabSpaces = 4;
	CKEDITOR.config.removePlugins = 'elementspath';
	$('.cls_text').ckeditor(config1);
});

function del_parag(valor) {
	msgbox("<br><table align='center'><tr><td>Deseja realmente deletar esse tópico?</td></tr></table>", {
		Sim: function() {
			$(this).dialog("close");
			$.ajax({
				type: "POST",
				url: "inc/ajax_parag.php",
				data: "flag=D"
					+ "&idvalor=" + valor,
				dataType: "json",
				success: function(response) {
					if (response && response.ok) {
						msgbox("<br><table align='center'><tr><td> Input deletado com sucesso !</td></tr></table>", {
							Fechar: function() {
								$(this).dialog("close");
								EnviarDados('index.php', '6', $('#TIPOPET').val());
							}
						});
					} else {
						var msg = response && response.message ? response.message : "Erro ao deletar topico.";
						alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
					}
				}
			});
		},
		"Não": function() {
			$(this).dialog("close");
		}
	});
}

function save_parag(id, valor1, valor2) {
	$(this).dialog("close");
	$.ajax({
		type: "POST",
		url: "inc/ajax_parag.php",
		data: "flag=" + valor2
			+ "&fund_id=" + id
			+ "&fund_text=" + escape($(valor1).val()),
		dataType: "json",
		success: function(response) {
			if (response && response.ok) {
				$(".cke_copyformatting_notification").after('<div class="cke_notifications_area" id="cke_notifications_area_input_cabec" style="z-index: 9998; float:left; position: absolute; top: 248px; left: 50%; margin-left: -158px"><div class="cke_notification cke_notification_info" id="cke-e51ba611e23782e3bc366a32ec27899e0" role="alert" aria-label="info"><p class="cke_notification_message">Salvo com sucesso!</p><a class="cke_notification_close" href="javascript:void(0)" onclick="javascript:$(\'#cke_notifications_area_input_cabec\').remove();" title="Fermer" role="button" tabindex="-1"><span class="cke_label">X</span></a></div></div>');
				setTimeout(function() {
					$("#cke_notifications_area_input_cabec").remove();
				}, 1000);
			} else {
				var msg = response && response.message ? response.message : "Erro ao salvar topico.";
				alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
			}
		}
	});
}

function novo_parag() {
	$("#dialog_parag").dialog({
		modal: true,
		autoOpen: true,
		close: function() {
		},
		buttons: {
			Salvar: function() {
				$.ajax({
					type: "POST",
					url: "inc/ajax_parag.php",
					data: "flag=I"
						+ "&toptitle=" + escape($("#TOPTITLE").val())
						+ "&tipo_id=" + escape($("#tipo_id").val()),
					dataType: "json",
					success: function(response) {
						if (response && response.ok) {
							$("#dialog_parag").dialog("close");
							msgbox("<br> Tópico criado com sucesso !", {
								Fechar: function() {
									$(this).dialog("close");
									EnviarDados('index.php', '6', $('#TIPOPET').val());
								}
							});
						} else {
							var msg = response && response.message ? response.message : "Erro ao criar topico.";
							alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
			},
			Sair: function() {
				$(this).dialog("close");
			}
		}
	});
}
