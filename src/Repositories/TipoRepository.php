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
}
