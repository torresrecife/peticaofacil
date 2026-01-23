<?php

namespace App\Repositories;

use App\Infra\Database;

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

	public function create(array $data)
	{
		$tiposetor = $this->esc($data['tiposetor'] ?? '');
		$tipoclien = $this->esc($data['tipoclien'] ?? 0);
		$tipoarqui = $this->esc($data['tipoarqui'] ?? '');
		$tiposql = $this->esc($data['tiposql'] ?? '');
		$tipotitle = strtoupper($data['tipotitle'] ?? '');
		$tipotitlePre = strtoupper($data['tipotitle_pre'] ?? '');

		$sql = "INSERT INTO tp_tipo_tb SET "
			. "id_db = '" . $tiposql . "', "
			. "tipo_nome = '" . $this->esc($tipotitle) . "', "
			. "nome_pre = '" . $this->esc($tipotitlePre) . "', "
			. "id_cliente = '" . $tipoclien . "', "
			. "tipo_data = now(), "
			. "tipo_stt = 'Y', "
			. "id_setor = '" . $tiposetor . "', "
			. "tipo_arq = '" . $tipoarqui . "'";

		return mysqli_query($this->db, $sql);
	}

	public function deleteTipo($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		return mysqli_query($this->db, "DELETE FROM tp_tipo_tb WHERE tipo_id = " . $tipoId . " LIMIT 1");
	}

	public function updateCabec($tipoId, $texto)
	{
		$tipoId = $this->esc($tipoId);
		$texto = $this->esc($texto);
		return mysqli_query($this->db, "UPDATE tp_tipo_tb SET cod_cabec = '" . $texto . "' WHERE tipo_id = " . $tipoId);
	}

	public function updateRodap($tipoId, $texto)
	{
		$tipoId = $this->esc($tipoId);
		$texto = $this->esc($texto);
		return mysqli_query($this->db, "UPDATE tp_tipo_tb SET cod_rodap = '" . $texto . "' WHERE tipo_id = " . $tipoId);
	}

	public function getSetorCodeByTipo($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$sql = "SELECT s.cod_setor FROM tp_tipo_tb AS t "
			. "JOIN tp_setor_tb AS s ON s.id_setor=t.id_setor "
			. "WHERE t.tipo_id = '" . $tipoId . "'";
		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return null;
		}
		$row = mysqli_fetch_array($query);
		return $row['cod_setor'] ?? null;
	}

	public function getTipoArquivoById($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$query = mysqli_query($this->db, "SELECT tipo_arq FROM tp_tipo_tb WHERE tipo_id = '" . $tipoId . "' LIMIT 1");
		if (!$query) {
			return null;
		}
		$row = mysqli_fetch_array($query);
		return $row['tipo_arq'] ?? null;
	}

	public function getCabecRodapById($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$query = mysqli_query($this->db, "SELECT cod_cabec, cod_rodap FROM tp_tipo_tb WHERE tipo_id = '" . $tipoId . "' LIMIT 1");
		if (!$query) {
			return null;
		}
		return mysqli_fetch_assoc($query) ?: null;
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

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
