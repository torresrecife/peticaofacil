<?php
/** @var array $rows */
?>
<select name="inputLoad" id="inputLoad" class="input-default" style="width:160px; height:20px">
	<option></option>
	<?php foreach ($rows as $row) {
		$id = $row['id_input'];
		$title = $row['input_title'];
		?>
		<option value='$(this).val($("#campo<?php echo $id; ?>").val());'><?php echo $title; ?></option>
	<?php } ?>
</select>
