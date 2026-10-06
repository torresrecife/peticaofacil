<?php

namespace Tests\Unit;

use App\Services\SqlServerLookupService;
use PHPUnit\Framework\TestCase;

class SqlServerLookupSqlTest extends TestCase
{
    private function config(array $overrides = [])
    {
        return (object) array_merge([
            'nome_db' => 'NEO',
            'query_db' => '',
            'where_db' => '',
            'table_db' => '',
            'chave_db' => '',
        ], $overrides);
    }

    public function test_custom_query_receives_the_code_as_a_placeholder()
    {
        $sql = (new SqlServerLookupService())->buildLookupSql($this->config([
            'query_db' => 'SELECT p.CodigoProcesso AS codigo_neo FROM v_Processo p',
            'where_db' => 'where 1=1',
            'chave_db' => 'p.CodigoProcesso',
        ]));

        $this->assertSame(
            'SELECT p.CodigoProcesso AS codigo_neo FROM v_Processo p where 1=1 AND p.CodigoProcesso = ?',
            $sql
        );
    }

    public function test_table_fallback_receives_the_code_as_a_placeholder()
    {
        $sql = (new SqlServerLookupService())->buildLookupSql($this->config([
            'table_db' => 'Processos',
            'chave_db' => 'CodigoProcesso',
        ]));

        $this->assertSame('SELECT * FROM Processos WHERE CodigoProcesso = ?', $sql);
    }

    public function test_no_sql_is_built_without_a_lookup_key_or_a_source()
    {
        $service = new SqlServerLookupService();

        $this->assertNull($service->buildLookupSql($this->config(['query_db' => 'SELECT 1', 'where_db' => 'where 1=1'])));
        $this->assertNull($service->buildLookupSql($this->config(['chave_db' => 'CodigoProcesso'])));
    }

    public function test_invalid_codes_are_rejected_before_any_connection_is_opened()
    {
        $service = new class extends SqlServerLookupService {
            public $connections = 0;

            protected function openConnection($config)
            {
                $this->connections++;

                return null;
            }
        };

        $config = $this->config(['table_db' => 'Processos', 'chave_db' => 'CodigoProcesso']);

        $this->assertNull($service->fetchByCode($config, ''));
        $this->assertNull($service->fetchByCode($config, str_repeat('9', 256)));
        $this->assertNull($service->fetchByCode($this->config(['table_db' => 'Processos']), '123'));
        $this->assertSame(0, $service->connections);

        $this->assertNull($service->fetchByCode($config, "0' OR '1'='1"));
        $this->assertSame(1, $service->connections);
    }
}
