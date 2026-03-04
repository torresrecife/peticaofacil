function novo_tipo() {
	$("#dialog_tipo").dialog({
		modal: true,
		autoOpen: true,
		close: function() {
		},
		width: 360,
		height: 380,
		buttons: {
			Salvar: function() {
				$.ajax({
					type: "POST",
					url: "inc/ajax_tipo.php",
					data: {
						flag: "T",
						tipotitle: $("#TIPOTITLE").val(),
						tipotitle_pre: $("#TIPOTITLE_PRE").val(),
						tiposql: $("#TIPOSQL").val(),
						tiposetor: $("#TIPOSETOR").val(),
						tipoclien: $("#TIPOCLIEN").val(),
						tipoarqui: $("#TIPOARQUI").val()
					},
					dataType: "json",
					success: function(response) {
						if (response && response.ok) {
							$("#dialog_tipo").dialog("close");
							msgbox("<br> Modelo criado com sucesso !", {
								Fechar: function() {
									$(this).dialog("close");
									EnviarDados('index.php', '5', '');
								}
							});
						} else {
							var msg = response && response.message ? response.message : "Erro ao criar modelo.";
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
	return false;
}
