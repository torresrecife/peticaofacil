<table style="margin-top:80px" class="adminlist">
	<tr height="30">
		<td class="order" ><b>Código		 </b></td>
		<td class="order" ><b>Nome			 </b></td>
		<td class="order" ><b>E-mail		 </b></td>
		<td class="order" ><b>Usuário		 </b></td>
		<td class="order" ><b>Nível			 </b></td>
		<td class="order" ><b>Setor			 </b></td>
		<td class="order" ><b>Carteira		 </b></td>
		<td class="order" ><b>Último Acesso	 </b></td>
		<td class="order" ><b>Data Cadastro	 </b></td>
		<td class="order" ><b>Status		 </b></td>
		<td class="order" ><b>Opções         </b></td>
	</tr>
<?php

$rows = null;
if (class_exists(\App\Repositories\UsuarioRepository::class)) {
	$repo = new \App\Repositories\UsuarioRepository($conexao1);
	$rows = $repo->listAllWithRelations();
} else {
	$rows = array();
}
foreach ($rows as $arr){
$acesso = empty($arr['acesso_usu']) || $arr['acesso_usu']=="0000-00-00 00:00:00" ? "" : strftime("%d/%m/%Y %H:%M:%S", strtotime($arr['acesso_usu']));
	?>
	<tr >
		<td class="order"><?PHP echo $arr['id_usu'];		?>	</td>
		<td class="order"><?php echo $arr['nome_usu']; ?>	</td>
		<td class="order"><?php echo $arr['email_usu']; 	?>	</td>
		<td class="order"><?php echo $arr['login_usu']; 	?>	</td>
		<td class="order"><?php echo $arr['nivel_usu'];		?>	</td>
		<td class="order"><?php echo ($arr['nome_setor'] ? $arr['nome_setor'] : 'TODOS'); ?></td>
		<td class="order"><?php echo ($arr['cliente_name']?$arr['cliente_name']:"TODAS"); ?></td>
		<td class="order"><?php echo $acesso;  ?>	</td>
		<td class="order"><?php echo strftime("%d/%m/%Y %H:%M:%S", strtotime($arr['datacad'])); 	?>	</td>
		<td class="order"><?php echo $arr['status_usu']; ?></td>
		<td class="order"><?php echo fc_botoes_usu($arr['id_usu'],"block",$arr['login_usu']); ?></td>
	</tr>
	<?php
}
?>
</table>
<div id="dialog-edit-usu" title="Editar Usuário" style="display:none; text-align:left;">
	<?php 
	if(isset($_GET['edit_status']) && $_GET['edit_status']==""){
		$cad_msg = "Para manter a senha, deixe em branco.";
	}elseif(isset($_GET['edit_status']) && $_GET['edit_status']=="1"){
		$cad_msg = '<font color="red">Alteração realizado com sucesso!</font>';
	}elseif(isset($_GET['edit_status']) && $_GET['edit_status']=="3"){
		$cad_msg = '<font color="red">Repita a senha corretamente!</font>';
	}elseif(isset($_GET['edit_status']) && $_GET['edit_status']=="5"){
		$cad_msg = '<font color="red">Nome e Usuário é obrigatório!</font>';
	}	
	?>
	<p class="validateTips">Edite o Usuário Abaixo</p>
	<fieldset>
		<div>
			<table>
				<tr>
					<td><label>Nome:</label></td>
					<td><input type="text" class="cls_usu" name="nome_usu" id="nome_usu" value="" obrigatorio="1" title="Nome e Sobrenome"/></td>
				</tr>
				<tr>
					<td><label>Usuário:</label></td>
					<td><input type="text" class="cls_usu" name="login_usu" id="login_usu"  value="" obrigatorio="1" title="Usuário"/></td>
				</tr>
				<tr>
					<td><label>E-mail:</label></td>
					<td><input type="text" class="cls_usu" name="email_usu" id="email_usu" value="" obrigatorio="1" title="E-mail"/></td>
				</tr>
				<tr>
					<td><label>Nível:</label></td>
					<td>
						<select class="cls_usu" name="nivel_usu" id="nivel_usu" obrigatorio="1" title="Nivel">
							<option value="">  </option>                                       
							<option value="ADM"> Admin </option>
							<option value="GER"> Gerente</option>
							<option value="USU"> Usuário</option> 
						</select>
					</td>
				</tr>
				<tr>
					<td><label>Setor:</label></td>
					<td>
						<select class="cls_usu" name="setor_usu" id="setor_usu" onchange="sel_tipo(1,this.value)" obrigatorio="1" title="Setor">                                  
							<option value="0">Todos</option>                                       
							<?php 
							$setores = array();
							if (class_exists(\App\Services\SetorService::class)) {
								$setorService = new \App\Services\SetorService($conexao1);
								$setores = $setorService->listAll();
							}
							foreach($setores as $wsetor){
								?>
								<option value="<?php echo $wsetor[0]; ?>"> <?php echo $wsetor[1]; ?></option>
								<?php 
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td><label id="sel_banco">Cliente:</label></td>	
					<td>
						<div id="banco_0">
						<select class="cls_usu input-default cls_usu2" name="banco_usu_1" id="banco_usu_1" obrigatorio="1" title="Cliente" style="height:22px">
						</select>
						<button id="inp1_1" type="button" class="bts" onclick="inserir_banco($('#banco_usu_1').html(),1);">+</button>
						</div>
						<div id="banco_1"></div>
					</td>
				</tr>
				<tr>
					<td><label>Status </label></td>
					<td>
						<select class="cls_usu" name="status_usu" id="status_usu" obrigatorio="1" title="Status" >
							<option value=""></option> 
							<option value="ATI">Ativo </option> 
							<option value="INA">Inativo</option> 
						</select>
					</td>
				</tr>
				<tr>
					<td><label>Senha </label></td>
					<td><input type="password" class="cls_usu" name="senha_usu1" id="senha_usu1" value="" /></td>
				</tr>
				<tr>
					<td><label>Repete a Senha</label></td>
					<td><input type="password" class="cls_usu" name="senha_usu2" id="senha_usu2" value="" /></td>
				</tr>
			</table>
			<input type="hidden" class="cls_usu" name="id_usu" id="id_usu" value="" />
			<input type="hidden" name="banco_num" id="banco_num" value="1" />
		</div>
	</fieldset>
</div>