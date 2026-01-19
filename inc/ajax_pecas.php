<?php

include("seguranca.php");
protegePagina();

//parâmetros dos usuários
$usu_setor = $_SESSION['usuarioSetor'];
$usu_nivel = $_SESSION['usuarioNivel'];
$usu_id    = $_SESSION['usuarioID'];

$limit=$_POST['limit'];
$search=$_POST['search'];

if($_POST['flag']=="H"){
	$q1 = " SELECT *, date_format(p.data_cad, '%d/%m/%Y %H:%i:%s') as dtcadastro ";
	$q2 = " SELECT count(*) ";
	$q  = " from tp_pecas_tb as p ";
	$q .= " JOIN tp_usu_tb AS u on u.id_usu=p.id_usu ";
	$q .= " where p.tipo_id='".$_POST['tipo_id']."' ";
	if($usu_nivel!="ADM"){
		$q .= "and p.id_usu = '".$usu_id."' ";
	}
	if($search!=""){
		$q .= "and p.nome_cli like '%".$search."%' ";
	}
	$q .= " ORDER by p.id_pecas desc ";
	
	$pg = $limit*10;
	$q3 = " limit " . $pg . ", 10";
	
	//select registros
	$query = mysqli_query($conexao1,$q1.$q.$q3) or die(mysqli_error());
	
	//select paginação
	$qpagi = mysqli_query($conexao1,$q2.$q) or die(mysqli_error());
	$qtd = mysqli_fetch_array($qpagi);
	
	if(mysqli_num_rows($query)>0){
	?>
	<div style="float:left; padding-bottom:5px; margin-left:13px"><label style="padding-top:3px; float:left;">Pesquisa:&nbsp;&nbsp;</label><input type="text" id="search_<?php echo $_POST['tipo_id']; ?>" value="" style="float:left; height:19px; width:260px"/><input type="button" onclick="ajax_pecas_search('<?php echo $_POST['tipo_id']; ?>')" value="Procurar" style="float:left; margin-left:5px; height:23px; cursor:pointer"/></div>
	<table class="adminlist" width="70%" align="center">
		<tr height="30">
			<td class="order" ><b>Código		 </b></td>
			<td class="order" ><b>Arquivo		 </b></td>
			<td class="order" ><b>Data Cadastro	 </b></td>
			<td class="order" ><b>Usuários       </b></td>
			<?php 
			if($usu_nivel=="ADM"){
			?>
			<td class="order" ><b>Opções         </b></td>
			<?php
			}
			?>
		</tr>
		<?php
		$n=1;
		while ($arr = mysqli_fetch_array($query)){
			?>
			<tr>
				<td class="order" width="50px"><span class="num"><?php echo $n++;?></span><span style="margin-left:20px"><?php echo $arr['id_pecas']; ?></span></td>
				<td class="order" style="text-align:left">
					<span style="padding-right:10px;"><a href="#" onclick="PetiDados('inc/getpdf.php','','<?php echo $arr['id_pecas'];?>','<?php echo $arr['tipo_id']; ?>','<?php echo ($arr['nome_pecas']); ?>','<?php echo ($arr['nome_cli']); ?>'); " style="float:left"><img src="img/pdf2.png" style="padding-right:10px;margin-top:-10px"></a></span>
					<span style="padding-right:10px;"><a href="#" onclick="PetiDados('inc/getrtf.php','','<?php echo $arr['id_pecas'];?>','<?php echo $arr['tipo_id']; ?>','<?php echo ($arr['nome_pecas']); ?>','<?php echo ($arr['nome_cli']); ?>'); " style="float:left"><img src="img/word.png" style="padding-right:10px;margin-top:-10px"></a></span>
					<?php echo ($arr['nome_pecas']."-".$arr['nome_cli']); ?>
				</td>
				<td class="order" width="100px"><?php echo $arr['dtcadastro']; ?></td>
				<?php 
				if($usu_nivel=="ADM"){
					?>
					<td class="order" width="100px"><?php echo $arr['login_usu']; ?></td>
					<?php
				}
				?>
				<td class="order" width="100px"><input type="button" value="Editar" onclick="PetiDados('index.php','3','<?php echo $arr['id_pecas'];?>','<?php echo $arr['tipo_id']; ?>','<?php echo ($arr['nome_pecas']); ?>','<?php echo ($arr['nome_cli']); ?>'); "></td>
			</tr>
			
			<?php
		}
		?>
		<tr>
			<td colspan="5" class="order" style="text-align:center">
				<?php 
					if($qtd[0]>10){
						$pag = ($qtd[0]/10) + 1;
					}else{
						$pag = 1;
					}
					$npag = number_format($pag,0);
					$lipg = 10;
					if($limit<1){
						$tpg1 = 0;
						$tpg2 = $lipg;
					}elseif($npag<$lipg){
						$tpg1 = 0;
						$tpg2 = $lipg;
					}elseif($limit==($npag-1)){
						$tpg1 = $limit-1;
						$tpg2 = $limit+($lipg-1);
					}else{
						$tpg1 = $limit-1;
						$tpg2 = $limit+($lipg-1);
					}
					if($limit==0 || $npag<$lipg){$stl2="display:none";}else{$stl2="";}
					echo "<input type='button' onclick='ajax_pecas(".$_POST['tipo_id'].",".($limit-1).",\"\")' style='border:0;background:none;cursor:pointer;$stl2' value='<<'>";
					for ($nr=1; $nr<=$npag; $nr++){
						if($nr>$tpg1 && $nr<$tpg2){
							if($limit==($nr-1)){ $stl = "font-weight:bold;font-size:8pt";}else{ $stl = ""; }
							echo "<input type='button' onclick='ajax_pecas(".$_POST['tipo_id'].",".($nr-1).",\"\")' style='border:0;background:none;cursor:pointer;$stl' value='".$nr."'>";
							$ult = ($nr-1);
						}
					}
					
					if($limit==($npag-1) || $npag<$lipg){$stl3="display:none";}else{$stl3="";}
					echo "<input type='button' onclick='ajax_pecas(".$_POST['tipo_id'].",".($limit+1).",\"\")' style='border:0;background:none;cursor:pointer;$stl3' value='>>'>";
				?>
			</td>
		</tr>
	</table>
	<br>
	<?php
	}else{
		?>
		<table class="adminlist" width="70%" align="center">
			<tr height="30">
				<td class="order" ><b>Sem registros</b></td>
			</tr>
		</table>
		<?php
	}
}
?>

