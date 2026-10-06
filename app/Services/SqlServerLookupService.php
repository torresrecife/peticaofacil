<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SqlServerLookupService
{
    const MAX_CODE_LENGTH = 255;

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
        $code = (string) $code;
        $sql = $this->buildLookupSql($config);

        // O codigo viaja como parametro, nunca dentro do texto do SQL.
        if ($sql === null || $code === '' || strlen($code) > self::MAX_CODE_LENGTH) {
            return null;
        }

        $connection = $this->openConnection($config);
        if (!$connection) {
            return null;
        }

        $result = sqlsrv_query($connection, $sql, [[
            $code,
            SQLSRV_PARAM_IN,
            SQLSRV_PHPTYPE_STRING('UTF-8'),
            SQLSRV_SQLTYPE_VARCHAR(self::MAX_CODE_LENGTH),
        ]]);
        if (!$result) {
            $this->logQueryFailure($config);
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

    /**
     * Monta o SQL da consulta por codigo com um marcador `?` no lugar do
     * valor. Query, where, tabela e chave vem do cadastro do servidor,
     * mantido por administradores; o codigo digitado pelo usuario nao
     * entra aqui.
     */
    public function buildLookupSql($config)
    {
        $query = trim((string) $config->query_db);
        $where = trim((string) $config->where_db);
        $table = trim((string) $config->table_db);
        $key = trim((string) $config->chave_db);

        if ($key === '') {
            return null;
        }

        if ($query !== '') {
            return $query . ' ' . $where . ' AND ' . $key . ' = ?';
        }

        if ($table === '') {
            return null;
        }

        return 'SELECT * FROM ' . $table . ' WHERE ' . $key . ' = ?';
    }

    protected function logQueryFailure($config)
    {
        $errors = [];
        foreach ((array) sqlsrv_errors() as $error) {
            $errors[] = [
                'sqlstate' => isset($error['SQLSTATE']) ? $error['SQLSTATE'] : null,
                'code' => isset($error['code']) ? $error['code'] : null,
                'message' => isset($error['message']) ? $error['message'] : null,
            ];
        }

        Log::warning('neo_lookup_query_failed', [
            'servidor' => trim((string) ($config->nome_db ?? $config->nome ?? '')),
            'erros' => $errors,
        ]);
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
