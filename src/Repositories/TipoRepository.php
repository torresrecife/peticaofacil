<?php

namespace App\Repositories;

class TipoRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function findWithClienteById($tipoId)
	{
		if (!$tipoId) {
			return null;
		}

		$tipoId = mysqli_real_escape_string($this->db, $tipoId);
		$sql = "SELECT t.tipo_nome, t.tipo_id, cliente_name "
			. "FROM tp_tipo_tb as t "
			. "LEFT JOIN tp_clientes_db as c on c.cliente_id=t.id_cliente "
			. "WHERE t.tipo_id = '" . $tipoId . "'";

		$q = mysqli_query($this->db, $sql);
		if (!$q) {
			return null;
		}

		return mysqli_fetch_assoc($q) ?: null;
	}

	public function listBySetor($setorId = null)
	{
		$where = '';
		if ($setorId !== null && $setorId !== '') {
			$setorId = mysqli_real_escape_string($this->db, $setorId);
			$where = "where id_setor = '" . $setorId . "' ";
		}
		$sql = "SELECT * from tp_tipo_tb as t " . $where . "ORDER by t.tipo_id";
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

	public function listForSelect($clienteList = null, $setorId = null)
	{
		$where = "where 1=1 ";
		if ($clienteList !== null && $clienteList !== '' && (string) $clienteList !== '0') {
			$clienteIds = $this->sanitizeIdList($clienteList);
			$where .= "and id_cliente in (0," . $clienteIds . ") ";
		}
		if ($setorId !== null && (int) $setorId !== 0) {
			$setorId = mysqli_real_escape_string($this->db, $setorId);
			$where .= "and id_setor = " . $setorId . " ";
		}
		$sql = "SELECT MIN(tipo_id) AS tipo_id, tipo_nome "
			. "FROM tp_tipo_tb " . $where
			. "GROUP BY tipo_nome "
			. "ORDER BY tipo_nome";
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

	public function listWithRelations($clienteList = null, $setorId = null)
	{
		$where = "where 1=1 ";
		if ($clienteList !== null && $clienteList !== '' && (string) $clienteList !== '0') {
			$clienteIds = $this->sanitizeIdList($clienteList);
			$where .= "and a.id_cliente in (0," . $clienteIds . ") ";
		}
		if ($setorId !== null && (int) $setorId !== 0) {
			$setorId = mysqli_real_escape_string($this->db, $setorId);
			$where .= "and a.id_setor = " . $setorId . " ";
		}
		$sql = "SELECT *, a.tipo_id, a.tipo_nome, a.nome_pre, a.nome_pos, a.id_setor "
			. "FROM tp_tipo_tb as a "
			. "left join tp_clientes_db as c on a.id_cliente=c.cliente_id "
			. "left join tp_setor_tb AS s ON s.id_setor=a.id_setor "
			. $where
			. "ORDER BY a.id_setor asc, c.cliente_name, a.tipo_nome";
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

	private function sanitizeIdList($list)
	{
		$parts = preg_split('/\s*,\s*/', (string) $list);
		$clean = array();
		foreach ($parts as $part) {
			if ($part !== '' && ctype_digit($part)) {
				$clean[] = $part;
			}
		}
		return $clean ? implode(',', $clean) : '0';
	}
}
