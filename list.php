<table align="center" style="margin-top:80px; width:70%"  class="adminlist">
	<tr height="30">
		<td class="order" ><b>id_grupo 	</b></td>
		<td class="order" ><b>nome_grupo</b></td>
		<td class="order" ><b>data_cad	</b></td>
		<td class="order" width="20%"><b>Opções    </b></td>
	</tr>
<?php
	
$rows = array();
if (!class_exists(\App\Repositories\ListaRepository::class)) {
	echo '<tr><td colspan="4" class="order">Erro ao carregar lista.</td></tr>';
} else {
	$repo = new \App\Repositories\ListaRepository($conexao1);
	$rows = $repo->listGroups();
}
foreach ($rows as $arr){
	?>
	<tr >
		<td class="order"><?php echo $arr['id_grupo'];	?></td>
		<td class="order"><?php echo $arr['nome_grupo'];?></td>
		<td class="order"><?php echo $arr['data_cad']; 	?></td>
		<td class="order"><?php echo fc_botoes_grp($arr['id_grupo'],"block",$arr['nome_grupo']); ?></td>
	</tr>
	<?php
}
?>
</table>
<div id="dialog-edit-list" title="Editar Lista" style="display:none; text-align:left;">
	<p class="validateTips">Edite a Lista Abaixo</p>
	<fieldset>
		<div style="height:290px; width:420px" id="return_lista">
			
		</div>
	</fieldset>
</div>
<input type="hidden" class="cls_usu" name="id_lista" id="id_lista" value="" />