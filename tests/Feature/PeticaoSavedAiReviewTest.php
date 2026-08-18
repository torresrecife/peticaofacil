<?php

namespace Tests\Feature;

use App\Services\OpenAIResponsesClient;
use App\User;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PeticaoSavedAiReviewTest extends TestCase
{
    public function test_saved_peticao_ai_review_endpoint_returns_interpretive_findings()
    {
        $user = factory(User::class)->create([
            'id_usu' => 33,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 33,
            'nome_setor' => 'Civel',
            'cod_setor' => 'CIV',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 303,
            'legacy_tipo_id' => 303,
            'legacy_setor_id' => 33,
            'nome' => 'Modelo IA',
            'slug' => 'modelo-ia-303',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticoes')->insert([
            'id' => 4004,
            'legacy_peca_id' => null,
            'modelo_id' => 303,
            'user_id' => $user->id,
            'legacy_usuario_id' => 33,
            'codigo_externo' => 'P4004',
            'nome_arquivo' => 'Modelo IA',
            'cliente_referencia' => 'Cliente IA',
            'conteudo_html' => '<p>Texto final com repeticao repeticao relevante.</p>',
            'campos_resolvidos' => null,
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mock = Mockery::mock(OpenAIResponsesClient::class);
        $mock->shouldReceive('isEnabled')->andReturn(true);
        $mock->shouldReceive('createStructuredResponse')
            ->once()
            ->andReturn([
                'ok' => true,
                'error' => null,
                'data' => [
                    'summary' => 'A analise encontrou uma duplicidade relevante no fechamento do texto.',
                    'warnings' => [],
                    'findings' => [
                        [
                            'title' => 'Duplicidade relevante',
                            'severity' => 'media',
                            'message' => 'Ha repeticao desnecessaria de expressao no corpo final.',
                            'recommendation' => 'Consolide o trecho repetido em uma unica formulacao.',
                            'snippet' => 'repeticao repeticao relevante',
                        ],
                    ],
                ],
            ]);

        $this->app->instance(OpenAIResponsesClient::class, $mock);

        $response = $this->actingAs($user)->postJson('/peticoes-salvas/4004/revisao/ia', [
            'cod_pecas' => '<p>Texto final com repeticao repeticao relevante.</p>',
            'plain_text' => 'Texto final com repeticao repeticao relevante.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('mode', 'openai')
            ->assertJsonPath('findings.0.title', 'Duplicidade relevante')
            ->assertJsonPath('findings.0.severity', 'media')
            ->assertJsonPath('findings.0.recommendation', 'Consolide o trecho repetido em uma unica formulacao.');
    }
}
