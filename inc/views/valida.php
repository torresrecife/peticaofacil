<?php
/** @var bool $showNewPass */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<script type="text/javascript" src="../js/jquery-1.8.0.min.js">		</script>
<script type="text/javascript" src="../js/jquery-ui-1.8.23.custom.min.js"></script>
	<link rel="stylesheet" href="../css/template.css" type="text/css" />
	<link rel="stylesheet" href="../css/custom-theme/jquery-ui-1.8.23.custom.css">
<script language= "JavaScript">
function new_pass(){
	var tt = "Nova senha";
	$( "#dialog-new-pass" ).dialog({
		title: tt,
		modal: true,
		autoOpen: true,
		height: 240,
		width: 220,
		close: function(){ 
			location.href="../login.php";
		},
		buttons: {
			Salvar: function() { 
				if($("#senha_usu1").val()!=$("#senha_usu2").val()){
					alert("As senhas não conferem!");
				}else if($("#senha_usu1").val()==""){
					alert("Preencha o campo sennha!");
				}else{
					$.ajax({
						type: "POST",
						url : "../inc/ajax_newpass.php",
						data: "flag=U" + 
							  "&id_usu=" + $("#id_usu").val() +
							  "&senha_usu1=" + $("#senha_usu1").val(),  
						dataType: "json",
						success: function(response){
							if(!response || !response.ok){
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
										$( this ).dialog( "close" );
										location.href="../index.php";
									}
								},
								title: 'Alerta'
							});	
						}
					});
				}
			},
			Sair: function() {
				$( this ).dialog( "close" );
			}
		}
	});
}
</script>
<?php if ($showNewPass) { ?>
	<script> $(function() { new_pass(); }); </script>
<?php } ?>
<div id="dialog-new-pass" title="Editar Usuário" style="display:none; text-align:left;">
	<p class="validateTips"><?php echo "Alteração de senha obrigatória!"; ?></p>
	<fieldset>
		<div>
			<table>
				<tr>
					<td><label>Nova Senha </label></td>
					<td><input type="password" class="cls_usu" name="senha_usu1" id="senha_usu1" value="" /></td>
				</tr>
				<tr>
					<td><label>Repete a Senha</label></td>
					<td><input type="password" class="cls_usu" name="senha_usu2" id="senha_usu2" value="" /></td>
				</tr>
			</table>
			<input type="hidden" class="cls_usu" name="id_usu" id="id_usu" value="<?php echo $_SESSION['usuarioID']; ?>" />
		</div>
	</fieldset>
</div>
