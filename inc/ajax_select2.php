<?php 
	include("seguranca.php");
	protegePagina();	
	?>
	<option value="">  </option>
	<?php 
	if($_POST['dados']==1){
		if (!class_exists(\App\Services\SelectService::class)) {
			exit;
		}
		$area_id  = $_POST['flag'];
		$service = new \App\Services\SelectService($conexao1);
		foreach ($service->listClientesByArea($area_id) as $row) {
			?>
			<option value="<?php echo $row['cliente_id']; ?>"> <?php echo $row['cliente_name']; ?></option>
			<?php 
		}
	}
?>