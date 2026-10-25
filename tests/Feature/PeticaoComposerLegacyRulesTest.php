<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\PeticaoComposerService;
use App\Services\PeticaoModeloRuntimeFactory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regras de montagem do texto do sistema antigo (parag.php).
 */
class PeticaoComposerLegacyRulesTest extends TestCase
{
    protected function compose($paragraph, array $values, array $campos = null, array $opcoes = [])
    {
        DB::table('peticao_modelos')->insert([
            'id' => 97, 'legacy_tipo_id' => 97, 'nome' => 'REGRAS', 'slug' => 'regras-97',
            'status' => 'ativo', 'arquivo_padrao' => 'pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('peticao_modelo_paragrafos')->insert([
            'modelo_id' => 97, 'titulo' => 'CORPO', 'conteudo_html' => $paragraph,
            'ordem' => 1, 'visivel' => 1, 'ativo' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $campos = $campos ?: [['id' => 9701, 'rotulo' => 'NOME']];
        foreach ($campos as $index => $campo) {
            DB::table('peticao_modelo_campos')->insert(array_merge([
                'modelo_id' => 97, 'legacy_input_id' => $campo['id'], 'token' => '@campo' . $campo['id'] . '@',
                'tipo' => 'TEXT', 'ordem' => $index + 1, 'visivel' => 1, 'obrigatorio' => 0,
                'gera_nome_arquivo' => 0, 'eventos_frontend' => '[]', 'created_at' => now(), 'updated_at' => now(),
            ], $campo));
        }
        foreach ($opcoes as $opcao) {
            DB::table('peticao_modelo_campo_opcoes')->insert(array_merge(['created_at' => now(), 'updated_at' => now()], $opcao));
        }

        $runtime = app(PeticaoModeloRuntimeFactory::class)->fromNormalized(PeticaoModelo::findOrFail(97));

        return app(PeticaoComposerService::class)->compose($runtime, $values)['html'];
    }

    public function test_marker_case_decides_value_case()
    {
        $html = $this->compose(
            '<p>[@CAMPO9701@] [@Campo9701@] [@campo9701@] [@Campo9702@]</p>',
            ['campo_9701' => 'joão da silva e souza', 'campo_9702' => 'JABOATÃO DOS GUARARAPES - PE'],
            [['id' => 9701, 'rotulo' => 'NOME'], ['id' => 9702, 'rotulo' => 'COMARCA']]
        );

        $this->assertStringContainsString('[JOÃO DA SILVA E SOUZA]', $html);
        $this->assertStringContainsString('[João da Silva e Souza]', $html);
        $this->assertStringContainsString('[joão da silva e souza]', $html);
        $this->assertStringContainsString('[Jaboatão dos Guararapes - PE]', $html);
    }

    public function test_case_change_keeps_html_tags_and_entities()
    {
        $html = $this->compose(
            '<div>@CAMPO9701@</div>',
            ['campo_9701' => '<p style="text-align:justify">proteção &amp; garantia</p>'],
            [['id' => 9701, 'rotulo' => 'TEXTO', 'tipo' => 'TEXTAREA']]
        );

        $this->assertStringContainsString('<p style="text-align:justify">PROTEÇÃO &amp; GARANTIA</p>', $html);
    }

    public function test_pre_and_pos_markers_build_table_rows_only_for_filled_installments()
    {
        $row = ['prefixo' => '<tr><td>%s</td><td>', 'sufixo' => '</td></tr>'];
        $html = $this->compose(
            '<table>@campo9701_pre@@campo9701@@campo9701_pos@@campo9702_pre@@campo9702@@campo9702_pos@</table>',
            ['campo_9701' => '10/01/2027', 'campo_9702' => ''],
            [
                ['id' => 9701, 'rotulo' => 'VENCIMENTO 1', 'prefixo' => sprintf($row['prefixo'], '01'), 'sufixo' => $row['sufixo']],
                ['id' => 9702, 'rotulo' => 'VENCIMENTO 2', 'prefixo' => sprintf($row['prefixo'], '02'), 'sufixo' => $row['sufixo']],
            ]
        );

        $this->assertSame('<table><tr><td>01</td><td>10/01/2027</td></tr></table>', $html);
    }

    public function test_plain_prefix_goes_with_the_value_only_when_filled()
    {
        $html = $this->compose(
            '<p>Valor: @campo9701@. Outro: @campo9702@.</p>',
            ['campo_9701' => '1.500,00', 'campo_9702' => ''],
            [
                ['id' => 9701, 'rotulo' => 'VALOR', 'prefixo' => 'R$ '],
                ['id' => 9702, 'rotulo' => 'OUTRO', 'prefixo' => 'R$ '],
            ]
        );

        $this->assertStringContainsString('Valor: R$ 1.500,00. Outro: .', $html);
    }

    public function test_commas_left_by_empty_fields_are_cleaned_like_the_legacy()
    {
        $html = $this->compose(
            '<p>@campo9701@, @campo9702@, @campo9703@, residente</p>',
            ['campo_9701' => 'FULANO', 'campo_9702' => '', 'campo_9703' => ''],
            [['id' => 9701, 'rotulo' => 'A'], ['id' => 9702, 'rotulo' => 'B'], ['id' => 9703, 'rotulo' => 'C']]
        );

        $this->assertStringContainsString('<p>FULANO, residente</p>', $html);
    }

    public function test_legacy_select_marker_brings_option_text_and_new_select_brings_return()
    {
        $html = $this->compose(
            '<p>pessoa @CAMPO9701@ / @campo9702@</p>',
            ['campo_9701' => 'FÍSICA', 'campo_9702' => 'Pessoa física'],
            [
                ['id' => 9701, 'rotulo' => 'PESSOA', 'tipo' => 'SELECT'],
                ['id' => 9702, 'legacy_input_id' => null, 'rotulo' => 'QUALIFICAÇÃO', 'tipo' => 'SELECT'],
            ],
            [
                ['campo_id' => 9701, 'legacy_dado_id' => 1, 'rotulo' => 'FÍSICA', 'valor_retorno' => '1', 'ordem' => 1],
                ['campo_id' => 9702, 'rotulo' => 'Pessoa física', 'valor_retorno' => 'brasileiro(a), inscrito(a) no CPF', 'ordem' => 1],
            ]
        );

        $this->assertStringContainsString('pessoa FÍSICA / brasileiro(a), inscrito(a) no CPF', $html);
    }
}
