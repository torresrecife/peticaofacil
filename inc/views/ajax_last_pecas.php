<?php
/** @var string $texto_pecas */
/** @var array $qpet1 */
/** @var int $a */
/** @var array $usu */
?>
<div class="cpanel">
	<table border="0" width="100%" align="center" style="padding-left: 8px;padding: 4px; border-spacing: 1px;background-color: #FFF;color: #666;">
		<tr>
			<?php $textoLabel = function_exists('app_to_utf8') ? app_to_utf8($texto_pecas) : $texto_pecas; ?>
			<td colspan="3" style="height:30px;border-bottom:1px dotted #ccc;"><label><?php echo $textoLabel; ?></label></td>
		</tr>
		<?php foreach ($qpet1 as $wpet) { ?>
			<tr>
				<td class="order" style="border-bottom:1px dotted #ccc;text-align:left"><span><img src="img/pdf2.png" style="padding-right:10px;margin-top:-10px"></span></td>
				<td style="border-bottom:1px dotted #ccc;">
					<?php
						$nomePecas = function_exists('app_to_utf8') ? app_to_utf8($wpet['nome_pecas']) : $wpet['nome_pecas'];
						$nomeCli = function_exists('app_to_utf8') ? app_to_utf8($wpet['nome_cli']) : $wpet['nome_cli'];
						$nomeUsu = function_exists('app_to_utf8') ? app_to_utf8($wpet['nome_usu']) : $wpet['nome_usu'];
					?>
					<a href="javascript:void(0)" onclick="return PetiDados('index.php','3','<?php echo $wpet['id_pecas'];?>','<?php echo $wpet['tipo_id']; ?>','<?php echo $nomePecas; ?>','<?php echo $nomeCli; ?>');" title="<?php echo strftime("%d/%m/%Y %H:%M:%S", strtotime($wpet['datacad'])); ?>">
						<?php echo $nomeCli; ?>
					</a>
				</td>
				<td style="border-bottom:1px dotted #ccc;"><?php echo $nomeUsu; ?></td>
			</tr>
		<?php } ?>
	</table>
</div>
<div style="margin-top:30px;width:100%;text-align:center;">
	<?php
	echo "<div style='border-bottom:1px dotted #ccc;margin-bottom:10px;height:20px;width:100%;text-align:center;'>";
		echo "<div style='float:left;width:80%;text-align:left;padding-left:30px;'>Petições elaboradas hoje:</div>";
		echo "<div style='float:left;width:auto;text-align:center;'>"  . $a  . " </div>";
	echo "</div>";
	arsort($usu);
	foreach ($usu as $us => $u) {
		$nomeUs = function_exists('app_to_utf8') ? app_to_utf8($us) : $us;
		echo "<div style='height:20px;width:100%;text-align:center;'>";
		echo "<div style='float:left;width:80%;text-align:left;padding-left:30px;'>" . $nomeUs . ":</div>";
		echo "<div style='float:left;width:auto;text-align:center;'>"  . $u  . " </div>";
		echo "</div>";
	}
	?>
</div>
<div style="width:100%;text-align:center;"></div>
