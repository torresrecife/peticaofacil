$(function() {
	if (!window.jQuery || !$.fn.accordion || !$("#accordion").length) {
		return;
	}
	$("#accordion").accordion({
		autoHeight: false,
		navigation: true,
		header: "> div > h3"
	});
});

function ajax_pecas(valor1, valor2, valor3) {
	$.ajax({
		type: "POST",
		url: "inc/ajax_pecas.php",
		data: "flag=H&tipo_id=" + valor1 + "&limit=" + valor2 + "&search=" + valor3,
		dataType: "json",
		success: function(response) {
			if (!response || !response.ok) {
				var msg = response && response.message ? response.message : "Erro ao carregar pecas.";
				alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
				return;
			}
			$("#html_pecas_" + valor1).html(response.data ? response.data.html : "");
		}
	});
}

function ajax_pecas_search(valor1) {
	ajax_pecas(valor1, 0, $("#search_" + valor1).val());
}
