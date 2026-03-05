var myfocus = 0;
var mystart = 0;

$(function() {
	if (!window.CKEDITOR || !document.getElementById('name_text') || !CKEDITOR.replace) {
		return;
	}
	if (CKEDITOR.instances && CKEDITOR.instances.name_text) {
		return;
	}
	var nameText = document.getElementById('name_text');
	if (nameText && nameText.tagName && nameText.tagName.toLowerCase() !== 'textarea') {
		return;
	}
	CKEDITOR.config.skin = 'moono-lisa2';
	CKEDITOR.config.tabSpaces = 4;
	CKEDITOR.config.removePlugins = 'elementspath';
	CKEDITOR.config.width = 618.7;
	CKEDITOR.config.defaultLanguage = 'pt_BR';
	CKEDITOR.config.toolbarGroups = [
		{ name: 'basicstyles', groups: ['basicstyles', 'cleanup'] },
		{ name: 'clipboard', groups: ['clipboard', 'undo'] },
		{ name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi', 'paragraph'] },
		{ name: 'insert', groups: ['insert', 'Image'] },
		{ name: 'editing', groups: ['find', 'selection', 'spellchecker', 'editing'] },
		{ name: 'styles', groups: ['styles'] },
		{ name: 'document', groups: ['mode', 'document', 'doctools'] },
		{ name: 'colors', groups: ['colors'] },
		{ name: 'tools', groups: ['tools'] },
		{ name: 'links', groups: ['links'] },
		{ name: 'forms', groups: ['forms'] },
		{ name: 'others', groups: ['others'] },
		{ name: 'about', groups: ['about'] }
	];
	CKEDITOR.config.removeButtons = 'Save,NewPage,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Flash,Smiley,SpecialChar,Iframe,About,ShowBlocks,Templates,Anchor,Unlink,Link,Language,BidiRtl,BidiLtr,Styles,Blockquote,CreateDiv,PageBreak,Print,Preview,Maximize';
	var editor = CKEDITOR.replace('name_text', {
		extraPlugins: 'autogrow,myplugin,sharedspace,uploadimage,insertTab',
		removePlugins: 'floatingspace,resize',
		sharedSpaces: {
			top: 'topSpace',
			bottom: 'bottomSpace'
		},
		language: 'pt_BR',
		contentsCss: 'css/texto.css',
		toolbar: [
			['Bold', 'Italic', 'Underline', 'Strike'],
			['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'insertTab', 'CampoBT'],
			['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print'],
			['Format', 'Font', 'FontSize'],
			['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
			['Image', 'Table', 'HorizontalRule']
		]
	});

	if (window.CKFinder && CKFinder.setupCKEditor) {
		CKFinder.setupCKEditor(editor, 'ckfinder/');
	}

	CKEDITOR.on('instanceReady', function(evt) {
		var editor = evt.editor;
		editor.on('focus', function() {
			myfocus = 1;
		});
		editor.on('blur', function() {
			myfocus = 0;
		});
	});

	$('#scrlBotm').click(function() {
		$('html, body').animate({ scrollTop: $(document).height() }, 1500);
		return false;
	});
	$('#scrlTop').click(function() {
		$('html, body').animate({ scrollTop: '0px' }, 1500);
		return false;
	});

	$("#div_bottom").mouseover(function() {
		$("#div_bottom").fadeTo("slow", 1.0);
	});
	$("#div_bottom").mouseout(function() {
		$("#div_bottom").fadeTo("slow", 1.0);
	});
	$("#nomepet").mouseover(function() {
		$("#nomepet").val() == "Nome_do_Arquivo" ? $("#nomepet").val("") : $("#nomepet").val();
	});
	$("#nomepet").mouseout(function() {
		$("#nomepet").val() == "" ? $("#nomepet").val("Nome_do_Arquivo") : $("#nomepet").val();
	});
});

$(document).ready(function() {
	$('#div_bottom').hide();

	$("body").css("color", "#333");
	$(function() {
		$(window).scroll(function() {
			if ($(this).scrollTop() > ($("#tb_editor").height() - 800)) {
				$('#div_bottom').fadeIn();
			} else {
				$('#div_bottom').fadeOut();
			}
		});
	});

	fc_salvar_auto();
});

function ver_title() {
	var n = 0;
	$('.titulos').each(function(index) {
		var n = parseInt(n) + 1;
		$("#topicT").append("<button type='button' id='bt_" + index + "' style='background:url(\"img/topicos.png\")no-repeat;color:#ffffff;width:55px;height:20px;font-size:6pt;margin-top:1px;text-align:left;' onclick='ver_topico(" + $(this).offset().top + "," + index + ");' title='" + $(this).text() + "'>" + $(this).text().substr(0, 6) + "..</button>");

		$("#bt_" + index).fadeTo("slow", 0.6);

		$("#bt_" + index).mouseover(function() {
			$("#bt_" + index).fadeTo("slow", 1.0);
		});

		$("#bt_" + index).mouseout(function() {
			$("#bt_" + index).fadeTo("slow", 0.6);
		});

		$("#bt_" + index).click(function() {
			$("#bt_" + index).css("opacity", 1);
		});
	});
}

function ver_topico(valor, nume) {
	$('html,body').animate({ scrollTop: (valor) - 50 }, 'slow');
	var tags = $(".titulos").length;
	tags2 = (parseInt(tags) - 1);

	if (nume == tags2) {
		$("#botao_next").hide();
	}
	if (nume < tags2) {
		$("#botao_next").show();
	}
	if (nume > 0) {
		$("#botao_prev").show();
	}
	if (nume < 1) {
		$("#botao_prev").hide();
	}
	$("#id_topicos").val(parseInt(nume) + 1);
}

function goToByScroll(id, num, par) {
	var tags = $(".titulos").length;
	if (par == 1) {
		$('.titulos').each(function(index) {
			alert(par);
			if (index == num) {
				$('html,body').animate({ scrollTop: ($(this).offset().top) - 50 }, 'slow');
			}
		});
		num = parseInt(num) + 1;
	}
	if (par == 0) {
		num = parseInt(num) - 1;
		$('.titulos').each(function(index) {
			if (index == (parseInt(num) - 1)) {
				$('html,body').animate({ scrollTop: ($(this).offset().top) - 50 }, 'slow');
			}
		});
	}

	if (num == tags) {
		$("#botao_next").hide();
	}
	if (num < tags) {
		$("#botao_next").show();
	}
	if (num > 0) {
		$("#botao_prev").show();
	}
	if (num == 1) {
		$("#botao_prev").hide();
	}

	$("#id_topicos").val(num);
}

$(window).load(function() {
	ver_title();
	$("#ger_rtf").attr("disabled", true);
	$("#ger_pdf").attr("disabled", true);
	$("#ger_rtf").css("background", "url('img/doc-c.png') no-repeat");
	$("#ger_pdf").css("background", "url('img/pdf-c.png') no-repeat");
});

function fc_focus(valor) {
	$("#" + valor).focus();
}

function replaceAll(string, token, newtoken) {
	while (string.indexOf(token) != -1) {
		string = string.replace(token, newtoken);
	}
	return string;
}

function fc_salvar_pet(valor) {
	for (instance in CKEDITOR.instances)
		CKEDITOR.instances[instance].updateElement();

	$("#ger_sav").attr("disabled", true);
	$("#ger_sav").css("background", "url('img/progress.gif') no-repeat");
	var name_text = $("#name_text").val();
	name_text = replaceAll(name_text, "&", "_|_");

	$.ajax({
		type: "POST",
		url: "inc/getsav.php",
		data: "flag=" + $("#id_sav").attr("flag")
			+ "&id_pecas=" + $("#id_sav").val()
			+ "&tipo_id=" + $("#tipo_id").val()
			+ "&nomepet=" + $("#nomepet").val()
			+ "&codsav=" + $("#codsav").val()
			+ "&name_text=" + name_text,
		dataType: "json",

		success: function(response) {
			if (!response || !response.ok) {
				var msg = response && response.message ? response.message : "Erro ao salvar peça.";
				alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
				$("#ger_sav").attr("disabled", false);
				$("#ger_sav").css("background", "url('img/salvar.png') no-repeat");
				return;
			}
			var retorno_ajax = response.data ? response.data.id : "";

			$("#ger_sav").css("background", "url('img/salvar_ok.png') no-repeat");
			$("#id_sav").val(retorno_ajax);
			$("#id_sav").attr("flag", 2);
			$("#ger_sav").attr("disabled", false);
			$("#ger_rtf").attr("disabled", false);
			$("#ger_pdf").attr("disabled", false);
			$("#ger_rtf").css("background", "url('img/doc.png') no-repeat");
			$("#ger_pdf").css("background", "url('img/pdf.png') no-repeat");
			$("#ger_rtf").css("cursor", "pointer");
			$("#ger_pdf").css("cursor", "pointer");
		}
	});
	if (valor == 1) {
		mystart = 1;
	}
}

function fc_salvar_auto() {
	setTimeout(function() {
		if (myfocus == 1) {
			if (mystart == 1) {
				fc_salvar_pet(0);
			}
		}
		fc_salvar_auto();
	}, 10000);
}
