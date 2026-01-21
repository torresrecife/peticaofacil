<?php

namespace App\Services;

use App\Infra\Database;

class SetorService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function insert(array $data)
	{
		$nome = $this->esc($data['nome_setor'] ?? '');
		$cod = $this->esc($data['cod_setor'] ?? '');
		$now = date("Y-m-d H:i:s");

		$sql = "INSERT INTO tp_setor_tb SET "
			. "nome_setor = '" . $nome . "', "
			. "cod_setor = '" . $cod . "', "
			. "data_cad = '" . $now . "'";

		return mysqli_query($this->db, $sql);
	}

	public function update($id, array $data)
	{
		$id = $this->esc($id);
		$nome = $this->esc($data['nome_setor'] ?? '');
		$cod = $this->esc($data['cod_setor'] ?? '');

		$sql = "UPDATE tp_setor_tb SET "
			. "nome_setor = '" . $nome . "', "
			. "cod_setor = '" . $cod . "' "
			. "WHERE id_setor = " . $id;

		return mysqli_query($this->db, $sql);
	}

	public function delete($id)
	{
		$id = $this->esc($id);
		return mysqli_query($this->db, "DELETE FROM tp_setor_tb WHERE id_setor = " . $id . " LIMIT 1");
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
