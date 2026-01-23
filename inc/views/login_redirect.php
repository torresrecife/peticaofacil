<?php
/** @var string $target */
/** @var string $message */
?>
<script language="javascript">
<?php if ($message !== '') { ?>
	alert("<?php echo $message; ?>");
<?php } ?>
	window.location="<?php echo $target; ?>";
</script>
