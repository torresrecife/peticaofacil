<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminNormalizedSqlServerConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users') || !Schema::hasTable('sql_server_profiles')) {
            $this->setUpLegacySchema();
        }
    }

    public function test_admin_can_create_sql_server_profile_in_normalized_schema_without_legacy_mirror_by_default()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/servidores-normalizados/create')
            ->assertStatus(200)
            ->assertSee('Novo servidor SQL');

        $response = $this->actingAs($admin)
            ->post('/admin/servidores-normalizados', [
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
        $this->assertNull($profile->legacy_config_id);

        if (Schema::hasTable('tp_config_db')) {
            $legacy = DB::table('tp_config_db')->where('nome_db', 'Consulta Processos')->first();
            $this->assertNull($legacy);
        }

        $response->assertRedirect('/admin/servidores-normalizados/' . $profile->id . '/edit');
    }

    public function test_legacy_index_route_redirects_to_normalized_server_index()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/servidores')
            ->assertRedirect('/admin/servidores-normalizados');
    }
}
