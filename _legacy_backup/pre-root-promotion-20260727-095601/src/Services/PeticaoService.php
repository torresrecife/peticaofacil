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

		$query2 = $this->normalizeSqlFragment($wdb['query_db'] ?? '');
		$where2 = $this->normalizeSqlFragment($wdb['where_db'] ?? '');
		$table = $this->normalizeSqlFragment($wdb['table_db'] ?? '');
		$chave = $this->normalizeSqlFragment($wdb['chave_db'] ?? '');
		$tipocha = str_replace("'", "''", (string) $tipocha);

		if ($query2 != "") {
			$sql = "$query2 $where2 AND " . $chave . " = '" . $tipocha . "' ";
		} else {
			$sql = "SELECT * FROM " . $table . " WHERE " . $chave . " = '" . $tipocha . "' ";
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

		$query2 = $this->normalizeSqlFragment($wdb['query_db'] ?? '');
		$where2 = $this->normalizeSqlFragment($wdb['where_db'] ?? '');
		$table = $this->normalizeSqlFragment($wdb['table_db'] ?? '');

		if ($query2 != "") {
			$sql = "$query2 $where2 ";
		} else {
			$sql = "SELECT TOP 1 * FROM " . $table . " ";
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
			"Database" => $wdb['data_db'] ?? null,
			"CharacterSet" => "UTF-8"
		);

		return sqlsrv_connect($wdb['ip_db'] ?? null, $connectionInfo);
	}

	private function normalizeSqlFragment($value)
	{
		if (!is_string($value) || $value === '') {
			return $value;
		}

		if (function_exists('app_to_utf8')) {
			return app_to_utf8($value);
		}

		return preg_match('//u', $value) ? $value : utf8_encode($value);
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
