<?php

namespace App\Repositories;

class ListaRepository
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function listGroups()
	{
		$q = mysqli_query($this->db, "SELECT * from tp_grupo_tb as g ORDER by g.id_grupo");
		if (!$q) {
			return array();
		}

		$rows = array();
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function findGroupById($idGrupo)
	{
		if ($idGrupo === '' || $idGrupo === null) {
			return null;
		}

		$idGrupo = mysqli_real_escape_string($this->db, $idGrupo);
		$q = mysqli_query($this->db, "SELECT * FROM tp_grupo_tb WHERE id_grupo = " . $idGrupo);
		if (!$q) {
			return null;
		}

		return mysqli_fetch_assoc($q) ?: null;
	}

	public function nextGroupId()
	{
		$q = mysqli_query($this->db, "SELECT max(id_grupo)+1 FROM tp_grupo_tb limit 1 ");
		if (!$q) {
			return 1;
		}
		$row = mysqli_fetch_row($q);
		$next = (int) ($row[0] ?? 1);
		return $next > 0 ? $next : 1;
	}

	public function listItemsByGroup($idGrupo)
	{
		if ($idGrupo === '' || $idGrupo === null) {
			return array();
		}

		$idGrupo = mysqli_real_escape_string($this->db, $idGrupo);
		$q = mysqli_query($this->db, "SELECT * FROM tp_lista_tb WHERE id_grupo = " . $idGrupo);
		if (!$q) {
			return array();
		}

		$rows = array();
		while ($row = mysqli_fetch_assoc($q)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function saveGroup($idGrupo, $nomeGrupo, $isNew)
	{
		$idGrupo = (int) $idGrupo;
		$nomeGrupo = $this->esc($nomeGrupo);

		if ($isNew) {
			return mysqli_query($this->db, "INSERT INTO tp_grupo_tb SET nome_grupo = '" . $nomeGrupo . "', id_grupo = " . $idGrupo . ", data_cad = now()");
		}

		return mysqli_query($this->db, "UPDATE tp_grupo_tb SET nome_grupo = '" . $nomeGrupo . "', data_cad = now() WHERE id_grupo = " . $idGrupo);
	}

	public function listItemIdsByGroup($idGrupo)
	{
		$idGrupo = (int) $idGrupo;
		$q = mysqli_query($this->db, "SELECT id_lista FROM tp_lista_tb WHERE id_grupo = " . $idGrupo);
		if (!$q) {
			return array();
		}

		$ids = array();
		while ($row = mysqli_fetch_assoc($q)) {
			$ids[] = (int) $row['id_lista'];
		}
		return $ids;
	}

	public function updateItem($idLista, $idGrupo, array $data)
	{
		$idLista = (int) $idLista;
		$idGrupo = (int) $idGrupo;
		$idSetor = (int) ($data['id_setor'] ?? 1);

		$nomeLista = $this->esc($data['nome_lista'] ?? '');
		$return1 = $this->esc($data['return_1'] ?? '');
		$return2 = $this->esc($data['return_2'] ?? '');
		$return3 = $this->esc($data['return_3'] ?? '');
		$return4 = $this->esc($data['return_4'] ?? '');
		$return5 = $this->esc($data['return_5'] ?? '');
		$return6 = $this->esc($data['return_6'] ?? '');

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

		return mysqli_query($this->db, $sql);
	}

	public function insertItem($idGrupo, array $data)
	{
		$idGrupo = (int) $idGrupo;
		$idSetor = (int) ($data['id_setor'] ?? 1);

		$nomeLista = $this->esc($data['nome_lista'] ?? '');
		$return1 = $this->esc($data['return_1'] ?? '');
		$return2 = $this->esc($data['return_2'] ?? '');
		$return3 = $this->esc($data['return_3'] ?? '');
		$return4 = $this->esc($data['return_4'] ?? '');
		$return5 = $this->esc($data['return_5'] ?? '');
		$return6 = $this->esc($data['return_6'] ?? '');

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

		$ok = mysqli_query($this->db, $sql);
		if (!$ok) {
			return 0;
		}

		return (int) mysqli_insert_id($this->db);
	}

	public function deleteItemsByGroup($idGrupo)
	{
		$idGrupo = (int) $idGrupo;
		return mysqli_query($this->db, "DELETE FROM tp_lista_tb WHERE id_grupo = " . $idGrupo);
	}

	public function deleteItemsNotIn($idGrupo, array $keep)
	{
		$idGrupo = (int) $idGrupo;
		if (empty($keep)) {
			return $this->deleteItemsByGroup($idGrupo);
		}

		$keepList = implode(',', array_map('intval', $keep));
		$sql = "DELETE FROM tp_lista_tb WHERE id_grupo = " . $idGrupo . " AND id_lista NOT IN (" . $keepList . ")";
		return mysqli_query($this->db, $sql);
	}

	private function esc($value)
	{
		return mysqli_real_escape_string($this->db, (string) $value);
	}
}
