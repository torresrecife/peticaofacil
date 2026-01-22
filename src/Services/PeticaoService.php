<?php

namespace App\Services;

class PeticaoService
{
	public function fetchDados(array $wdb, $tipocha)
	{
		$conn = $this->connectSqlsrv($wdb);
		if (!$conn) {
			return null;
		}

		$query2 = $wdb['query_db'];
		$where2 = $wdb['where_db'];

		if ($query2 != "") {
			$sql = "$query2 $where2 AND " . $wdb['chave_db'] . " = '" . $tipocha . "' ";
		} else {
			$sql = "SELECT * FROM " . $wdb['table_db'] . " WHERE " . $wdb['chave_db'] . " = '" . $tipocha . "' ";
		}

		$result = sqlsrv_query($conn, $sql);
		if (!$result) {
			return null;
		}

		$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
		return $this->normalizeEncoding($row);
	}

	public function fetchSample(array $wdb)
	{
		$conn = $this->connectSqlsrv($wdb);
		if (!$conn) {
			return null;
		}

		$query2 = $wdb['query_db'];
		$where2 = $wdb['where_db'];

		if ($query2 != "") {
			$sql = "$query2 $where2 ";
		} else {
			$sql = "SELECT top1 * FROM " . $wdb['table_db'] . " ";
		}

		$result = sqlsrv_query($conn, $sql);
		if (!$result) {
			return null;
		}

		$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
		return $this->normalizeEncoding($row);
	}

	private function connectSqlsrv(array $wdb)
	{
		if (class_exists(\App\Infra\Database::class)) {
			return \App\Infra\Database::sqlsrv(array(
				'server' => $wdb['ip_db'] ?? null,
				'user' => $wdb['usu_db'] ?? null,
				'password' => $wdb['senha_db'] ?? null,
				'database' => $wdb['data_db'] ?? null
			));
		}

		$connectionInfo = array(
			"UID" => $wdb['usu_db'] ?? null,
			"PWD" => $wdb['senha_db'] ?? null,
			"Database" => $wdb['data_db'] ?? null
		);

		return sqlsrv_connect($wdb['ip_db'] ?? null, $connectionInfo);
	}

	private function normalizeEncoding($value)
	{
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $this->normalizeEncoding($item);
			}
			return $value;
		}
		if (!is_string($value) || $value === '') {
			return $value;
		}
		return preg_match('//u', $value) ? $value : utf8_encode($value);
	}
}
