<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNormalizedSqlServerConfigTest extends TestCase
{
    public function test_admin_can_create_sql_server_profile_in_normalized_schema_and_reflect_to_legacy()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/servidores/create')
            ->assertRedirect('/admin/servidores-normalizados/create');

        $this->actingAs($admin)
            ->get('/admin/servidores-normalizados/create')
            ->assertStatus(200)
            ->assertSee('Novo servidor SQL');

        $response = $this->actingAs($admin)
            ->post('/admin/servidores', [
                'nome_db' => 'Consulta Processos',
                'ip_db' => '10.0.0.15',
                'data_db' => 'juridico',
                'usu_db' => 'consulta',
                'senha_db' => 'segredo',
                'table_db' => 'Processos',
                'chave_db' => 'CodigoProcesso',
                'query_db' => 'SELECT * FROM Processos',
                'where_db' => 'where 1=1',
                'stt' => 'Y',
            ]);

        $profile = DB::table('sql_server_profiles')->where('nome', 'Consulta Processos')->first();
        $this->assertNotNull($profile);
        $this->assertNotNull($profile->legacy_config_id);

        $legacy = DB::table('tp_config_db')->where('id_db', $profile->legacy_config_id)->first();
        $this->assertNotNull($legacy);
        $this->assertSame('Consulta Processos', $legacy->nome_db);
        $this->assertSame('10.0.0.15', $legacy->ip_db);
        $this->assertSame('juridico', $legacy->data_db);

        $response->assertRedirect('/admin/servidores-normalizados/' . $profile->id . '/edit');
    }
}
