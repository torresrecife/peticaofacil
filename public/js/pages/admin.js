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
					data: "flag=T"
						+ "&tipotitle=" + escape($("#TIPOTITLE").val())
						+ "&tipotitle_pre=" + escape($("#TIPOTITLE_PRE").val())
						+ "&tiposql=" + escape($("#TIPOSQL").val())
						+ "&tiposetor=" + escape($("#TIPOSETOR").val())
						+ "&tipoclien=" + escape($("#TIPOCLIEN").val())
						+ "&tipoarqui=" + escape($("#TIPOARQUI").val()),
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
