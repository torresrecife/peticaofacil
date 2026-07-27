<?php

namespace App\Repositories;

use App\Infra\Database;

class UsuarioAuthRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function findByLoginAndSenha($login, $senhaHash, $caseSensitive = false)
	{
		$login = Database::escape($this->db, $login);
		$senhaHash = Database::escape($this->db, $senhaHash);
		$cs = $caseSensitive ? 'BINARY' : '';

		$sql = "SELECT * FROM tp_usu_tb "
			. "WHERE " . $cs . " login_usu = '" . $login . "' "
			. "AND " . $cs . " senha_usu = '" . $senhaHash . "' "
			. "LIMIT 1";

		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return null;
		}

		return mysqli_fetch_assoc($query) ?: null;
	}

	public function getAcessoById($usuarioId)
	{
		$usuarioId = Database::escape($this->db, $usuarioId);
		$qpass = mysqli_query($this->db, "SELECT acesso_usu FROM tp_usu_tb where id_usu = " . $usuarioId);
		if (!$qpass) {
			return null;
		}
		$wpass = mysqli_fetch_assoc($qpass);
		return $wpass['acesso_usu'] ?? null;
	}

	public function updateAcesso($usuarioId, $datetime)
	{
		$usuarioId = Database::escape($this->db, $usuarioId);
		$datetime = Database::escape($this->db, $datetime);
		mysqli_query($this->db, "UPDATE tp_usu_tb SET acesso_usu = '" . $datetime . "' where id_usu = " . $usuarioId);
	}
}
