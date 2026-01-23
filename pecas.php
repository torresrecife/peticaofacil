<script type="text/javascript" src="js/pages/pecas.js"></script>
<div class="content_body">
	<div class="cpanel-left">
		<div class="cpanel">
			<div class="icon-wrapper">
				<div align="left" id="accordion" style="width:1080px;" >
					
				<?php
				$rows = array();
				if (class_exists(\App\Repositories\TipoRepository::class)) {
					$repo = new \App\Repositories\TipoRepository($conexao1);
					$setorId = $usu_nivel != "ADM" ? $usu_setor : null;
					$rows = $repo->listBySetor($setorId);
				}
				foreach ($rows as $arTipo) {
					?>
					<div class="group">
						<h3><a href="#" style="cursor: move;" onclick="ajax_pecas('<?php echo $arTipo['tipo_id']; ?>','0','')" ><?php echo $arTipo['tipo_nome']; ?></a></h3>
						<div align="center" id="html_pecas_<?php echo $arTipo['tipo_id']; ?>"></div>
					</div>
					<?php
				}
				?>
					<input type="hidden" name="is_pecas" id="is_pecas" value="1" />
					<input type="hidden" name="id_pecas" id="id_pecas" value=""  />
					<input type="hidden" name="tipo_id"  id="tipo_id"  value=""  />
					<input type="hidden" name="nomepet"  id="nomepet"  value=""  />
					<input type="hidden" name="nomecli"  id="nomecli"  value=""  />
				</div>
			</div>
		</div>
	</div>
</div>
<div id="dialog-edit-setor" title="Editar Setor" style="display:none; text-align:left;">
	<p class="validateTips">Edite o Usuário Abaixo</p>
	<fieldset>
		<div>
			<table>
				<tr>
					<td><label>Nome do Setor:</label></td>
					<td><input type="text" class="cls_setor" name="nome_setor" id="nome_setor" value="" obrigatorio="1" title="Nome do Setor"/></td>
				</tr>
			</table>
			<input type="hidden" class="cls_setor" name="id_setor" id="id_setor" value="" />
		</div>
	</fieldset>
</div>