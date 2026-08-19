<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoNormalizedRouteTest extends TestCase
{
    public function test_user_can_open_compose_and_create_normalized_petition_from_normalized_model_route()
    {
        $user = factory(User::class)->create([
            'id_usu' => 77,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('setores')->insert([
            'id_setor' => 77,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 77,
            'legacy_tipo_id' => null,
            'legacy_setor_id' => 77,
            'nome' => 'Modelo Nativo',
            'slug' => 'modelo-nativo-77',
            'status' => 'ativo',
            'arquivo_padrao' => 'doc',
            'cabecalho_html' => '<p>Cabecalho Nativo @CAMPO7701@</p>',
            'rodape_html' => '<p>Rodape Nativo</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_paragrafos')->insert([
            'id' => 7701,
            'modelo_id' => 77,
            'legacy_fund_id' => null,
            'titulo' => 'Principal',
            'conteudo_html' => '<p>Paragrafo @campo7701@</p>',
            'ordem' => 1,
            'visivel' => 1,
            'ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            'id' => 7701,
            'modelo_id' => 77,
            'legacy_input_id' => null,
            'rotulo' => 'Cliente',
            'token' => '@campo7701@',
            'tipo' => 'TEXT',
            'origem_coluna' => null,
            'ordem' => 1,
            'visivel' => 1,
            'gera_nome_arquivo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/77')
            ->assertStatus(200)
            ->assertSee('Modelo Nativo')
            ->assertDontSee('Abrir editor legado');

        $previewResponse = $this->actingAs($user)->post('/peticoes/modelos/77', [
            'action_type' => 'preview',
            'campo_7701' => 'Cliente Nativo',
        ]);

        $previewResponse->assertStatus(200)
            ->assertSee('Cabecalho Nativo', false)
            ->assertSee('Paragrafo Cliente Nativo', false)
            ->assertDontSee('Abrir editor legado');

        $previewHtml = $previewResponse->viewData('preview')['html'];

        $storeResponse = $this->actingAs($user)->post('/peticoes/modelos/77/peticao-normalizada', [
            'nome_cli' => 'Cliente Nativo',
            'content' => $previewHtml,
            'resolved_fields' => json_encode($previewResponse->viewData('preview')['resolved_fields']),
        ]);

        $peticao = DB::table('peticoes')->where('modelo_id', 77)->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $storeResponse->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
    }
}
