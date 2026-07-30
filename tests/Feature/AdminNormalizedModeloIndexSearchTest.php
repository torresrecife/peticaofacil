<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNormalizedModeloIndexSearchTest extends TestCase
{
    public function test_admin_normalized_model_index_can_filter_models_by_search()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 1, 'nome_setor' => 'Contencioso', 'cod_setor' => 'CON', 'data_cad' => now()],
        ]);

        DB::table('peticao_modelos')->insert([
            [
                'id' => 11,
                'legacy_tipo_id' => 101,
                'legacy_setor_id' => 1,
                'nome' => 'Peticao de Busca e Apreensao',
                'slug' => 'peticao-busca-apreensao-11',
                'status' => 'ativo',
                'arquivo_padrao' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'legacy_tipo_id' => 202,
                'legacy_setor_id' => 1,
                'nome' => 'Peticao Revisional',
                'slug' => 'peticao-revisional-12',
                'status' => 'ativo',
                'arquivo_padrao' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos-normalizados?search=Busca')
            ->assertStatus(200)
            ->assertSee('Buscar modelo')
            ->assertSee('Peticao de Busca e Apreensao')
            ->assertDontSee('Peticao Revisional');
    }
}
