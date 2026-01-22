<?php

namespace App\Services;

use App\Infra\Database;

class TipoService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function create(array $data)
	{
		$tiposetor = $this->esc($data['tiposetor'] ?? '');
		$tipoclien = $this->esc($data['tipoclien'] ?? 0);
		$tipoarqui = $this->esc($data['tipoarqui'] ?? '');
		$tiposql = $this->esc($data['tiposql'] ?? '');
		$tipotitle = strtoupper($data['tipotitle'] ?? '');
		$tipotitlePre = strtoupper($data['tipotitle_pre'] ?? '');

		$sql = "INSERT INTO tp_tipo_tb SET "
			. "id_db = '" . $tiposql . "', "
			. "tipo_nome = '" . $this->esc($tipotitle) . "', "
			. "nome_pre = '" . $this->esc($tipotitlePre) . "', "
			. "id_cliente = '" . $tipoclien . "', "
			. "tipo_data = now(), "
			. "tipo_stt = 'Y', "
			. "id_setor = '" . $tiposetor . "', "
			. "tipo_arq = '" . $tipoarqui . "'";

		return mysqli_query($this->db, $sql);
	}

	public function deleteTipo($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		return mysqli_query($this->db, "DELETE FROM tp_tipo_tb WHERE tipo_id = " . $tipoId . " LIMIT 1");
	}

	public function updateCabec($tipoId, $texto)
	{
		$tipoId = $this->esc($tipoId);
		$texto = $this->esc($texto);
		return mysqli_query($this->db, "UPDATE tp_tipo_tb SET cod_cabec = '" . $texto . "' WHERE tipo_id = " . $tipoId);
	}

	public function updateRodap($tipoId, $texto)
	{
		$tipoId = $this->esc($tipoId);
		$texto = $this->esc($texto);
		return mysqli_query($this->db, "UPDATE tp_tipo_tb SET cod_rodap = '" . $texto . "' WHERE tipo_id = " . $tipoId);
	}

	public function getSetorCodeByTipo($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$sql = "SELECT s.cod_setor FROM tp_tipo_tb AS t "
			. "JOIN tp_setor_tb AS s ON s.id_setor=t.id_setor "
			. "WHERE t.tipo_id = '" . $tipoId . "'";
		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return null;
		}
		$row = mysqli_fetch_array($query);
		return $row['cod_setor'] ?? null;
	}

	public function getTipoArquivoById($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$query = mysqli_query($this->db, "SELECT tipo_arq FROM tp_tipo_tb WHERE tipo_id = '" . $tipoId . "' LIMIT 1");
		if (!$query) {
			return null;
		}
		$row = mysqli_fetch_array($query);
		return $row['tipo_arq'] ?? null;
	}

	public function getCabecRodapById($tipoId)
	{
		$tipoId = $this->esc($tipoId);
		$query = mysqli_query($this->db, "SELECT cod_cabec, cod_rodap FROM tp_tipo_tb WHERE tipo_id = '" . $tipoId . "' LIMIT 1");
		if (!$query) {
			return null;
		}
		return mysqli_fetch_assoc($query) ?: null;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}
}
