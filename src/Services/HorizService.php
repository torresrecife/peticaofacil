<?php

namespace App\Services;

use App\Infra\Database;

class HorizService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function buildOptions(array $data, $ddInput = null)
	{
		$hinput = $data['hinput'] ?? '';
		$inputdb0 = $data['inputdb_0'] ?? '';
		$inputdb1 = $data['inputdb_1'] ?? '';
		$inputdb3 = $data['inputdb_3'] ?? '';
		$inputdb4 = $data['inputdb_4'] ?? '';
		$inputdb5 = $data['inputdb_5'] ?? '';


		if ($inputdb4 === 'vert') {
			$conca = '*';
			$where = $inputdb3 ? $inputdb3 : '1=1';
			$and = '';
		} elseif ($inputdb4 === 'hori') {
			$conca = "id_lista,id_grupo,concat(return_2,return_3,return_4,return_5,return_6) as nome_lista";
			if ($inputdb5) {
				$hinputEsc = Database::escape($this->db, $hinput);
				$where = " nome_lista='" . $hinputEsc . "' ";
			} else {
				$where = '1=1';
			}
			$and = $inputdb3 ? "and " . $inputdb3 : '';
		} else {
			$conca = '*';
			$where = '1=1';
			$and = '';
		}

		$qsel = mysqli_query($this->db, "SELECT $conca FROM " . $inputdb0 . " WHERE $where $and ORDER BY " . $inputdb1 . " asc ");
		if (!$qsel) {
			return "<option></option>";
		}

		$options = "<option></option>";
		while ($wsel = mysqli_fetch_array($qsel)) {
			$selected = (trim((string) $ddInput) === trim((string) ($wsel[$inputdb1] ?? ''))) ? 'selected' : '';
			$options .= "<option value='" . $wsel[2] . "' ident='" . $wsel[0] . "' " . $selected . " >" . $wsel[$inputdb1] . "</option>";
		}

		return $options;
	}
}
