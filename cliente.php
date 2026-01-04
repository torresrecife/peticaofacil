<div class="content_body">
	<div class="cpanel-center">
		<div class="cpanel">
			<div class="icon-wrapper">
				<table class="adminlist" width="60%" align="center">
					<tr height="30">
						<td class="order" ><b>Código		  </b></td>
						<td class="order" ><b>Nome	do cliente</b></td>
						<td class="order" ><b>Nome chave</b></td>
						<td class="order" ><b>Setor			  </b></td>
						<td class="order" ><b>Data Cadastro	  </b></td>
						<td class="order" ><b>Opções          </b></td>
					</tr>
					<?php
						
					$query = mysqli_query($conexao1,"SELECT * FROM tp_clientes_db AS c JOIN tp_setor_tb AS s ON s.id_setor=c.cliente_area ORDER BY c.cliente_id") or die(mysqli_error());
					while ($arr = mysqli_fetch_array($query))
					{
						?>
						<tr >
							<td class="order"><?PHP echo $arr['cliente_id'];	 ?></td>
							<td class="order"><?php echo $arr['cliente_name']; ?></td>
							<td class="order"><?php echo $arr['cliente_cod']; ?></td>
							<td class="order"><?php echo $arr['nome_setor']; 	 ?></td>
							<td class="order"><?php echo $arr['cliente_creator']; 	 ?></td>
							<td class="order"><?php echo fc_botoes_cliente($arr['cliente_id'],"block",$arr['cliente_name']); ?></td>
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
							$qsetor = mysqli_query($conexao1,"SELECT * FROM tp_setor_tb");
							while($wsetor = mysqli_fetch_array($qsetor)){
								
								?>
								<option value="<?php echo $wsetor[0]; ?>"> <?php echo $wsetor[1]; ?></option>
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