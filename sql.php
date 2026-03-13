<table style="margin-top:80px" class="adminlist">
	<tr height="30">
		<td class="order" ><b>Código		 </b></td>
		<td class="order" ><b>Nome			 </b></td>
		<td class="order" ><b>Tipo			 </b></td>
		<td class="order" ><b>IP			 </b></td>
		<td class="order" ><b>Banco de dados </b></td>
		<td class="order" ><b>Usuário		 </b></td>
		<td class="order" ><b>Senha			 </b></td>
		<td class="order" ><b>Tabela		 </b></td>
		<td class="order" ><b>Chave			 </b></td>
		<td class="order" ><b>Query	         </b></td>
		<td class="order" ><b>Where	         </b></td>
		<td class="order" ><b>Opções         </b></td>
	</tr>
<?php
	
$rows = null;
if (class_exists(\App\Repositories\SqlConfigRepository::class)) {
	$repo = new \App\Repositories\SqlConfigRepository($conexao1);
	$rows = $repo->listAll();
} else {
	$rows = array();
}
foreach ($rows as $arr){
	$nomeDb = function_exists('app_to_utf8') ? app_to_utf8($arr['nome_db']) : $arr['nome_db'];
	$queryDb = function_exists('app_to_utf8') ? app_to_utf8($arr['query_db']) : $arr['query_db'];
	$whereDb = function_exists('app_to_utf8') ? app_to_utf8($arr['where_db']) : $arr['where_db'];
	?>
	<tr >
		<td class="order"><?PHP echo $arr['id_db'];		?>	</td>
		<td class="order"><?php echo $nomeDb;	?>	</td>
		<td class="order"><?php echo $arr['tipo_id'];	?>	</td>
		<td class="order"><?php echo $arr['ip_db']; 	?>	</td>
		<td class="order"><?php echo $arr['data_db'];	?>	</td>
		<td class="order"><?php echo $arr['usu_db'];	?>	</td>
		<td class="order"><?php echo $arr['senha_db']; 	?>	</td>
		<td class="order"><?php echo $arr['table_db']; 	?>	</td>
		<td class="order"><?php echo $arr['chave_db']; 	?>	</td>
		<td class="order" title="<?php echo htmlspecialchars($queryDb, ENT_QUOTES, 'UTF-8'); ?>" ><?php echo substr($queryDb,0,30) . (strlen($queryDb)>30?"...":""); 	?>	</td>
		<td class="order" title="<?php echo htmlspecialchars($whereDb, ENT_QUOTES, 'UTF-8'); ?>" ><?php echo substr($whereDb,0,30) . (strlen($whereDb)>30?"...":""); 	?>	</td>
		<td class="order"><?php echo fc_botoes_sql($arr['id_db'],"block",$nomeDb); ?></td>
	</tr>
	<?php
}
?>
</table>
<div id="dialog-edit-sql" title="Editar Usuário" style="display:none; text-align:left;">
	<?php 
	if($_GET['edit_status']==""){
		$cad_msg = "Para manter a senha, deixe em branco.";
	}
	if($_GET['edit_status']=="1"){
		$cad_msg = '<font color="red">Alteração realizado com sucesso!</font>';
	}
	if($_GET['edit_status']=="3"){
		$cad_msg = '<font color="red">Repita a senha corretamente!</font>';
	}
	if($_GET['edit_status']=="5"){
		$cad_msg = '<font color="red">Nome do Servidor é obrigatório!</font>';
	}
	?>
	<p class="validateTips">Edite o Sql Abaixo</p>
	<fieldset>
		<div style="height:290px; width:420px">
			<table>
				<tr>
					<td>Nome do servidor:<br>
					<input type="text" class="cls_usu" name="nome_db" id="nome_db" value="" obrigatorio="1" title="Nome do servidor" style="width:200px" /></td>
					<td>Status <br>
						<select class="cls_usu" name="stt" id="stt" obrigatorio="1" title="Status" style="width:200px; height:22px">
							<option value=""></option> 
							<option value="ATI">Ativo </option> 
							<option value="INA">Inativo</option> 
						</select>
					</td>
				</tr>
				<tr>
					<td>IP do servidor:<br>
					<input type="text" class="cls_usu" name="ip_db" id="ip_db"  value="" obrigatorio="1" title="IP do servidor" style="width:200px" /></td>
					<td>Banco de dados:<br>
					<input type="text" class="cls_usu" name="data_db" id="data_db"  value="" obrigatorio="1" title="Banco de dados" style="width:200px"/></td>
				</tr>
				<tr>
					<td>Usuário:<br>
					<input type="text" class="cls_usu" name="usu_db" id="usu_db"  value="" obrigatorio="1" title="Usuário" style="width:200px"/></td>
					<td>Senha <br>
					<input type="text" class="cls_usu" name="senha_db" id="senha_db" value="" title="Senha de acesso ao banco de dados" style="width:200px"/></td>
				</tr>
				<tr>
					<td>Tabela:<br>
					<input type="text" class="cls_usu" name="table_db" id="table_db"  value="" obrigatorio="1" title="Tabela do banco" style="width:200px"/></td>
					<td>Chave:<br>
					<input type="text" class="cls_usu" name="chave_db" id="chave_db" value="" obrigatorio="1" title="Chave utilizada para localizar o caso" style="width:200px"/></td>
				</tr>
				<tr>
					<td colspan="2">Query:<br>
					<textarea type="text" class="cls_usu" name="query_db" id="query_db" value="" obrigatorio="1" title="Query utilizada para executar" style="width:405px"></textarea></td>
				</tr>
				<tr>
					<td colspan="2">Where:<br>
					<textarea type="text" class="cls_usu" name="where_db" id="where_db" value="" obrigatorio="1" title="Where utilizada para executar" style="width:405px"></textarea></td>
				</tr>
			</table>
			<input type="hidden" class="cls_usu" name="id_db" id="id_db" value="" />
		</div>
	</fieldset>
</div>
