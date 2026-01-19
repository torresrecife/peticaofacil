<?php 
	include("seguranca.php");
	protegePagina();	
	?>
	<option value="">  </option>
	<?php 
	if($_POST['dados']==1){
		$area_id  = $_POST['flag'];
		$scli  = " SELECT * FROM tp_clientes_db";
		if ($area_id != 0) {
			$scli .= " where cliente_area = $area_id";
		}
		$scli .= " order by cliente_name";
		$qcli = mysqli_query($conexao1,$scli);
		while($wcli = mysqli_fetch_array($qcli)){
			?>
			<option value="<?php echo $wcli['cliente_id']; ?>"> <?php echo $wcli['cliente_name']; ?></option>
			<?php 
		}
	}
?>