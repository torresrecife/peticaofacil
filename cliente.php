<div class="content_body">
	<div class="cpanel-center">
		<div class="cpanel">
			<div class="icon-wrapper">
				<table class="adminlist" width="60%" align="center">
					<tr height="30">
						<td class="order" ><b>C&oacute;digo		  </b></td>
						<td class="order" ><b>Nome	do cliente</b></td>
						<td class="order" ><b>Nome chave</b></td>
						<td class="order" ><b>Setor			  </b></td>
						<td class="order" ><b>Data Cadastro	  </b></td>
						<td class="order" ><b>Op&ccedil;&otilde;es          </b></td>
					</tr>
					<?php
						
					$rows = array();
					if (class_exists(\App\Services\ClienteService::class)) {
						$service = new \App\Services\ClienteService($conexao1);
						$rows = $service->listAllWithSetor();
					}
					foreach ($rows as $arr) {
						$clienteNome = function_exists('app_to_utf8') ? app_to_utf8($arr['cliente_name']) : $arr['cliente_name'];
						$clienteCod = function_exists('app_to_utf8') ? app_to_utf8($arr['cliente_cod']) : $arr['cliente_cod'];
						$setorNome = function_exists('app_to_utf8') ? app_to_utf8($arr['nome_setor']) : $arr['nome_setor'];
						?>
						<tr >
							<td class="order"><?PHP echo $arr['cliente_id'];	 ?></td>
							<td class="order"><?php echo $clienteNome; ?></td>
							<td class="order"><?php echo $clienteCod; ?></td>
							<td class="order"><?php echo $setorNome; 	 ?></td>
							<td class="order"><?php echo $arr['cliente_creator']; 	 ?></td>
							<td class="order"><?php echo fc_botoes_cliente($arr['cliente_id'],"block",$clienteNome); ?></td>
						</tr>
						<?php
					}
				?>
				</table>
			</div>
		</div>
	</div>
</div>
<div id="dialog-edit-cliente" title="Editar Cliente" style="display:none; text-align:left;">
	<p class="validateTips">Edite o cliente Abaixo</p>
	<fieldset>
		<div>
			<table>
				<tr>
					<td><label>Nome do Cliente:</label></td>
					<td><input type="text" class="cls_cliente" name="cliente_name" id="cliente_name" value="" obrigatorio="1" title="Nome do Cliente"/></td>
				</tr>
				<tr>
					<td><label>Nome chave:</label></td>
					<td><input type="text" class="cls_cliente" name="cliente_cod" id="cliente_cod" value="" obrigatorio="1" title="Nome chave"/></td>
				</tr>
				<tr>
					<td><label>Setor do Cliente:</label></td>
					<td>
					<select class="cls_cliente" name="cliente_area" id="cliente_area" obrigatorio="1" title="Setor do Cliente">
							<option value="">  </option>                                                                             
							<?php 
							$setores = array();
							if (class_exists(\App\Services\SetorService::class)) {
								$setorService = new \App\Services\SetorService($conexao1);
								$setores = $setorService->listAll();
							}
							foreach($setores as $wsetor){
								$setorLabel = function_exists('app_to_utf8') ? app_to_utf8($wsetor[1]) : $wsetor[1];
								?>
								<option value="<?php echo $wsetor[0]; ?>"> <?php echo $setorLabel; ?></option>
								<?php 
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<input type="hidden" class="cls_cliente" name="cliente_id" id="cliente_id" value="" />
		</div>
	</fieldset>
</div>
