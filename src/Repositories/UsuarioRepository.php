<?php

namespace App\Repositories;

use App\Infra\Database;

class UsuarioRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listAllWithRelations()
	{
		$sql = "SELECT *, u.data_cad as datacad "
			. "FROM tp_usu_tb as u "
			. "LEFT JOIN tp_setor_tb as s on s.id_setor=u.id_setor "
			. "LEFT JOIN tp_clientes_db AS c ON c.cliente_id=u.id_cliente "
			. "ORDER by u.id_usu";

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

	public function getRow($id)
	{
		$id = $this->esc($id);
		$result = mysqli_query($this->db, "SELECT * FROM tp_usu_tb WHERE id_usu = " . $id);
		if (!$result) {
			return null;
		}
		return mysqli_fetch_row($result) ?: null;
	}

	public function insert(array $data)
	{
		$nomeRaw = $data['nome_usu'] ?? '';
		$loginRaw = $data['login_usu'] ?? '';
		$emailRaw = $data['email_usu'] ?? '';
		$nivelRaw = $data['nivel_usu'] ?? '';
		$nome = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($nomeRaw) : $nomeRaw);
		$login = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($loginRaw) : $loginRaw);
		$email = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($emailRaw) : $emailRaw);
		$nivel = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($nivelRaw) : $nivelRaw);
		$setor = $this->esc($data['setor_usu'] ?? '');
		$cliente = $this->esc($data['banco_neo'] ?? '');
		$senha = $data['senha_usu1'] ?? '';
		$now = date("Y-m-d H:i:s");

		$sql = "INSERT INTO tp_usu_tb SET "
			. "nome_usu = '" . $nome . "', "
			. "login_usu = '" . $login . "', ";
		if ($senha !== '') {
			$sql .= "senha_usu = '" . md5($senha) . "', ";
		}
		$sql .= "email_usu = '" . $email . "', "
			. "nivel_usu = '" . $nivel . "', "
			. "id_setor = '" . $setor . "', "
			. "id_cliente = '" . $cliente . "', "
			. "acesso_usu = NULL, "
			. "data_cad = '" . $now . "' ";

		return mysqli_query($this->db, $sql);
	}

	public function update($id, array $data)
	{
		$id = $this->esc($id);
		$nomeRaw = $data['nome_usu'] ?? '';
		$loginRaw = $data['login_usu'] ?? '';
		$emailRaw = $data['email_usu'] ?? '';
		$nivelRaw = $data['nivel_usu'] ?? '';
		$nome = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($nomeRaw) : $nomeRaw);
		$login = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($loginRaw) : $loginRaw);
		$email = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($emailRaw) : $emailRaw);
		$nivel = $this->esc(function_exists('app_from_utf8') ? app_from_utf8($nivelRaw) : $nivelRaw);
		$setor = $this->esc($data['setor_usu'] ?? '');
		$cliente = $this->esc($data['banco_neo'] ?? '');
		$senha = $data['senha_usu1'] ?? '';

		$sql = "UPDATE tp_usu_tb SET "
			. "nome_usu = '" . $nome . "', "
			. "login_usu = '" . $login . "', ";
		if ($senha !== '') {
			$sql .= "senha_usu = '" . md5($senha) . "', ";
		}
		$sql .= "email_usu = '" . $email . "', "
			. "nivel_usu = '" . $nivel . "', "
			. "id_setor = '" . $setor . "', "
			. "id_cliente = '" . $cliente . "' "
			. "WHERE id_usu = " . $id;

		return mysqli_query($this->db, $sql);
	}

	public function delete($id)
	{
		$id = $this->esc($id);
		return mysqli_query($this->db, "DELETE FROM tp_usu_tb WHERE id_usu = '" . $id . "' LIMIT 1");
	}

	public function updatePassword($id, $senha)
	{
		$id = $this->esc($id);
		if ($senha === '') {
			return false;
		}
		$sql = "UPDATE tp_usu_tb SET senha_usu = '" . md5($senha) . "' WHERE id_usu = " . $id;
		return mysqli_query($this->db, $sql);
	}

	public function updateAcesso($id, $datetime)
	{
		$id = $this->esc($id);
		$datetime = $this->esc($datetime);
		return mysqli_query($this->db, "UPDATE tp_usu_tb SET acesso_usu = '" . $datetime . "' where id_usu = " . $id);
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
