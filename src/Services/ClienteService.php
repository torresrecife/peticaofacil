<?php

namespace App\Services;

use App\Infra\Database;

class ClienteService
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
		$nome = $this->esc($data['cliente_name'] ?? '');
		$cod = $this->esc($data['cliente_cod'] ?? '');
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
		$nome = $this->esc($data['cliente_name'] ?? '');
		$cod = $this->esc($data['cliente_cod'] ?? '');
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

	public function getLastError()
	{
		return $this->lastError;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
