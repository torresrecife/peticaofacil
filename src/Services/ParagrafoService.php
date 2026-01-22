<?php

namespace App\Services;

use App\Infra\Database;

class ParagrafoService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function create($tipoId, $titulo)
	{
		$tipoId = $this->esc($tipoId);
		$tituloUpper = strtoupper($titulo);
		$fundTitulo = $this->esc(utf8_encode($tituloUpper));
		$title = '<div class="titulos">' . $tituloUpper . '</div><p>&nbsp;</p><p align="left"></p>';
		$titleEscaped = $this->esc($title);

		$qOrder = mysqli_query($this->db, "SELECT MAX(fund_order) FROM tp_funda_tb WHERE tipo_id = " . $tipoId . " LIMIT 1");
		$wOrder = mysqli_fetch_array($qOrder);
		$order = (int) ($wOrder[0] ?? 0) + 1;

		$sql = "INSERT INTO tp_funda_tb SET "
			. "tipo_id = " . $tipoId . ", "
			. "fund_titulo = '" . $fundTitulo . "', "
			. "fund_text = '" . $titleEscaped . "', "
			. "fund_order = " . $order;

		return mysqli_query($this->db, $sql);
	}

	public function updateText($fundId, $text)
	{
		$fundId = $this->esc($fundId);
		$text = str_replace("%u2013", "-", $text);
		$text = $this->esc($text);

		$sql = "UPDATE tp_funda_tb SET fund_text = '" . $text . "' WHERE fund_id = " . $fundId;
		return mysqli_query($this->db, $sql);
	}

	public function delete($fundId)
	{
		$fundId = $this->esc($fundId);
		return mysqli_query($this->db, "DELETE FROM tp_funda_tb WHERE fund_id = " . $fundId . " LIMIT 1");
	}

	public function listByTipoWithArquivo($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$sql = "SELECT tf.*, tt.tipo_arq, tt.cod_cabec, tt.cod_rodap FROM tp_funda_tb as tf "
			. "JOIN tp_tipo_tb as tt on tt.tipo_id = tf.tipo_id "
			. "WHERE tt.tipo_id = " . $tipoId . " "
			. "ORDER BY tf.fund_order ASC";
		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return array();
		}
		$rows = array();
		while ($row = mysqli_fetch_array($query)) {
			$rows[] = $row;
		}
		return $rows;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
