<?php

require_once __DIR__ . '/seguranca.php';
protegePagina();

$usu_setor = $_SESSION['usuarioSetor'];
$usu_nivel = $_SESSION['usuarioNivel'];
$usu_id = $_SESSION['usuarioID'];
$usu_cliente = $_SESSION['usuarioCliente'];

$texto_pecas = "";
if ($usu_nivel == 'USU') {
	$texto_pecas = "Suas últimas petições salvas:";
} else {
	if ($usu_setor != 0) {
		$texto_pecas = "Últimas petições do setor:";
		if ($usu_cliente != 0) {
			$texto_pecas = "Últimas petições da carteira:";
		}
	} else {
		$texto_pecas = "Últimas petições:";
	}
}

$qpet1 = array();
$qpet2 = array();
if (class_exists(\App\Services\PecaService::class)) {
	$pecaService = new \App\Services\PecaService($conexao1);
	$qpet1 = $pecaService->listRecent($usu_nivel, $usu_id, $usu_setor, $usu_cliente, 10);
	$qpet2 = $pecaService->listTodayCounts($usu_nivel, $usu_id, $usu_setor, $usu_cliente);
}

$a = 0;
$usu = array();
foreach ($qpet2 as $wpet2) {
	$nomeUsu = $wpet2["nome_usu"];
	$total = (int) ($wpet2["total"] ?? 0);
	$usu[$nomeUsu] = $total;
	$a += $total;
}

?>
<div class="cpanel">
	<table border="0" width="100%" align="center" style="padding-left: 8px;padding: 4px; border-spacing: 1px;background-color: #FFF;color: #666;">
		<tr>
			<td colspan="3" style="height:30px;border-bottom:1px dotted #ccc;"><label><?php echo $texto_pecas; ?></label></td>
		</tr>
		<?php foreach ($qpet1 as $wpet) { ?>
			<tr>
				<td class="order" style="border-bottom:1px dotted #ccc;text-align:left"><span><img src="img/pdf2.png" style="padding-right:10px;margin-top:-10px"></span></td>
				<td style="border-bottom:1px dotted #ccc;">
					<a href="javascript:void(0)" onclick="return PetiDados('index.php','3','<?php echo $wpet['id_pecas'];?>','<?php echo $wpet['tipo_id']; ?>','<?php echo $wpet['nome_pecas']; ?>','<?php echo $wpet['nome_cli']; ?>');" title="<?php echo strftime("%d/%m/%Y %H:%M:%S", strtotime($wpet['datacad'])); ?>">
						<?php echo $wpet['nome_cli']; ?>
					</a>
				</td>
				<td style="border-bottom:1px dotted #ccc;"><?php echo $wpet['nome_usu']; ?></td>
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
		echo "<div style='height:20px;width:100%;text-align:center;'>";
		echo "<div style='float:left;width:80%;text-align:left;padding-left:30px;'>" . $us . ":</div>";
		echo "<div style='float:left;width:auto;text-align:center;'>"  . $u  . " </div>";
		echo "</div>";
	}
	?>
</div>
<div style="width:100%;text-align:center;"></div>
