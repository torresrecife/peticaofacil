<?php

namespace Tests\Feature;

use App\Services\SqlServerLookupService;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoLookupConnectionStatusTest extends TestCase
{
    public function test_lookup_controls_are_disabled_when_sql_connection_is_unavailable()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('setores')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 48,
            'legacy_tipo_id' => 48,
            'legacy_setor_id' => 1,
            'legacy_sql_config_id' => 90,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento-48',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sql_server_profiles')->insert([
            'id' => 90,
            'legacy_config_id' => 90,
            'nome' => 'NEO',
            'host' => '10.0.0.10',
            'database_name' => 'juridico',
            'username' => 'sa',
            'password' => '123',
            'table_name' => 'Processos',
            'lookup_key' => 'CodigoProcesso',
            'base_query' => 'SELECT * FROM Processos',
            'where_clause' => 'where 1=1',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app->instance(SqlServerLookupService::class, new class extends SqlServerLookupService {
            public function connectionStatus($config)
            {
                return [
                    'available' => false,
                    'server_name' => 'NEO',
                    'message' => 'A conexao com o banco de dados "NEO" falhou.',
                ];
            }
        });

        $this->actingAs($user)
            ->get('/peticoes/modelos/48')
            ->assertStatus(200)
            ->assertSee('A conexao com o banco de dados &quot;NEO&quot; falhou.', false)
            ->assertSee('name="codigo_processo"', false)
            ->assertSee('disabled', false);
    }
}
