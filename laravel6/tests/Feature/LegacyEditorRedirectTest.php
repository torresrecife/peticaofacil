<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyEditorRedirectTest extends TestCase
{
    public function test_legacy_editor_route_redirects_to_normalized_editor_when_mirror_exists()
    {
        $user = factory(User::class)->create([
            'id_usu' => 55,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 55,
            'nome_setor' => 'Execucao',
            'cod_setor' => 'EXE',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 55,
            'tipo_nome' => 'Modelo Redirecionado',
            'id_setor' => 55,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 55,
            'legacy_tipo_id' => 55,
            'legacy_setor_id' => 55,
            'nome' => 'Modelo Redirecionado',
            'slug' => 'modelo-redirecionado-55',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 5501,
            'tipo_id' => 55,
            'id_usu' => 55,
            'nome_pecas' => 'Modelo Redirecionado',
            'nome_cli' => 'Cliente Redirect',
            'cod_pecas' => '<p>Conteudo</p>',
            'data_cad' => now(),
            'cod_sav' => 'RED55',
        ]);

        DB::table('peticoes')->insert([
            'id' => 6501,
            'legacy_peca_id' => 5501,
            'modelo_id' => 55,
            'legacy_usuario_id' => 55,
            'codigo_externo' => 'RED55',
            'nome_arquivo' => 'Modelo Redirecionado',
            'cliente_referencia' => 'Cliente Redirect',
            'conteudo_html' => '<p>Conteudo</p>',
            'campos_resolvidos' => null,
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/pecas/5501/editar')
            ->assertRedirect('/peticoes-salvas/6501/editar');
    }

    public function test_legacy_editor_route_creates_mirror_on_demand_when_model_is_normalized()
    {
        $user = factory(User::class)->create([
            'id_usu' => 56,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 56,
            'nome_setor' => 'Execucao',
            'cod_setor' => 'EXE',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 56,
            'tipo_nome' => 'Modelo Espelhavel',
            'id_setor' => 56,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 56,
            'legacy_tipo_id' => 56,
            'legacy_setor_id' => 56,
            'nome' => 'Modelo Espelhavel',
            'slug' => 'modelo-espelhavel-56',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 5601,
            'tipo_id' => 56,
            'id_usu' => 56,
            'nome_pecas' => 'Modelo Espelhavel',
            'nome_cli' => 'Cliente Mirror',
            'cod_pecas' => '<p>Conteudo Mirror</p>',
            'data_cad' => now(),
            'cod_sav' => 'RED56',
        ]);

        $response = $this->actingAs($user)->get('/pecas/5601/editar');

        $peticaoEspelho = DB::table('peticoes')->where('legacy_peca_id', 5601)->first();

        $this->assertNotNull($peticaoEspelho);
        $response->assertRedirect('/peticoes-salvas/' . $peticaoEspelho->id . '/editar');
    }
}
