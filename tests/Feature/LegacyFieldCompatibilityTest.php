<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\PeticaoComposerService;
use App\Services\PeticaoModeloRuntimeFactory;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyFieldCompatibilityTest extends TestCase
{
    public function test_legacy_field_types_masks_events_and_row_break_are_rendered()
    {
        $user = factory(User::class)->create(['nivel_usu' => 'USU', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 1, 'nome_setor' => 'Juridico', 'cod_setor' => 'JUR', 'data_cad' => now()]);
        DB::table('peticao_modelos')->insert([
            'id' => 80, 'legacy_tipo_id' => 80, 'legacy_setor_id' => 1, 'nome' => 'COMPATIBILIDADE',
            'slug' => 'compatibilidade-80', 'status' => 'ativo', 'arquivo_padrao' => 'pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $base = [
            'modelo_id' => 80, 'origem_coluna' => null, 'origem_alias' => null, 'ordem' => 1,
            'colunas_layout' => 1, 'linhas_layout' => 0, 'visivel' => 1, 'obrigatorio' => 0,
            'gera_nome_arquivo' => 0, 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('peticao_modelo_campos')->insert(array_merge($base, [
            'id' => 8001, 'legacy_input_id' => 8001, 'rotulo' => 'PROCESSO', 'token' => '@campo8001@',
            'tipo' => 'TEXT', 'comportamento' => 'processo', 'linhas_layout' => 1, 'eventos_frontend' => '[]',
        ]));
        DB::table('peticao_modelo_campos')->insert(array_merge($base, [
            'id' => 8002, 'legacy_input_id' => 8002, 'rotulo' => 'DATA', 'token' => '@campo8002@',
            'tipo' => 'TEXT', 'comportamento' => 'date', 'ordem' => 2,
            'eventos_frontend' => json_encode(['blur' => 'diasemana(this);']),
        ]));
        DB::table('peticao_modelo_campos')->insert(array_merge($base, [
            'id' => 8003, 'legacy_input_id' => 8003, 'rotulo' => 'DECISÃO', 'token' => '@campo8003@',
            'tipo' => 'RADIO2', 'comportamento' => '', 'ordem' => 3, 'eventos_frontend' => '[]',
        ]));
        DB::table('peticao_modelo_campo_opcoes')->insert([
            ['campo_id' => 8003, 'rotulo' => 'SIM', 'valor_retorno' => 'DEFERIDO', 'ordem' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['campo_id' => 8003, 'rotulo' => 'NÃO', 'valor_retorno' => 'INDEFERIDO', 'ordem' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('peticao_modelo_paragrafos')->insert([
            'modelo_id' => 80, 'titulo' => 'TESTE', 'conteudo_html' => '<p>@campo8003@</p>',
            'ordem' => 1, 'visivel' => 1, 'ativo' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->get('/peticoes/modelos/80')
            ->assertStatus(200)
            ->assertSee('data-input-behavior="processo"', false)
            ->assertSee('legacy-row-break', false)
            ->assertSee('data-event-blur="diasemana(this);"', false)
            ->assertSee('type="radio"', false)
            ->assertSee('function formatProcess(value)', false)
            ->assertSee("raw.indexOf('diasemana(this)')", false);
        $model = PeticaoModelo::findOrFail(80);
        $runtime = app(PeticaoModeloRuntimeFactory::class)->fromNormalized($model);
        $result = app(PeticaoComposerService::class)->compose($runtime, ['campo_8003' => 'SIM']);
        $this->assertStringContainsString('DEFERIDO', $result['html']);
    }
}
