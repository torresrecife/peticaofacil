<?php

namespace App\Services;

class SqlServerLookupService
{
    public function connectionStatus($config)
    {
        $serverName = trim((string) ($config->nome_db ?? $config->nome ?? 'Servidor SQL'));

        if (!function_exists('sqlsrv_connect') || !function_exists('sqlsrv_query')) {
            return [
                'available' => false,
                'server_name' => $serverName,
                'message' => 'A conexao com o banco de dados "' . $serverName . '" falhou.',
            ];
        }

        $connection = $this->openConnection($config);
        if (!$connection) {
            return [
                'available' => false,
                'server_name' => $serverName,
                'message' => 'A conexao com o banco de dados "' . $serverName . '" falhou.',
            ];
        }

        sqlsrv_close($connection);

        return [
            'available' => true,
            'server_name' => $serverName,
            'message' => null,
        ];
    }

    public function fetchByCode($config, $code)
    {
        $connection = $this->openConnection($config);
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
            sqlsrv_close($connection);
            return null;
        }

        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            sqlsrv_free_stmt($result);
            sqlsrv_close($connection);
            return null;
        }

        sqlsrv_free_stmt($result);
        sqlsrv_close($connection);

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

    protected function openConnection($config)
    {
        if (!function_exists('sqlsrv_connect') || !function_exists('sqlsrv_query')) {
            return null;
        }

        $server = $config->ip_db;
        if (!$server) {
            return null;
        }

        return sqlsrv_connect($server, [
            'UID' => $config->usu_db,
            'PWD' => $config->senha_db,
            'Database' => $config->data_db,
            'CharacterSet' => 'UTF-8',
        ]);
    }
}
