<?php

include("seguranca.php");
protegePagina();

if($_POST['flag']=="E")
{
	$id_grupo  = $_POST['id_lista'];
	$return = "";
	if (!class_exists(\App\Repositories\ListaRepository::class)) {
		echo 0;
		exit;
	}

	$wg = null;
	$nun_grupo = null;
	$listRows = array();

	$repo = new \App\Repositories\ListaRepository($conexao1);
	if ($id_grupo != "") {
		$wg = $repo->findGroupById($id_grupo);
		$nun_grupo = $id_grupo;
		$listRows = $repo->listItemsByGroup($id_grupo);
	} else {
		$nun_grupo = $repo->nextGroupId();
	}
	
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	
	?>
	<table align='center' border='0' cellspacing='0' cellpadding='0' style='margin-bottom:1px;width:33%'>
		<tr>
			<td><input type="text" value="Grupo:" 	 	 title="Id do grupo" 	style="background:#ffffff;text-align:center;margin:1px;width:30px ;" /></td>
			<td><input type="text" value="Nome do Grupo" title="Nome da lista" 	style="background:#ffffff;text-align:center;margin:1px;width:200px;" /></td>
		</tr>
		<tr>
			<td><input type="text" id="num_grupo"  value="<?php echo $nun_grupo; ?>" 				 	   title="Id do grupo" 	 style="text-align:center;margin:1px;width:30px ;" readonly="readonly" /></td>
			<td><input type="text" id="nome_grupo" value="<?php echo ($id_grupo?$wg['nome_grupo']:''); ?>" title="Nome do Grupo" style="text-align:center;margin:1px;width:200px;" /></td>
				<input type="hidden" value="<?php echo ($id_grupo?"nao":"sim"); ?>" id="novo_grupo" />
		</tr>
	</table>
	<br/><br/>
	<table align='center' border='0' cellspacing='0' cellpadding='0' style='width:99%'>
		<tr>
			<td><input type="text" value="Grupo:" 	 title="Id do grupo" 	style="background:#ffffff;text-align:center;margin:1px;width:30px ;" readonly="readonly"/></td>
			<td><input type="text" value="Nome" 	 title="Nome da lista" 	style="background:#ffffff;text-align:center;margin:1px;width:100px;" readonly="readonly"/></td>
			<td><input type="text" value="Retorno 1" title="return_1" 		style="background:#ffffff;text-align:center;margin:1px;width:160px;" readonly="readonly"/></td>
			<td><input type="text" value="Retorno 2" title="return_2" 		style="background:#ffffff;text-align:center;margin:1px;width:160px;" readonly="readonly"/></td>
			<td><input type="text" value="Retorno 3" title="return_3" 		style="background:#ffffff;text-align:center;margin:1px;width:160px;" readonly="readonly"/></td>
			<td><input type="text" value="Retorno 4" title="return_4" 		style="background:#ffffff;text-align:center;margin:1px;width:160px;" readonly="readonly"/></td>
			<td><input type="text" value="Retorno 5" title="return_5" 		style="background:#ffffff;text-align:center;margin:1px;width:160px;" readonly="readonly"/></td>
			<td><input type="text" value="Retorno 6" title="return_6" 		style="background:#ffffff;text-align:center;margin:1px;width:160px;" readonly="readonly"/></td>
			<td><input type="text" value="id_setor"  title="Setor" 	 		style="background:#ffffff;text-align:center;margin:1px;width:60px;" readonly="readonly"/></td>
		</tr>
	</table>
	<?php 
	if($id_grupo!=""){
	?>
		<table align='center' border='0' cellspacing='0' cellpadding='0' style='width:99%'>
			<?php
			$i = 0;
			foreach ($listRows as $while) {
				?>
				<tr class="slInputs">
					<td>
						<input type="hidden" class="cls_list" name="id_lista" value="<?php echo $while['id_lista']; ?>" />
						<input type="text" class="cls_list" name="id_grupo" id="id_grupo" value="<?php echo $while['id_grupo']; ?>" title="Id do grupo" style="margin:1px;width:30px" readonly="readonly"/>
					</td>
					<td><input type="text" class="cls_list" name="nome_lista" id="nome_lista" value="<?php echo $while['nome_lista']; ?>" title="Nome da lista" style="margin:1px;width:100px" /></td>
					<td><input type="text" class="cls_list" name="return_1"   id="return_1"   value="<?php echo $while['return_1'];   ?>" title="return_1" 		style="margin:1px;width:160px" /></td>
					<td><input type="text" class="cls_list" name="return_2"   id="return_2"   value="<?php echo $while['return_2'];   ?>" title="return_2" 		style="margin:1px;width:160px" /></td>
					<td><input type="text" class="cls_list" name="return_3"   id="return_3"   value="<?php echo $while['return_3'];   ?>" title="return_3" 		style="margin:1px;width:160px" /></td>
					<td><input type="text" class="cls_list" name="return_4"   id="return_4"   value="<?php echo $while['return_4'];   ?>" title="return_4" 		style="margin:1px;width:160px" /></td>
					<td><input type="text" class="cls_list" name="return_5"   id="return_5"   value="<?php echo $while['return_5'];   ?>" title="return_5" 		style="margin:1px;width:160px" /></td>
					<td><input type="text" class="cls_list" name="return_6"   id="return_6"   value="<?php echo $while['return_6'];   ?>" title="return_6" 		style="margin:1px;width:160px" /></td>
					<td><input type="text" class="cls_list" name="id_setor"   id="id_setor"   value="<?php echo $while['id_setor'];   ?>" title="Setor" 		style="margin:1px;width:60px;text-align:center"  readonly="readonly"/></td>
				</tr>
				<!--input type="hidden" class="cls_list" name="separador" id="separador" value="_|_" /-->
				<?php
			}
			?>
		</table>
	<?php 
	}
	?>
	<table align='center' border='0' cellspacing='0' cellpadding='0' style='width:98%'>
		<div id="inputs2" style="margin:0;"></div>
	</table>
	<?php
}

?>