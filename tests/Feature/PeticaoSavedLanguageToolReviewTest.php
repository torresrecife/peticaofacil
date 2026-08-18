<?php

namespace Tests\Feature;

use App\Services\LanguageToolClient;
use App\UserLanguageToolPreference;
use App\User;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PeticaoSavedLanguageToolReviewTest extends TestCase
{
    public function test_saved_peticao_review_endpoint_returns_languagetool_matches_with_offsets_and_suggestions()
    {
        $user = factory(User::class)->create([
            'id_usu' => 31,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 31,
            'nome_setor' => 'Civel',
            'cod_setor' => 'CIV',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 301,
            'legacy_tipo_id' => 301,
            'legacy_setor_id' => 31,
            'nome' => 'Modelo Revisao',
            'slug' => 'modelo-revisao-301',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticoes')->insert([
            'id' => 4002,
            'legacy_peca_id' => null,
            'modelo_id' => 301,
            'user_id' => $user->id,
            'legacy_usuario_id' => 31,
            'codigo_externo' => 'P4002',
            'nome_arquivo' => 'Modelo Revisao',
            'cliente_referencia' => 'Cliente Revisao',
            'conteudo_html' => '<p>peticcao inicial</p>',
            'campos_resolvidos' => null,
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mock = Mockery::mock(LanguageToolClient::class);
        $mock->shouldReceive('check')
            ->once()
            ->with('peticcao inicial')
            ->andReturn([
                'ok' => true,
                'error' => null,
                'error_code' => null,
                'data' => [
                    'matches' => [
                        [
                            'message' => 'Possivel erro de ortografia encontrado.',
                            'offset' => 0,
                            'length' => 8,
                            'replacements' => [
                                ['value' => 'peticao'],
                            ],
                            'rule' => [
                                'id' => 'MORFOLOGIK_RULE_PT_BR',
                                'issueType' => 'misspelling',
                                'category' => [
                                    'id' => 'TYPOS',
                                    'name' => 'Ortografia',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(LanguageToolClient::class, $mock);

        $response = $this->actingAs($user)->postJson('/peticoes-salvas/4002/revisar', [
            'cod_pecas' => '<p>peticcao inicial</p>',
            'plain_text' => 'peticcao inicial',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('mode', 'languagetool')
            ->assertJsonPath('issues.0.category', 'Ortografia')
            ->assertJsonPath('issues.0.severity', 'alta')
            ->assertJsonPath('issues.0.snippet', 'peticcao')
            ->assertJsonPath('issues.0.suggestion', 'peticao')
            ->assertJsonPath('issues.0.offset', 0)
            ->assertJsonPath('issues.0.length', 8)
            ->assertJsonPath('issues.0.replacements.0', 'peticao');
    }

    public function test_saved_peticao_review_filters_user_dictionary_and_can_store_preference()
    {
        $user = factory(User::class)->create([
            'id_usu' => 32,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 32,
            'nome_setor' => 'Civel',
            'cod_setor' => 'CIV',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 302,
            'legacy_tipo_id' => 302,
            'legacy_setor_id' => 32,
            'nome' => 'Modelo Preferencia Revisao',
            'slug' => 'modelo-preferencia-revisao-302',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticoes')->insert([
            'id' => 4003,
            'legacy_peca_id' => null,
            'modelo_id' => 302,
            'user_id' => $user->id,
            'legacy_usuario_id' => 32,
            'codigo_externo' => 'P4003',
            'nome_arquivo' => 'Modelo Preferencia Revisao',
            'cliente_referencia' => 'Cliente Revisao',
            'conteudo_html' => '<p>peticcao inicial</p>',
            'campos_resolvidos' => null,
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->postJson('/peticoes-salvas/4003/revisao/languagetool/preferencias', [
            'entry_type' => 'dictionary_word',
            'token' => 'peticcao',
        ])->assertStatus(200);

        $this->assertDatabaseHas('user_languagetool_preferences', [
            'user_id' => $user->id,
            'entry_type' => 'dictionary_word',
            'token' => 'peticcao',
        ]);

        $mock = Mockery::mock(LanguageToolClient::class);
        $mock->shouldReceive('check')
            ->once()
            ->with('peticcao inicial')
            ->andReturn([
                'ok' => true,
                'error' => null,
                'error_code' => null,
                'data' => [
                    'matches' => [
                        [
                            'message' => 'Possivel erro de ortografia encontrado.',
                            'offset' => 0,
                            'length' => 8,
                            'replacements' => [
                                ['value' => 'peticao'],
                            ],
                            'rule' => [
                                'id' => 'MORFOLOGIK_RULE_PT_BR',
                                'issueType' => 'misspelling',
                                'category' => [
                                    'id' => 'TYPOS',
                                    'name' => 'Ortografia',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(LanguageToolClient::class, $mock);

        $response = $this->actingAs($user)->postJson('/peticoes-salvas/4003/revisar', [
            'cod_pecas' => '<p>peticcao inicial</p>',
            'plain_text' => 'peticcao inicial',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('issues', [])
            ->assertJsonPath('score', 100);
    }
}
