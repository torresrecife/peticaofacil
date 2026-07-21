<?php

namespace App\Services;

use App\SqlServerConfig;

class SqlServerLookupService
{
    public function fetchByCode(SqlServerConfig $config, $code)
    {
        if (!function_exists('sqlsrv_connect') || !function_exists('sqlsrv_query')) {
            return null;
        }

        $server = $config->ip_db;
        if (!$server) {
            return null;
        }

        $connection = sqlsrv_connect($server, [
            'UID' => $config->usu_db,
            'PWD' => $config->senha_db,
            'Database' => $config->data_db,
            'CharacterSet' => 'UTF-8',
        ]);

        if (!$connection) {
            return null;
        }

        $query = trim((string) $config->query_db);
        $where = trim((string) $config->where_db);
        $table = trim((string) $config->table_db);
        $key = trim((string) $config->chave_db);

        $escapedCode = str_replace("'", "''", (string) $code);

        if ($query !== '') {
            $sql = $query . ' ' . $where . " AND " . $key . " = '" . $escapedCode . "'";
        } else {
            $sql = "SELECT * FROM " . $table . " WHERE " . $key . " = '" . $escapedCode . "'";
        }

        $result = sqlsrv_query($connection, $sql);
        if (!$result) {
            return null;
        }

        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    protected function normalizeRow(array $row)
    {
        foreach ($row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format('d/m/Y');
                continue;
            }

            if (is_string($value) && $value !== '' && !preg_match('//u', $value)) {
                $row[$key] = utf8_encode($value);
            }
        }

        return $row;
    }
}
