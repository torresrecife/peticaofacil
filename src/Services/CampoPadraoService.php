<?php

namespace App\Services;

use App\Infra\Database;

class CampoPadraoService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function createForTipo($tipoId)
	{
		$tipoId = (int) $tipoId;
		$campos = array(
			array('CÓDIGO', 'pj', 'Y'),
			array('VARA', 'vara', null),
			array('COMARCA', 'comarca', null),
			array('ESTADO', 'uf', null),
			array('PROCESSO', 'numero_processo', null),
			array('AUTOR', 'autor', null),
			array('RÉU', 'reu', null),
		);

		foreach ($campos as $campo) {
			$titulo = $this->esc($campo[0]);
			$valor = $this->esc($campo[1]);
			$nomepet = $campo[2];

			$sql = "INSERT INTO tp_inputs_tb SET "
				. "tipo_id = " . $tipoId . ", "
				. "input_title = '" . $titulo . "', "
				. "input_tipo = 'TEXT', "
				. "input_val = '" . $valor . "' ";
			if ($nomepet !== null) {
				$sql .= ", nomepet = '" . $this->esc($nomepet) . "'";
			}
			$ok = mysqli_query($this->db, $sql);
			if (!$ok) {
				return false;
			}
		}

		return true;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
