<table align="center" style="margin-top:80px; width:70%"  class="adminlist">
	<tr height="30">
		<td class="order" ><b>id_grupo 	</b></td>
		<td class="order" ><b>nome_grupo</b></td>
		<td class="order" ><b>data_cad	</b></td>
		<td class="order" width="20%"><b>Opções    </b></td>
	</tr>
<?php
	
$query = mysqli_query($conexao1,"SELECT * from tp_grupo_tb as g ORDER by g.id_grupo") or die(mysqli_error());
while ($arr = mysqli_fetch_array($query)){
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