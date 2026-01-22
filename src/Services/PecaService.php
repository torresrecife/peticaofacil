<?php

namespace App\Services;

use App\Infra\Database;

class PecaService
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function fetchList($tipoId, $limit, $search, $usuarioNivel, $usuarioId)
	{
		$tipoId = $this->esc($tipoId);
		$usuarioId = $this->esc($usuarioId);
		$limit = (int) $limit;
		$offset = $limit * 10;
		$search = trim((string) $search);

		$where = " from tp_pecas_tb as p "
			. "JOIN tp_usu_tb AS u on u.id_usu=p.id_usu "
			. "where p.tipo_id='" . $tipoId . "' ";
		if ($usuarioNivel !== "ADM") {
			$where .= "and p.id_usu = '" . $usuarioId . "' ";
		}
		if ($search !== "") {
			$where .= "and p.nome_cli like '%" . $this->esc($search) . "%' ";
		}

		$listSql = "SELECT *, date_format(p.data_cad, '%d/%m/%Y %H:%i:%s') as dtcadastro "
			. $where
			. "ORDER by p.id_pecas desc "
			. "limit " . $offset . ", 10";

		$countSql = "SELECT count(*) " . $where;

		$listQuery = mysqli_query($this->db, $listSql);
		$countQuery = mysqli_query($this->db, $countSql);

		$rows = array();
		if ($listQuery) {
			while ($row = mysqli_fetch_array($listQuery)) {
				$rows[] = $row;
			}
		}

		$total = 0;
		if ($countQuery) {
			$countRow = mysqli_fetch_array($countQuery);
			$total = (int) ($countRow[0] ?? 0);
		}

		return array(
			'rows' => $rows,
			'total' => $total,
		);
	}

	public function listRecent($usuarioNivel, $usuarioId, $setorId, $clienteId, $limit = 10)
	{
		$where = $this->buildScopeWhere($usuarioNivel, $usuarioId, $setorId, $clienteId);
		$sql = "SELECT p.id_pecas, u.nome_usu, p.tipo_id, p.nome_pecas, p.nome_cli, p.data_cad as datacad "
			. "FROM tp_pecas_tb as p "
			. "JOIN tp_usu_tb AS u ON u.id_usu=p.id_usu "
			. $where
			. "ORDER BY p.data_cad DESC "
			. "LIMIT " . (int) $limit;
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

	public function listToday($usuarioNivel, $usuarioId, $setorId, $clienteId, $date = null)
	{
		$date = $date ?: date('Y-m-d');
		$where = $this->buildScopeWhere($usuarioNivel, $usuarioId, $setorId, $clienteId);
		$where .= ($where === '' ? 'WHERE ' : ' AND ') . "DATE(p.data_cad) = '" . $this->esc($date) . "'";
		$sql = "SELECT p.id_pecas, u.nome_usu, p.tipo_id, p.nome_pecas, p.nome_cli, p.data_cad as datacad "
			. "FROM tp_pecas_tb as p "
			. "JOIN tp_usu_tb AS u ON u.id_usu=p.id_usu "
			. $where
			. "ORDER BY p.data_cad DESC";
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

	public function getEditInfo($id)
	{
		$id = $this->esc($id);
		$sql = "SELECT cod_pecas, cod_sav, id_usu, "
			. "(DATE_FORMAT(NOW(),'%Y%m%d%H%i')-DATE_FORMAT(data_cad,'%Y%m%d%H%i')) AS minutos "
			. "FROM tp_pecas_tb WHERE id_pecas = '" . $id . "'";
		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return null;
		}
		return mysqli_fetch_assoc($query) ?: null;
	}

	public function getById($id)
	{
		$id = $this->esc($id);
		$query = mysqli_query($this->db, "SELECT * FROM tp_pecas_tb WHERE id_pecas = '" . $id . "'");
		if (!$query) {
			return null;
		}
		return mysqli_fetch_assoc($query) ?: null;
	}

	public function findByCodSavOrId($codSav, $idPecas)
	{
		$where = '';
		if ($codSav !== '') {
			$where = "t.cod_sav = '" . $this->esc($codSav) . "'";
		} elseif ($idPecas !== '') {
			$where = "t.id_pecas = '" . $this->esc($idPecas) . "'";
		} else {
			return null;
		}
		$sql = "SELECT id_pecas, cod_pecas FROM tp_pecas_tb as t WHERE " . $where . " LIMIT 1";
		$query = mysqli_query($this->db, $sql);
		if (!$query) {
			return null;
		}
		return mysqli_fetch_assoc($query) ?: null;
	}

	public function savePeca($tipoId, $idPecas, $nomePecas, $nomeCli, $texto, $codSav, $usuarioId)
	{
		$tipoId = $this->esc($tipoId);
		$idPecas = $this->esc($idPecas);
		$nomePecas = $this->esc($nomePecas);
		$nomeCli = $this->esc($nomeCli);
		$texto = $this->esc($texto);
		$codSav = $this->esc($codSav);
		$usuarioId = $this->esc($usuarioId);

		$exists = mysqli_query($this->db, "SELECT cod_sav FROM tp_pecas_tb WHERE cod_sav = '" . $codSav . "' LIMIT 1");
		if ($exists && mysqli_num_rows($exists) == 1) {
			$sql = "UPDATE tp_pecas_tb SET "
				. "tipo_id = '" . $tipoId . "', "
				. "id_usu = '" . $usuarioId . "', "
				. "nome_pecas = '" . $nomePecas . "', "
				. "nome_cli = '" . $nomeCli . "', "
				. "cod_pecas = '" . $texto . "', "
				. "data_cad = '" . date('Y-m-d H:i:s') . "' "
				. "WHERE id_pecas = '" . $idPecas . "' "
				. "AND cod_sav = '" . $codSav . "'";
			$ok = mysqli_query($this->db, $sql);
			return $ok ? $idPecas : null;
		}

		$sql = "INSERT INTO tp_pecas_tb SET "
			. "tipo_id = '" . $tipoId . "', "
			. "id_usu = '" . $usuarioId . "', "
			. "nome_pecas = '" . $nomePecas . "', "
			. "nome_cli = '" . $nomeCli . "', "
			. "cod_pecas = '" . $texto . "', "
			. "data_cad = '" . date('Y-m-d H:i:s') . "', "
			. "cod_sav = '" . $codSav . "'";
		$ok = mysqli_query($this->db, $sql);
		if (!$ok) {
			return null;
		}
		$q = mysqli_query($this->db, "SELECT MAX(t.id_pecas) AS id_pecas FROM tp_pecas_tb AS t");
		$w = mysqli_fetch_array($q);
		return $w['id_pecas'] ?? null;
	}

	private function esc($value)
	{
		return Database::escape($this->db, $value);
	}

	private function buildScopeWhere($usuarioNivel, $usuarioId, $setorId, $clienteId)
	{
		$where = '';
		if ($usuarioNivel === 'USU') {
			$where = "WHERE p.id_usu = '" . $this->esc($usuarioId) . "' ";
		} else {
			if ((int) $setorId !== 0) {
				$where = "WHERE u.id_setor = '" . $this->esc($setorId) . "' ";
				if ((int) $clienteId !== 0) {
					$where .= "and u.id_cliente = '" . $this->esc($clienteId) . "' ";
				}
			}
		}
		return $where;
	}
}
