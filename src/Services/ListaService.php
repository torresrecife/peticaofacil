<?php

namespace App\Services;

use App\Infra\Database;
use App\Repositories\ListaRepository;

class ListaService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function saveGrupo($idGrupo, $nomeGrupo, $isNew)
	{
		$repo = new ListaRepository($this->db);
		return $repo->saveGroup($idGrupo, $nomeGrupo, $isNew);
	}

	public function saveItens($idGrupo, array $rows)
	{
		$idGrupo = (int) $idGrupo;
		$repo = new ListaRepository($this->db);
		$existing = $repo->listItemIdsByGroup($idGrupo);

		$keep = array();
		$hasRows = false;
		$ok = true;

		foreach ($rows as $row) {
			if (trim((string) ($row['nome_lista'] ?? '')) === '') {
				continue;
			}
			$hasRows = true;

			$idLista = (int) ($row['id_lista'] ?? 0);

			if ($idLista > 0) {
				$ok = $repo->updateItem($idLista, $idGrupo, $row) && $ok;
				$keep[] = $idLista;
			} else {
				$newId = $repo->insertItem($idGrupo, $row);
				$ok = ($newId > 0) && $ok;
				if ($newId) {
					$keep[] = (int) $newId;
				}
			}
		}

		if (!$hasRows) {
			$ok = $repo->deleteItemsByGroup($idGrupo) && $ok;
			return $ok;
		}

		if (!empty($existing)) {
			$ok = $repo->deleteItemsNotIn($idGrupo, $keep) && $ok;
		}

		return $ok;
	}

	public function deleteGroup($idGrupo)
	{
		$idGrupo = (int) $idGrupo;
		$repo = new ListaRepository($this->db);
		$okItems = $repo->deleteItemsByGroup($idGrupo);
		$okGroup = $repo->deleteGroup($idGrupo);
		return $okItems && $okGroup;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
