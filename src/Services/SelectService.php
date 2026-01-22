<?php

namespace App\Services;

use App\Infra\Database;

class SelectService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listDadosByInput($campoId)
	{
		$campoId = $this->esc($campoId);
		$query = mysqli_query($this->db, "SELECT nome_dados FROM tp_dados_tb WHERE id_input = " . $campoId);
		if (!$query) {
			return array();
		}

		$rows = array();
		while ($row = mysqli_fetch_assoc($query)) {
			$rows[] = $row['nome_dados'];
		}

		return $rows;
	}

	public function listClientesByArea($areaId)
	{
		$areaId = (int) $areaId;
		$sql = "SELECT cliente_id, cliente_name FROM tp_clientes_db";
		if ($areaId !== 0) {
			$sql .= " where cliente_area = " . $areaId;
		}
		$sql .= " order by cliente_name";

		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return array();
		}

		$rows = array();
		while ($row = mysqli_fetch_assoc($query)) {
			$rows[] = $row;
		}

		return $rows;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
