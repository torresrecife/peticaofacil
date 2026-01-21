<?php

namespace App\Services;

use App\Infra\Database;

class SqlConfigService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function insert(array $data)
	{
		$nome = $this->esc($data['nome_db'] ?? '');
		$stt = $this->esc($data['stt'] ?? '');
		$ip = $this->esc($data['ip_db'] ?? '');
		$dataDb = $this->esc($data['data_db'] ?? '');
		$usu = $this->esc($data['usu_db'] ?? '');
		$senha = $this->esc($data['senha_db'] ?? '');
		$table = $this->esc($data['table_db'] ?? '');
		$chave = $this->esc($data['chave_db'] ?? '');
		$queryDb = $this->esc($data['query_db'] ?? '');
		$whereDb = $this->esc($data['where_db'] ?? '');

		$sql = "INSERT INTO tp_config_db SET "
			. "nome_db = '" . $nome . "', "
			. "stt = '" . $stt . "', "
			. "ip_db = '" . $ip . "', "
			. "data_db = '" . $dataDb . "', "
			. "usu_db = '" . $usu . "', "
			. "senha_db = '" . $senha . "', "
			. "table_db = '" . $table . "', "
			. "chave_db = '" . $chave . "', "
			. "query_db = '" . $queryDb . "', "
			. "where_db = '" . $whereDb . "'";

		return mysqli_query($this->db, $sql);
	}

	public function update($id, array $data)
	{
		$id = $this->esc($id);
		$nome = $this->esc($data['nome_db'] ?? '');
		$stt = $this->esc($data['stt'] ?? '');
		$ip = $this->esc($data['ip_db'] ?? '');
		$dataDb = $this->esc($data['data_db'] ?? '');
		$usu = $this->esc($data['usu_db'] ?? '');
		$senha = $this->esc($data['senha_db'] ?? '');
		$table = $this->esc($data['table_db'] ?? '');
		$chave = $this->esc($data['chave_db'] ?? '');
		$queryDb = $this->esc($data['query_db'] ?? '');
		$whereDb = $this->esc($data['where_db'] ?? '');

		$sql = "UPDATE tp_config_db SET "
			. "nome_db = '" . $nome . "', "
			. "stt = '" . $stt . "', "
			. "ip_db = '" . $ip . "', "
			. "data_db = '" . $dataDb . "', "
			. "usu_db = '" . $usu . "', "
			. "senha_db = '" . $senha . "', "
			. "table_db = '" . $table . "', "
			. "chave_db = '" . $chave . "', "
			. "query_db = '" . $queryDb . "', "
			. "where_db = '" . $whereDb . "' "
			. "WHERE id_db = " . $id;

		return mysqli_query($this->db, $sql);
	}

	public function delete($id)
	{
		$id = $this->esc($id);
		return mysqli_query($this->db, "DELETE FROM tp_config_db WHERE id_db = " . $id . " LIMIT 1");
	}

	public function getRow($id)
	{
		$id = $this->esc($id);
		$result = mysqli_query($this->db, "SELECT * FROM tp_config_db WHERE id_db = " . $id);
		if (!$result) {
			return null;
		}
		return mysqli_fetch_row($result) ?: null;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
