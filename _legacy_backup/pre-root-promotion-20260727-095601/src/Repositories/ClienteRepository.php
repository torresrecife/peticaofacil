<?php

namespace App\Repositories;

use App\Infra\Database;

class ClienteRepository
{
	private $db;
	private $lastError;

	public function __construct($db)
	{
		$this->db = $db;
		$this->lastError = null;
	}

	public function insert(array $data)
	{
		$nomeRaw = $data['cliente_name'] ?? '';
		$codRaw = $data['cliente_cod'] ?? '';
		$nomeDb = function_exists('app_from_utf8') ? app_from_utf8($nomeRaw) : $nomeRaw;
		$codDb = function_exists('app_from_utf8') ? app_from_utf8($codRaw) : $codRaw;
		$nome = $this->esc($nomeDb);
		$cod = $this->esc($codDb);
		$area = Database::trimOrNull($data['cliente_area'] ?? '');
		$areaSql = $area === null ? 'NULL' : "'" . $this->esc($area) . "'";

		$sql = "INSERT INTO tp_clientes_db SET "
			. "cliente_name = '" . $nome . "', "
			. "cliente_cod = '" . $cod . "', "
			. "cliente_area = " . $areaSql . ", "
			. "cliente_creator = '" . date("Y-m-d H:i:s") . "'";

		$result = mysqli_query($this->db, $sql);
		if (!$result) {
			$this->lastError = mysqli_error($this->db);
		}
		return $result;
	}

	public function update($id, array $data)
	{
		$id = $this->esc($id);
		$nomeRaw = $data['cliente_name'] ?? '';
		$codRaw = $data['cliente_cod'] ?? '';
		$nomeDb = function_exists('app_from_utf8') ? app_from_utf8($nomeRaw) : $nomeRaw;
		$codDb = function_exists('app_from_utf8') ? app_from_utf8($codRaw) : $codRaw;
		$nome = $this->esc($nomeDb);
		$cod = $this->esc($codDb);
		$area = Database::trimOrNull($data['cliente_area'] ?? '');
		$areaSql = $area === null ? 'NULL' : "'" . $this->esc($area) . "'";

		$sql = "UPDATE tp_clientes_db SET "
			. "cliente_name = '" . $nome . "', "
			. "cliente_cod = '" . $cod . "', "
			. "cliente_area = " . $areaSql . " "
			. "WHERE cliente_id = " . $id;

		$result = mysqli_query($this->db, $sql);
		if (!$result) {
			$this->lastError = mysqli_error($this->db);
		}
		return $result;
	}

	public function delete($id)
	{
		$id = $this->esc($id);
		$result = mysqli_query($this->db, "DELETE FROM tp_clientes_db WHERE cliente_id = " . $id . " LIMIT 1");
		if (!$result) {
			$this->lastError = mysqli_error($this->db);
		}
		return $result;
	}

	public function getRow($id)
	{
		$id = $this->esc($id);
		$result = mysqli_query($this->db, "SELECT * FROM tp_clientes_db WHERE cliente_id = " . $id);
		if (!$result) {
			$this->lastError = mysqli_error($this->db);
			return null;
		}
		return mysqli_fetch_row($result) ?: null;
	}

	public function listAllWithSetor()
	{
		$sql = "SELECT * FROM tp_clientes_db AS c "
			. "JOIN tp_setor_tb AS s ON s.id_setor=c.cliente_area "
			. "ORDER BY c.cliente_id";
		$result = mysqli_query($this->db, $sql);
		if (!$result) {
			return array();
		}
		$rows = array();
		while ($row = mysqli_fetch_array($result)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function listAll()
	{
		$result = mysqli_query($this->db, "SELECT * FROM tp_clientes_db ORDER BY cliente_id");
		if (!$result) {
			return array();
		}
		$rows = array();
		while ($row = mysqli_fetch_array($result)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function getLastError()
	{
		return $this->lastError;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
