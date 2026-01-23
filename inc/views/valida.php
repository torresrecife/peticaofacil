<?php
/** @var bool $showNewPass */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<script type="text/javascript" src="../js/jquery-1.8.0.min.js">		</script>
<script type="text/javascript" src="../js/jquery-ui-1.8.23.custom.min.js"></script>
<link rel="stylesheet" href="../css/main.min.css" type="text/css" />
<script type="text/javascript" src="../js/main.min.js"></script>
<input type="hidden" id="valida_show_newpass" value="<?php echo $showNewPass ? '1' : '0'; ?>" />
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
