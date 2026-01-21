<?php

namespace App\Repositories;

class ConfigRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function findActiveByTipoId($tipoId)
	{
		if (!$tipoId) {
			return null;
		}

		$tipoId = mysqli_real_escape_string($this->db, $tipoId);
		$sql = "SELECT c.* FROM tp_config_db as c "
			. "JOIN tp_tipo_tb as t on t.id_db=c.id_db "
			. "WHERE t.tipo_id = '" . $tipoId . "' and c.stt='Y'";

		$qdb = mysqli_query($this->db, $sql);
		if (!$qdb) {
			return null;
		}

		return mysqli_fetch_assoc($qdb) ?: null;
	}
}
