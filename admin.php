
<?php
if($_POST['hid_enviar']==5){
?>
	<div class="content_body">
		<div class="cpanel-left">
			<div class="cpanel">
				<?php fc_select_div("tp_tipo_tb",'1',"tipo_id","tipo_nome",$usu_cliente,"E",$conexao1,$usu_setor); ?>
			</div>
		</div>
		<div class="cpanel-right">
			<div class="cpanel">
				<?php 
				if($_SESSION['usuarioNivel']=="ADM"){
					?>
					<div class="icon-wrapper">
						<div class="icon">
							<a href="#" onclick="return EnviarDados('index.php','11','')">
								<img src="css/images/header/icon-48-sql.png" alt=""  /><span>Servidor</span>
							</a>
						</div>
					</div>
					<div class="icon-wrapper">
						<div class="icon">
							<a href="#" onclick="return EnviarDados('index.php','9','')">
								<img src="css/images/header/icon-48-move.png" alt=""  /><span>Setores</span>
							</a>
						</div>
					</div>
					<div class="icon-wrapper">
						<div class="icon">
							<a href="#" onclick="return EnviarDados('index.php','13','')">
								<img src="css/images/header/icon-48-module.png" alt=""  /><span>Clientes</span>
							</a>
						</div>
					</div>
					<div class="icon-wrapper">
						<div class="icon">
							<a href="#" onclick="return EnviarDados('index.php','8','')">
								<img src="css/images/header/icon-48-user.png" alt=""  /><span>Usuários</span>
							</a>
						</div>
					</div>
					<?php 
				}
				?>
				<div class="icon-wrapper">
					<div class="icon">
						<a href="#" id="prg" onclick="return novo_tipo()">
							<img src="css/images/header/icon-48-article-add.png" alt=""  /><span>Novo Modelo</span>
						</a>
					</div>
				</div>
				<div class="icon-wrapper">
					<div class="icon">
						<a href="#" onclick="return EnviarDados('index.php','12','')">
							<img src="css/images/header/icon-48-list.png" alt=""  /><span>Lista</span>
						</a>
					</div>
				</div>
			</div>
		</div>
		<div class="cpanel-right-sub">
			<div class="cpanel">
				<div class="icon-wrapper">
					<div class="icon">
						<a href="#" id="frm" onclick="return mark_edit(7,0)" style="border:1px solid red">
							<img src="css/images/header/icon-48-themes.png" alt=""  /><span>Formulário</span>
						</a>
					</div>
				</div>	
				<div class="icon-wrapper">
					<div class="icon">
						<a href="#" id="prg" onclick="return mark_edit(6,0)" style="border:1px solid red">
							<img src="css/images/header/icon-48-article-edit.png" alt=""  /><span>Parágrafos</span>
						</a>
					</div>
				</div>
				<div class="icon-wrapper">
					<div class="icon">
						<a href="#" id="prg" onclick="return mark_edit('',1)" style="border:1px solid red">
							<img src="css/images/header/icon-48-deny.png" alt=""  /><span>Excluir</span>
						</a>
					</div>
				</div>		
			</div>
		</div>	
	</div>
<?php
}
?>
<script>
function novo_tipo(){
	$( "#dialog_tipo" ).dialog({
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
				   url:  "inc/ajax_tipo.php",
				   data: "flag=T" + 
						 "&tipotitle=" + escape($("#TIPOTITLE").val()) +
						 "&tipotitle_pre=" + escape($("#TIPOTITLE_PRE").val()) +
						 "&tiposql="   + escape($("#TIPOSQL").val()) +
						 "&tiposetor=" + escape($("#TIPOSETOR").val()) +
						 "&tipoclien=" + escape($("#TIPOCLIEN").val()) +
						 "&tipoarqui=" + escape($("#TIPOARQUI").val()) 
						 ,
				   dataType: "json",
				   success: function(response){
						if(response && response.ok){
							$( "#dialog_tipo" ).dialog( "close" );
							msgbox("<br> Modelo criado com sucesso !", {
								Fechar: function(){
									$( this ).dialog( "close" );
									EnviarDados('index.php','5','');
								}
							});
						}else{
							var msg = response && response.message ? response.message : "Erro ao criar modelo.";
							alert("Erro: " + msg + ". (Copie esse erro e informe ao administrador)");
						}
					}
				});
			},
			Sair: function() {
				$( this ).dialog( "close" );
			}
		}
	
		
	});
	return false;
}
</script>

<div id="dialog_tipo" title="Novo Modelo" style="display:none">
	<div style="height:210px; width:260px" >
		<center>
			<br/>
			<table>
				<tr height="30px">
					<td align="left" class="td_title" colspan="2">Título do Modelo<br/>
					<input type="text" name="TIPOTITLE" id="TIPOTITLE" style="width:222px"/></td>
				</tr>
				<tr height="30px">
					<td align="left" class="td_title" colspan="2">
						Descrição:<br>
						<input type="text" name="TIPOTITLE_PRE" id="TIPOTITLE_PRE" style="width:222px"/>
					</td>
				</tr>
				<tr height="30px">
					<td align="left" class="td_title">Servidor<br/>
						<select name="TIPOSQL" id="TIPOSQL" title="Setor" style="width:222px; height:21px; background: #e6e6e6" >
							<option value="">  </option>                                       
						<?php 
						$servers = array();
						if (class_exists(\App\Repositories\SqlConfigRepository::class)) {
							$repo = new \App\Repositories\SqlConfigRepository($conexao1);
							$servers = $repo->listAll();
						}
						foreach ($servers as $wserver){
							?>
							<option value="<?php echo $wserver[0]; ?>"> <?php echo $wserver[1]; ?></option>
							<?php 
						}
						?>
						</select>
					</td>
				</tr>
				<tr height="30px">
					<td align="left" class="td_title">Setor<br />
						<select name="TIPOSETOR" id="TIPOSETOR" title="Setor" style="width:222px; height:21px; background: #e6e6e6" >
							<option value="">  </option>                                       
						<?php 
						$setores = array();
						if (class_exists(\App\Services\SetorService::class)) {
							$setorService = new \App\Services\SetorService($conexao1);
							$setores = $setorService->listAll();
						}
						foreach ($setores as $wsetor){
							if ($usu_setor != 0 && $wsetor[0] != $usu_setor) {
								continue;
							}
							?>
							<option value="<?php echo $wsetor[0]; ?>"> <?php echo $wsetor[1]; ?></option>
							<?php 
						}
						?>
						</select>
					</td>
				</tr>
				<tr height="30px">
					<td align="left" class="td_title">Cliente<br />
						<select name="TIPOCLIEN" id="TIPOCLIEN" title="Cliente" style="width:222px; height:21px; background: #e6e6e6" >
							<option value="0">Todos do Setor</option>                                       
						<?php 
						$clientes = array();
						if (class_exists(\App\Services\ClienteService::class)) {
							$clienteService = new \App\Services\ClienteService($conexao1);
							$clientes = $clienteService->listAll();
						}
						foreach ($clientes as $wcliente){
							if ($usu_cliente != 0 && strpos(',' . $usu_cliente . ',', ',' . $wcliente[0] . ',') === false) {
								continue;
							}
							?>
							<option value="<?php echo $wcliente[0]; ?>"> <?php echo $wcliente[1]; ?></option>
							<?php 
						}
						?>
						</select>
					</td>
				</tr>
				<tr height="30px">
					<td align="left" class="td_title">Tipo de Arquivo<br />
						<select name="TIPOARQUI" id="TIPOARQUI" title="Setor" style="width:222px; height:21px; background: #e6e6e6" >
							<option value="">  </option>                                       
							<option value="pdf"> PDF</option>
							<!--option value="word"> WORD</option-->
							<!--option value="pdf,word"> PDF e WORD</option-->
						</select>
					</td>
				</tr>
			</table> 
		</center>	
	</div>
</div>

