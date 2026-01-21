<?php

namespace App\Services;

use App\Infra\Database;

class ListaService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function saveGrupo($idGrupo, $nomeGrupo, $isNew)
	{
		$idGrupo = (int) $idGrupo;
		$nomeGrupo = $this->esc($nomeGrupo);

		if ($isNew) {
			return mysqli_query($this->db, "INSERT INTO tp_grupo_tb SET nome_grupo = '" . $nomeGrupo . "', id_grupo = " . $idGrupo . ", data_cad = now()");
		}

		return mysqli_query($this->db, "UPDATE tp_grupo_tb SET nome_grupo = '" . $nomeGrupo . "', data_cad = now() WHERE id_grupo = " . $idGrupo);
	}

	public function saveItens($idGrupo, array $rows)
	{
		$idGrupo = (int) $idGrupo;
		$existing = array();
		$existingQuery = mysqli_query($this->db, "SELECT id_lista FROM tp_lista_tb WHERE id_grupo = " . $idGrupo);
		if ($existingQuery) {
			while ($row = mysqli_fetch_assoc($existingQuery)) {
				$existing[] = (int) $row['id_lista'];
			}
		}

		$keep = array();
		$ok = true;

		foreach ($rows as $row) {
			$nomeLista = $this->esc($row['nome_lista'] ?? '');
			if ($nomeLista === '') {
				continue;
			}

			$idLista = (int) ($row['id_lista'] ?? 0);
			$idSetor = (int) ($row['id_setor'] ?? 1);

			$return1 = $this->esc($row['return_1'] ?? '');
			$return2 = $this->esc($row['return_2'] ?? '');
			$return3 = $this->esc($row['return_3'] ?? '');
			$return4 = $this->esc($row['return_4'] ?? '');
			$return5 = $this->esc($row['return_5'] ?? '');
			$return6 = $this->esc($row['return_6'] ?? '');

			if ($idLista > 0) {
				$sql = "UPDATE tp_lista_tb SET "
					. "id_grupo = " . $idGrupo . ", "
					. "nome_lista = '" . $nomeLista . "', "
					. "return_1 = '" . $return1 . "', "
					. "return_2 = '" . $return2 . "', "
					. "return_3 = '" . $return3 . "', "
					. "return_4 = '" . $return4 . "', "
					. "return_5 = '" . $return5 . "', "
					. "return_6 = '" . $return6 . "', "
					. "id_setor = " . $idSetor . " "
					. "WHERE id_lista = " . $idLista . " LIMIT 1";
				$ok = mysqli_query($this->db, $sql) && $ok;
				$keep[] = $idLista;
			} else {
				$sql = "INSERT INTO tp_lista_tb SET "
					. "id_grupo = " . $idGrupo . ", "
					. "nome_lista = '" . $nomeLista . "', "
					. "return_1 = '" . $return1 . "', "
					. "return_2 = '" . $return2 . "', "
					. "return_3 = '" . $return3 . "', "
					. "return_4 = '" . $return4 . "', "
					. "return_5 = '" . $return5 . "', "
					. "return_6 = '" . $return6 . "', "
					. "id_setor = " . $idSetor . ", "
					. "data_cad = now()";
				$ok = mysqli_query($this->db, $sql) && $ok;
			}
		}

		if (empty($keep)) {
			$ok = mysqli_query($this->db, "DELETE FROM tp_lista_tb WHERE id_grupo = " . $idGrupo) && $ok;
			return $ok;
		}

		if (!empty($existing)) {
			$keepList = implode(',', array_map('intval', $keep));
			$sql = "DELETE FROM tp_lista_tb WHERE id_grupo = " . $idGrupo . " AND id_lista NOT IN (" . $keepList . ")";
			$ok = mysqli_query($this->db, $sql) && $ok;
		}

		return $ok;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
