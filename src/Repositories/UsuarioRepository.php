<?php

namespace App\Repositories;

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
}
