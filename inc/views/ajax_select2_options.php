<?php
/** @var array $rows */
?>
<option value="">  </option>
<?php foreach ($rows as $row) { ?>
	<option value="<?php echo $row['cliente_id']; ?>"> <?php echo $row['cliente_name']; ?></option>
<?php } ?>
