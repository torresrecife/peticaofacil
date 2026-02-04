<?php

namespace App\Repositories;

use App\Infra\Database;

class SelectRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listDadosByInput($campoId)
	{
		$campoId = $this->esc($campoId);
		$query = mysqli_query($this->db, "SELECT id_dados, nome_dados, return_1 FROM tp_dados_tb WHERE id_input = " . $campoId);
		if (!$query) {
			return array();
		}

		$rows = array();
		while ($row = mysqli_fetch_assoc($query)) {
			$rows[] = $row;
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

	public function listRowsByTable($table, $where, $andClause, $orderByField)
	{
		$sql = "SELECT * FROM " . $table;
		if ($where !== '') {
			$sql .= " WHERE " . $where;
		}
		if ($andClause !== '') {
			$sql .= " " . $andClause;
		}
		if ($orderByField !== '') {
			$sql .= " ORDER BY '" . $orderByField . "' asc ";
		}

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
