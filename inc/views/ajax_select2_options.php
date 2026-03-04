<?php
/** @var array $rows */
?>
<option value="">  </option>
<?php foreach ($rows as $row) { ?>
	<?php $clienteNome = function_exists('app_to_utf8') ? app_to_utf8($row['cliente_name']) : $row['cliente_name']; ?>
	<option value="<?php echo $row['cliente_id']; ?>"> <?php echo $clienteNome; ?></option>
<?php } ?>
