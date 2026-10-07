<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\Services\PeticaoComposerService;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OptionReturnUndefinedTest extends TestCase
{
    public function test_normalize_return_treats_undefined_and_null_as_empty()
    {
        foreach (['undefined', 'UNDEFINED', ' Undefined ', 'null', 'NULL', '', '   '] as $v) {
            $this->assertNull(PeticaoModeloCampoOpcao::normalizeReturn($v), var_export($v, true));
        }
        foreach (['SIM', '0', 'Parágrafo real', 'undefinedX'] as $v) {
            $this->assertSame($v, PeticaoModeloCampoOpcao::normalizeReturn($v), $v);
        }
    }

    public function test_option_accessor_hides_the_undefined_value()
    {
        $this->seedModel();
        $sim = PeticaoModeloCampoOpcao::where('rotulo', 'SIM')->firstOrFail();
        $real = PeticaoModeloCampoOpcao::where('rotulo', 'COM TEXTO')->firstOrFail();

        $this->assertNull($sim->valor_retorno);
        $this->assertNull($sim->return_1);
        $this->assertSame('Parágrafo predefinido', $real->valor_retorno);
    }

    public function test_select_options_drop_undefined_return_so_dependent_fill_stays_empty()
    {
        $this->seedModel();
        $notif = PeticaoModeloCampo::where('rotulo', 'NOTIFICAÇÃO MUDOU-SE?')->firstOrFail();

        $options = collect($notif->select_options)->keyBy('label');
        // SIM sem parágrafo: valor do SELECT cai no rótulo, e NÃO há return_1 (campo alvo fica vazio).
        $this->assertSame('SIM', $options['SIM']['return']);
        $this->assertArrayNotHasKey('return_1', $options['SIM']['extras']);
        // Opção com texto real mantém o parágrafo no return e no return_1.
        $comTexto = PeticaoModeloCampo::where('rotulo', 'TEXTO')->firstOrFail();
        $opt = collect($comTexto->select_options)->firstWhere('label', 'COM TEXTO');
        $this->assertSame('Parágrafo predefinido', $opt['return']);
        $this->assertSame('Parágrafo predefinido', $opt['extras']['return_1']);
    }

    public function test_composer_never_outputs_the_word_undefined()
    {
        $this->seedModel();
        $modelo = PeticaoModelo::with(['campos.opcoes', 'paragrafos'])->findOrFail(760);

        $html = app(PeticaoComposerService::class)->compose($modelo, [
            'campo_7601' => 'SIM',
            'campo_7603' => 'COM TEXTO',
        ])['html'];

        $this->assertStringNotContainsStringIgnoringCase('undefined', $html);
        $this->assertStringContainsString('Resposta: SIM', $html);
        $this->assertStringContainsString('Texto: Parágrafo predefinido', $html);
    }

    public function test_montagem_select_has_no_undefined_return_attribute()
    {
        $this->seedModel();
        $user = factory(User::class)->create(['nivel_usu' => 'USU', 'acesso_usu' => now()]);

        $html = $this->actingAs($user)->get('/peticoes/modelos/760')->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('data-return-1="undefined"', $html);
        $this->assertStringNotContainsString('>undefined<', $html);
    }

    public function test_migration_clears_undefined_returns_in_the_database()
    {
        require_once database_path('migrations/2026_10_06_000100_clean_undefined_option_returns.php');
        $this->seedModel();

        // Valores crus no banco (o seed grava via query, sem passar pelo mutator).
        $this->assertSame(2, DB::table('peticao_modelo_campo_opcoes')->whereRaw("LOWER(TRIM(valor_retorno)) IN ('undefined','null')")->count());

        (new \CleanUndefinedOptionReturns())->up();

        $this->assertSame(0, DB::table('peticao_modelo_campo_opcoes')->whereRaw("LOWER(TRIM(valor_retorno)) IN ('undefined','null')")->count());
        $this->assertSame('Parágrafo predefinido', DB::table('peticao_modelo_campo_opcoes')->where('rotulo', 'COM TEXTO')->value('valor_retorno'));
    }

    private function seedModel()
    {
        DB::table('setores')->insert(['id_setor' => 1, 'nome_setor' => 'Jurídico', 'cod_setor' => 'JUR', 'data_cad' => now()]);
        DB::table('peticao_modelos')->insert([
            'id' => 760, 'legacy_tipo_id' => 760, 'legacy_setor_id' => 1, 'nome' => 'MODELO UNDEF',
            'slug' => 'modelo-undef', 'status' => 'ativo', 'arquivo_padrao' => 'pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('peticao_modelo_paragrafos')->insert([
            'id' => 7600, 'modelo_id' => 760, 'legacy_fund_id' => 7600, 'titulo' => 'Corpo',
            'conteudo_html' => '<p>Resposta: @campo7601@ Texto: @campo7603@</p>',
            'ordem' => 1, 'visivel' => 1, 'ativo' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $campos = [
            [7601, 'NOTIFICAÇÃO MUDOU-SE?', 'SELECT', 1],
            [7602, 'FUNDAMENTAÇÃO MUDOU-SE', 'TEXTAREA', 2],
            [7603, 'TEXTO', 'SELECT', 3],
        ];
        foreach ($campos as $c) {
            DB::table('peticao_modelo_campos')->insert([
                'id' => $c[0], 'modelo_id' => 760, 'legacy_input_id' => $c[0], 'rotulo' => $c[1],
                'token' => '@campo' . $c[0] . '@', 'tipo' => $c[2], 'colunas_layout' => 1, 'linhas_layout' => 0,
                'ordem' => $c[3], 'obrigatorio' => 0, 'visivel' => 1, 'gera_nome_arquivo' => 0,
                'eventos_frontend' => json_encode([]), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('peticao_modelo_campo_opcoes')->insert([
            ['id' => 71, 'campo_id' => 7601, 'legacy_dado_id' => 71, 'rotulo' => 'SIM', 'valor_retorno' => 'undefined', 'ordem' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 72, 'campo_id' => 7601, 'legacy_dado_id' => 72, 'rotulo' => 'NÃO', 'valor_retorno' => 'null', 'ordem' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 73, 'campo_id' => 7603, 'legacy_dado_id' => 73, 'rotulo' => 'COM TEXTO', 'valor_retorno' => 'Parágrafo predefinido', 'ordem' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
