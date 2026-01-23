function new_pass() {
	var tt = "Nova senha";
	$("#dialog-new-pass").dialog({
		title: tt,
		modal: true,
		autoOpen: true,
		height: 240,
		width: 220,
		close: function() {
			location.href = "../login.php";
		},
		buttons: {
			Salvar: function() {
				if ($("#senha_usu1").val() != $("#senha_usu2").val()) {
					alert("As senhas não conferem!");
				} else if ($("#senha_usu1").val() == "") {
					alert("Preencha o campo sennha!");
				} else {
					$.ajax({
						type: "POST",
						url: "../inc/ajax_newpass.php",
						data: "flag=U"
							+ "&id_usu=" + $("#id_usu").val()
							+ "&senha_usu1=" + $("#senha_usu1").val(),
						dataType: "json",
						success: function(response) {
							if (!response || !response.ok) {
								var msg = response && response.message ? response.message : "Erro ao alterar senha.";
								alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
								return;
							}

							var $dialog = $('<div></div>')
								.html("<br><table align='center'><tr><td>Senha alterada com sucesso!</td></tr></table>")
								.dialog({
									modal: true,
									autoOpen: true,
									buttons: {
										"Fechar": function() {
											$(this).dialog("close");
											location.href = "../index.php";
										}
									},
									title: 'Alerta'
								});
						}
					});
				}
			},
			Sair: function() {
				$(this).dialog("close");
			}
		}
	});
}

$(function() {
	var showNewPass = $("#valida_show_newpass").val();
	if (showNewPass === "1") {
		new_pass();
	}
});
