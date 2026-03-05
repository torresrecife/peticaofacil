var rand = 0;
var config3 = {
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
};

if (window.CKEDITOR) {
	CKEDITOR.config.skin = 'moono-lisa';
	CKEDITOR.config.tabSpaces = 4;
	CKEDITOR.config.removePlugins = 'elementspath';
	CKEDITOR.config.defaultLanguage = 'pt_BR';
	CKEDITOR.config.toolbarGroups = [
		{ name: 'basicstyles', groups: ['basicstyles', 'cleanup'] },
		{ name: 'clipboard', groups: ['clipboard', 'undo'] },
		{ name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi', 'paragraph'] },
		{ name: 'insert', groups: ['insert'] },
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
	CKEDITOR.config.removeButtons = 'Save,NewPage,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Image,Flash,Smiley,SpecialChar,Iframe,About,ShowBlocks,Templates,Anchor,Unlink,Link,Language,BidiRtl,BidiLtr,Styles,Blockquote,CreateDiv,PageBreak,Print,Preview';
}

$(function() {
	if ($.fn.combobox) {
		$('.js-combobox').combobox();
	}

	$('.js-horiz-select').each(function() {
		var $select = $(this);
		var hinputSource = $select.data('hinput-source');
		var hinputValue = hinputSource ? $('#' + hinputSource).val() : '';

		$.ajax({
			type: 'POST',
			url: 'inc/ajax_horiz.php',
			data: {
				flag: 'H',
				dd_input: $select.data('dd-input') || '',
				hinput: hinputValue || '',
				inputdb_0: $select.data('inputdb-0') || '',
				inputdb_1: $select.data('inputdb-1') || '',
				inputdb_2: $select.data('inputdb-2') || '',
				inputdb_3: $select.data('inputdb-3') || '',
				inputdb_4: $select.data('inputdb-4') || '',
				inputdb_5: $select.data('inputdb-5') || ''
			},
			dataType: 'json',
			success: function(response) {
				if (!response || !response.ok) {
					return;
				}
				$select.html(response.data ? response.data.html : '');
			}
		});
	});
});

function fc_textarea(valor, texto, editor) {
	rand = parseInt(rand) + 1;
	var $dialog = $('<div></div>')
		.html("<textarea id='id_text_" + rand + "' style='width:99%;height:200px'>" + valor.value + "</textarea>")
		.dialog({
			position: ["60%", 145],
			width: "700px",
			modal: true,
			autoOpen: true,
			close: function() {
				$("#topSpace").html("");
			},
			buttons: {
				Sim: function() {
					$(this).dialog("close");
					$('#' + valor.id).val($('#id_text_' + rand).val());
				},
				"Não": function() {
					$(this).dialog("close");
				}
			},
			title: texto
		});
	if (editor == 2 && $.fn.ckeditor) {
		$('#id_text_' + rand).ckeditor(config3);
	}
}

function validate_peticao(args='') {
	var dd = 0;
	$('.new_required').each(function(index, object) {
		var $obj = $(object);
		var rawVal = ($obj.val() || "").toString();
		if ($obj.is('select') && rawVal === '') {
			var $comboInput = $obj.next('.ui-combobox').find('input.ui-autocomplete-input');
			if ($comboInput.length) {
				rawVal = ($comboInput.val() || "").toString();
			}
		}
		var normVal = rawVal.toUpperCase();
		if (normVal.normalize) {
			normVal = normVal.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		}
		normVal = normVal.replace(/[^A-Z]/g, '');
		if (normVal === 'NO') {
			normVal = 'NAO';
		}
		if (args != "") {
			if (normVal == "AUSENTE" || normVal == "MUDOUSE" || normVal == "DESCONHECIDO") {
				dd = 1;
			}
		} else {
			if (normVal == "NAO" && $obj.attr("type") != "radio") {
				dd = 1;
			} else if ($obj.attr("type") == "radio") {
				setTimeout(function() {
					if ($obj.is(":checked") == true && normVal == "NAO") {
						dd = 1;
					}
				}, 200);
			}
		}
	});

	setTimeout(function() {
		if (dd == 1) {
			$("#bt-enviar-dados").attr("disabled", true).css("border", "1px solid red");
			$(".content_form").css("border", "1px solid red");
			$("#msg-enviar-dados").show();
		} else {
			$("#bt-enviar-dados").attr("disabled", false).css("border", "1px solid #d3d3d3");
			$(".content_form").css("border", "0");
			$("#msg-enviar-dados").hide();
		}
	}, 500);
}
