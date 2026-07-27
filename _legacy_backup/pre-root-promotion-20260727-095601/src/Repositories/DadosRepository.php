<?php

namespace App\Repositories;

use App\Infra\Database;

class DadosRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listDadosMap()
	{
		$query = mysqli_query($this->db, "SELECT id_dados, nome_dados FROM tp_dados_tb");
		if (!$query) {
			return array();
		}
		$map = array();
		while ($row = mysqli_fetch_array($query)) {
			$map[$row['id_dados']] = $row['nome_dados'];
		}
		return $map;
	}

	public function listByInput($inputId, $setorId = null)
	{
		$inputId = $this->esc($inputId);
		$where = "id_input = '" . $inputId . "'";
		if ($setorId !== null && (int) $setorId !== 0) {
			$where .= " and id_setor = " . (int) $setorId;
		}
		$query = mysqli_query($this->db, "SELECT * FROM tp_dados_tb WHERE " . $where . " ORDER BY nome_dados asc ");
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
