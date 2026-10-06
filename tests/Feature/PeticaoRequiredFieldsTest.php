<?php

namespace Tests\Feature;

use App\Services\PeticaoRequiredFieldsService;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoRequiredFieldsTest extends TestCase
{
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create(['nivel_usu' => 'USU', 'acesso_usu' => now()]);
        $this->seedModel();
    }

    public function test_form_marks_only_the_required_fields_that_take_input()
    {
        $html = $this->actingAs($this->user)->get('/peticoes/modelos/700')->assertStatus(200)->getContent();

        foreach ([3001, 3005, 3006, 3007] as $id) {
            $this->assertSame(1, preg_match('/dis_campo' . $id . '\b[^>]*data-required="1"/', $html), 'campo ' . $id);
        }
        // Opcional, e titulo marcado como obrigatorio no cadastro: sem marca.
        foreach ([3002, 3003] as $id) {
            $this->assertSame(0, preg_match('/dis_campo' . $id . '\b[^>]*data-required/', $html), 'campo ' . $id);
        }

        $this->assertSame(4, substr_count($html, '<span class="required-mark" title='));
        $this->assertStringContainsString('name="hidden_fields"', $html);
        $this->assertStringContainsString('id="required-check"', $html);
    }

    public function test_preview_is_generated_and_lists_the_required_fields_left_blank()
    {
        $response = $this->actingAs($this->user)->post('/peticoes/modelos/700', [
            'action_type' => 'preview',
            'campo_3002' => 'so o opcional',
        ]);

        $response->assertStatus(200)->assertSee('Preview da peticao');
        $this->assertNotNull($response->viewData('preview'));

        // 3005 comeca escondido e, sem a tela dizer o contrario, nao e cobrado.
        $this->assertSame(
            [['id' => 3001, 'label' => 'NOME ALFA'], ['id' => 3006, 'label' => 'TEXTO ZETA'], ['id' => 3007, 'label' => 'TIPO ETA']],
            $response->viewData('missingRequired')
        );

        $html = $response->getContent();
        $this->assertStringContainsString('data-missing-required="3001,3006,3007"', $html);
        $this->assertStringContainsString('3 campos obrigatórios em branco', $html);
        $this->assertStringContainsString('NOME ALFA, TEXTO ZETA, TIPO ETA.', $html);
    }

    public function test_no_warning_when_every_required_field_is_filled()
    {
        $response = $this->actingAs($this->user)->post('/peticoes/modelos/700', $this->filled());

        $response->assertStatus(200)->assertSee('Preview da peticao');
        $this->assertSame([], $response->viewData('missingRequired'));
        $this->assertStringNotContainsString('data-missing-required=', $response->getContent());
    }

    public function test_fields_hidden_by_the_screen_rules_are_not_charged()
    {
        // A tela informa o que esta escondido agora: 3001 sumiu por uma regra, 3005 apareceu.
        $response = $this->actingAs($this->user)->post('/peticoes/modelos/700', array_merge($this->filled(), [
            'campo_3001' => '',
            'hidden_fields' => '0,3001',
        ]));

        $this->assertSame([['id' => 3005, 'label' => 'BEM EPSILON']], $response->viewData('missingRequired'));
        $this->assertStringContainsString('1 campo obrigatório em branco', $response->getContent());

        // "0" sozinho: a tela informou que nada esta escondido.
        $response = $this->actingAs($this->user)->post('/peticoes/modelos/700', array_merge($this->filled(), [
            'hidden_fields' => '0',
        ]));

        $this->assertSame([['id' => 3005, 'label' => 'BEM EPSILON']], $response->viewData('missingRequired'));
    }

    public function test_empty_rich_text_counts_as_blank_but_an_image_does_not()
    {
        $blank = $this->actingAs($this->user)->post('/peticoes/modelos/700', array_merge($this->filled(), [
            'campo_3006' => '<p>&nbsp;</p>',
        ]));
        $this->assertSame([['id' => 3006, 'label' => 'TEXTO ZETA']], $blank->viewData('missingRequired'));

        $image = $this->actingAs($this->user)->post('/peticoes/modelos/700', array_merge($this->filled(), [
            'campo_3006' => '<p><img src="assinatura.png"></p>',
        ]));
        $this->assertSame([], $image->viewData('missingRequired'));
    }

    public function test_lookup_action_does_not_check_required_fields()
    {
        $response = $this->actingAs($this->user)->post('/peticoes/modelos/700', ['action_type' => 'lookup']);

        $response->assertStatus(200);
        $this->assertNull($response->viewData('preview'));
        $this->assertSame([], $response->viewData('missingRequired'));
    }

    public function test_blank_and_hidden_list_rules()
    {
        $service = new PeticaoRequiredFieldsService();

        foreach ([null, '', '   ', "\n\t", '<p></p>', '<p>&nbsp;</p>', "<div><br>\xC2\xA0</div>", []] as $value) {
            $this->assertTrue($service->isBlank($value), var_export($value, true));
        }
        foreach (['0', 'a', '<p>texto</p>', '<table><tr><td></td></tr></table>', '<hr>', ['', 'x']] as $value) {
            $this->assertFalse($service->isBlank($value), var_export($value, true));
        }

        $this->assertNull($service->parseHiddenIds(null));
        $this->assertNull($service->parseHiddenIds(''));
        $this->assertSame([], $service->parseHiddenIds('0'));
        $this->assertSame([12, 15], $service->parseHiddenIds('0,12, 15,abc,-3'));
    }

    private function filled()
    {
        return [
            'action_type' => 'preview',
            'campo_3001' => 'Fulano',
            'campo_3006' => '<p>Texto informado</p>',
            'campo_3007' => 'JUDICIAL',
        ];
    }

    private function seedModel()
    {
        DB::table('setores')->insert(['id_setor' => 1, 'nome_setor' => 'Juridico', 'cod_setor' => 'JUR', 'data_cad' => now()]);

        DB::table('peticao_modelos')->insert([
            'id' => 700, 'legacy_tipo_id' => 700, 'legacy_setor_id' => 1, 'nome' => 'MODELO COM OBRIGATORIOS',
            'slug' => 'modelo-com-obrigatorios', 'status' => 'ativo', 'arquivo_padrao' => 'pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_paragrafos')->insert([
            'id' => 7001, 'modelo_id' => 700, 'legacy_fund_id' => 7001, 'titulo' => 'Corpo',
            'conteudo_html' => '<p>@campo3001@ @campo3002@ @campo3005@ @campo3006@ @campo3007@</p>',
            'ordem' => 1, 'visivel' => 1, 'ativo' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $fields = [
            [3001, 'NOME ALFA', 'TEXT', 1, 1],
            [3002, 'OBSERVACAO BETA', 'TEXT', 0, 1],
            // Marcados como obrigatorios no cadastro, mas ninguem digita num titulo nem num campo oculto.
            [3003, 'TITULO GAMA', 'TITLE', 1, 1],
            [3004, 'OCULTO DELTA', 'HIDDEN', 1, 1],
            // Obrigatorio que so aparece quando uma regra da tela o mostra.
            [3005, 'BEM EPSILON', 'TEXT', 1, 0],
            [3006, 'TEXTO ZETA', 'TEXTAREA', 1, 1],
            [3007, 'TIPO ETA', 'SELECT', 1, 1],
        ];

        foreach ($fields as $index => $field) {
            DB::table('peticao_modelo_campos')->insert([
                'id' => $field[0], 'modelo_id' => 700, 'legacy_input_id' => $field[0], 'rotulo' => $field[1],
                'token' => '@campo' . $field[0] . '@', 'tipo' => $field[2], 'colunas_layout' => 1, 'linhas_layout' => 0,
                'ordem' => $index + 1, 'obrigatorio' => $field[3], 'visivel' => $field[4], 'gera_nome_arquivo' => 0,
                'eventos_frontend' => json_encode([]), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('peticao_modelo_campo_opcoes')->insert([
            'id' => 70, 'campo_id' => 3007, 'legacy_dado_id' => 70, 'rotulo' => 'JUDICIAL', 'valor_retorno' => 'judicial',
            'ordem' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
