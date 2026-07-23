<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoIndexSourceTest extends TestCase
{
    public function test_peticoes_index_lists_normalized_models_first_and_legacy_as_fallback()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 1, 'nome_setor' => 'A', 'cod_setor' => 'A', 'data_cad' => now()],
            ['id_setor' => 2, 'nome_setor' => 'B', 'cod_setor' => 'B', 'data_cad' => now()],
        ]);

        DB::table('tp_tipo_tb')->insert([
            [
                'tipo_id' => 101,
                'tipo_nome' => 'Modelo Espelhado',
                'id_setor' => 1,
                'tipo_data' => now(),
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
            ],
            [
                'tipo_id' => 202,
                'tipo_nome' => 'Modelo So Legado',
                'id_setor' => 2,
                'tipo_data' => now(),
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
            ],
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 101,
            'legacy_tipo_id' => 101,
            'legacy_setor_id' => 1,
            'nome' => 'Modelo Espelhado',
            'slug' => 'modelo-espelhado-101',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes')
            ->assertStatus(200)
            ->assertSee('Modelos normalizados')
            ->assertSee('Fallback legado')
            ->assertSee('Modelo Espelhado')
            ->assertSee('Modelo So Legado');
    }

    public function test_peticoes_index_can_filter_models_by_search()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 1, 'nome_setor' => 'A', 'cod_setor' => 'A', 'data_cad' => now()],
            ['id_setor' => 2, 'nome_setor' => 'B', 'cod_setor' => 'B', 'data_cad' => now()],
        ]);

        DB::table('tp_tipo_tb')->insert([
            [
                'tipo_id' => 101,
                'tipo_nome' => 'Peticao de Busca e Apreensao',
                'id_setor' => 1,
                'tipo_data' => now(),
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
            ],
            [
                'tipo_id' => 202,
                'tipo_nome' => 'Peticao de Revisional',
                'id_setor' => 2,
                'tipo_data' => now(),
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
            ],
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 101,
            'legacy_tipo_id' => 101,
            'legacy_setor_id' => 1,
            'nome' => 'Peticao de Busca e Apreensao',
            'slug' => 'peticao-busca-apreensao-101',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes?search=Busca')
            ->assertStatus(200)
            ->assertSee('Peticao de Busca e Apreensao')
            ->assertDontSee('Peticao de Revisional');
    }

    public function test_peticoes_index_prioritizes_favorites_in_results()
    {
        $user = factory(User::class)->create([
            'id_usu' => 321,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 1, 'nome_setor' => 'A', 'cod_setor' => 'A', 'data_cad' => now()],
        ]);

        DB::table('peticao_modelos')->insert([
            [
                'id' => 11,
                'legacy_tipo_id' => 11,
                'legacy_setor_id' => 1,
                'nome' => 'Peticao Alfa',
                'slug' => 'peticao-alfa-11',
                'status' => 'ativo',
                'arquivo_padrao' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'legacy_tipo_id' => 12,
                'legacy_setor_id' => 1,
                'nome' => 'Peticao Beta',
                'slug' => 'peticao-beta-12',
                'status' => 'ativo',
                'arquivo_padrao' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('user_model_favorites')->insert([
            'legacy_usuario_id' => 321,
            'source' => 'normalized',
            'modelo_id' => 12,
            'legacy_tipo_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/peticoes?search=Peticao');
        $response->assertStatus(200);

        $content = $response->getContent();
        $betaLink = route('peticoes.normalized.show', 12);
        $alfaLink = route('peticoes.normalized.show', 11);
        $this->assertNotFalse(strpos($content, $betaLink));
        $this->assertNotFalse(strpos($content, $alfaLink));
        $this->assertTrue(
            strpos($content, $betaLink) < strpos($content, $alfaLink),
            'O modelo favorito deveria aparecer antes dos demais resultados.'
        );
    }
}
