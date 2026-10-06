<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminModeloInactiveLinksTest extends TestCase
{
    public function test_saving_a_model_keeps_its_inactive_server_and_client()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        $this->seedModelWithInactiveLinks();

        $edit = $this->actingAs($admin)->get('/admin/modelos-normalizados/88/edit');
        $edit->assertStatus(200);
        $html = $edit->getContent();

        // O que esta vinculado aparece selecionado e marcado como inativo; outros inativos nao entram.
        $this->assertSame(1, preg_match('/<option value="60"\s+selected\s*>Servidor antigo \(inativo\)<\/option>/', $html));
        $this->assertSame(1, preg_match('/<option value="7"\s+selected\s*>Cliente antigo \(inativo\)<\/option>/', $html));
        $this->assertStringContainsString('NEO', $html);
        $this->assertStringNotContainsString('Outro servidor inativo', $html);
        $this->assertStringNotContainsString('Outro cliente inativo', $html);

        // Salvar o formulario como ele veio nao pode soltar os vinculos.
        $this->actingAs($admin)
            ->put('/admin/modelos-normalizados/88', [
                'tipo_nome' => 'Modelo renomeado',
                'nome_pre' => '',
                'nome_pos' => '',
                'id_db' => 60,
                'id_cliente' => 7,
                'id_setor' => 8,
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
                'cod_cabec' => '',
                'cod_rodap' => '',
            ])
            ->assertSessionHasNoErrors();

        $modelo = DB::table('peticao_modelos')->where('id', 88)->first();
        $this->assertSame('Modelo renomeado', $modelo->nome);
        $this->assertSame(60, (int) $modelo->legacy_sql_config_id);
        $this->assertSame(7, (int) $modelo->legacy_cliente_id);
    }

    public function test_new_model_form_lists_only_active_servers_and_clients()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        $this->seedModelWithInactiveLinks();

        $html = $this->actingAs($admin)->get('/admin/modelos-normalizados/create')->assertStatus(200)->getContent();

        $this->assertStringContainsString('NEO', $html);
        $this->assertStringContainsString('Cliente ativo', $html);
        $this->assertStringNotContainsString('Servidor antigo', $html);
        $this->assertStringNotContainsString('Cliente antigo', $html);
    }

    private function seedModelWithInactiveLinks()
    {
        DB::table('setores')->insert(['id_setor' => 8, 'nome_setor' => 'Civel', 'cod_setor' => 'CIV', 'data_cad' => now()]);

        DB::table('clientes')->insert([
            ['cliente_id' => 6, 'cliente_name' => 'Cliente ativo', 'cliente_cod' => 'ATV', 'cliente_area' => 8, 'cliente_status' => 'Y'],
            ['cliente_id' => 7, 'cliente_name' => 'Cliente antigo', 'cliente_cod' => 'ANT', 'cliente_area' => 8, 'cliente_status' => 'N'],
            ['cliente_id' => 9, 'cliente_name' => 'Outro cliente inativo', 'cliente_cod' => 'OUT', 'cliente_area' => 8, 'cliente_status' => 'N'],
        ]);

        $server = ['host' => '127.0.0.1', 'database_name' => 'neo', 'username' => 'leitura', 'created_at' => now(), 'updated_at' => now()];
        DB::table('sql_server_profiles')->insert([
            $server + ['id' => 1, 'legacy_config_id' => 54, 'nome' => 'NEO', 'status' => 'ativo'],
            $server + ['id' => 2, 'legacy_config_id' => 60, 'nome' => 'Servidor antigo', 'status' => 'inativo'],
            $server + ['id' => 3, 'legacy_config_id' => 61, 'nome' => 'Outro servidor inativo', 'status' => 'inativo'],
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 88,
            'legacy_tipo_id' => 88,
            'legacy_setor_id' => 8,
            'legacy_cliente_id' => 7,
            'legacy_sql_config_id' => 60,
            'nome' => 'Modelo Original',
            'slug' => 'modelo-original-88',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'metadata' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
