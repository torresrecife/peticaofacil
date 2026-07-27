<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSqlServerMirrorRedirectTest extends TestCase
{
    public function test_legacy_edit_route_redirects_to_normalized_server_when_mirror_exists()
    {
        config()->set('legacy.compat_admin_sql_routes', true);

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_config_db')->insert([
            'id_db' => 88,
            'nome_db' => 'Servidor Espelhado',
            'ip_db' => '10.1.1.1',
            'data_db' => 'juridico',
            'usu_db' => 'consulta',
            'senha_db' => '123',
            'table_db' => 'Processos',
            'chave_db' => 'Codigo',
            'query_db' => 'SELECT * FROM Processos',
            'where_db' => 'where 1=1',
            'stt' => 'Y',
        ]);

        DB::table('sql_server_profiles')->insert([
            'id' => 501,
            'legacy_config_id' => 88,
            'nome' => 'Servidor Espelhado',
            'host' => '10.1.1.1',
            'database_name' => 'juridico',
            'username' => 'consulta',
            'password' => '123',
            'table_name' => 'Processos',
            'lookup_key' => 'Codigo',
            'base_query' => 'SELECT * FROM Processos',
            'where_clause' => 'where 1=1',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/servidores/88/edit')
            ->assertRedirect('/admin/servidores-normalizados/501/edit');
    }
}
