<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminLegacySqlServerFallbackTest extends TestCase
{
    public function test_legacy_sql_server_fallback_update_still_works_without_mirror()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_config_db')->insert([
            'id_db' => 77,
            'nome_db' => 'Servidor Legado',
            'ip_db' => '192.168.0.20',
            'data_db' => 'base_antiga',
            'usu_db' => 'legacy',
            'senha_db' => '123',
            'table_db' => 'tbl',
            'chave_db' => 'Codigo',
            'query_db' => 'SELECT * FROM tbl',
            'where_db' => 'where 1=1',
            'stt' => 'Y',
        ]);

        $this->actingAs($admin)
            ->get('/admin/servidores/77/edit')
            ->assertStatus(200)
            ->assertSee('Servidor Legado')
            ->assertDontSee('Fonte principal atual da edicao.');

        $this->actingAs($admin)
            ->put('/admin/servidores/77', [
                'nome_db' => 'Servidor Legado Ajustado',
                'ip_db' => '192.168.0.21',
                'data_db' => 'base_nova',
                'usu_db' => 'legado2',
                'senha_db' => '456',
                'table_db' => 'processos',
                'chave_db' => 'Numero',
                'query_db' => 'SELECT * FROM processos',
                'where_db' => 'where stt = 1',
                'stt' => 'N',
            ])
            ->assertRedirect('/admin/servidores/77/edit');

        $legacy = DB::table('tp_config_db')->where('id_db', 77)->first();
        $profile = DB::table('sql_server_profiles')->where('legacy_config_id', 77)->first();

        $this->assertSame('Servidor Legado Ajustado', $legacy->nome_db);
        $this->assertSame('192.168.0.21', $legacy->ip_db);
        $this->assertSame('base_nova', $legacy->data_db);
        $this->assertSame('N', $legacy->stt);

        $this->assertNotNull($profile);
        $this->assertSame('Servidor Legado Ajustado', $profile->nome);
        $this->assertSame('inativo', $profile->status);
    }
}
