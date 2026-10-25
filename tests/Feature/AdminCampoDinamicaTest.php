<?php

namespace Tests\Feature;

use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\Support\CampoDinamica;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCampoDinamicaTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 10, 'nome_setor' => 'Recuperacao', 'cod_setor' => 'REC', 'data_cad' => now()]);
        DB::table('peticao_modelos')->insert([
            'id' => 98, 'legacy_tipo_id' => 98, 'legacy_setor_id' => 10, 'nome' => 'DINAMICAS', 'slug' => 'dinamicas-98',
            'status' => 'ativo', 'arquivo_padrao' => 'pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([9801 => 'SEGREDO', 9802 => 'TITULO SEGREDO', 9803 => 'PARAGRAFO SEGREDO', 9804 => 'PESSOA', 9805 => 'CPF/CNPJ', 9806 => 'QUALIFICACAO'] as $id => $rotulo) {
            DB::table('peticao_modelo_campos')->insert([
                'id' => $id, 'modelo_id' => 98, 'legacy_input_id' => $id, 'rotulo' => $rotulo, 'token' => '@campo' . $id . '@',
                'tipo' => in_array($id, [9801, 9804], true) ? 'SELECT' : 'TEXT', 'ordem' => $id - 9800, 'visivel' => 1, 'obrigatorio' => 0,
                'gera_nome_arquivo' => 0, 'colunas_layout' => 1, 'linhas_layout' => 0, 'eventos_frontend' => '[]',
                'created_at' => '2026-08-02 22:18:40', 'updated_at' => '2026-08-02 22:18:40',
            ]);
        }
        DB::table('peticao_modelo_campo_opcoes')->insert([
            ['id' => 501, 'campo_id' => 9801, 'legacy_dado_id' => 631, 'rotulo' => 'SIM', 'valor_retorno' => 'DO SEGREDO', 'valores_extras' => json_encode(['return_2' => '<p>O tramite em segredo.</p>']), 'ordem' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 502, 'campo_id' => 9801, 'legacy_dado_id' => 632, 'rotulo' => 'NÃO', 'valor_retorno' => null, 'valores_extras' => null, 'ordem' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 503, 'campo_id' => 9804, 'legacy_dado_id' => 353, 'rotulo' => 'FÍSICA', 'valor_retorno' => '1', 'valores_extras' => null, 'ordem' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 504, 'campo_id' => 9804, 'legacy_dado_id' => 354, 'rotulo' => 'JURÍDICA', 'valor_retorno' => '2', 'valores_extras' => null, 'ordem' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function save($campoId, array $extra)
    {
        $campo = PeticaoModeloCampo::findOrFail($campoId);

        return $this->actingAs($this->admin)->put('/admin/modelos-normalizados/98/campos/' . $campoId, array_merge([
            'input_title' => $campo->rotulo,
            'input_tipo' => $campo->tipo,
            'input_cols' => 1,
            'input_req' => 0,
            'opcoes' => '',
        ], $extra));
    }

    public function test_option_return_fills_two_fields_and_options_keep_legacy_code_and_extra_returns()
    {
        $this->save(9801, [
            // A caixa de opcoes so traz o retorno 1: o retorno 2 e o codigo do legado continuam.
            'opcoes' => "SIM|DO SEGREDO\nNÃO|",
            'dinamica_tipo' => 'retorno_opcao',
            'dinamica_alvos' => [9802, 9803, ''],
            'dinamica_colunas' => ['return_1', 'return_2', 'return_1'],
            'dinamica_mostrar_alvos' => 1,
        ])->assertRedirect();

        $campo = PeticaoModeloCampo::findOrFail(9801);
        $this->assertStringContainsString('fc_ajax_comp("tp_dados_tb","return_1","campo9802"', $campo->eventos_frontend['focus']);
        $this->assertStringContainsString('fc_ajax_comp("tp_dados_tb","return_2","campo9803"', $campo->eventos_frontend['focus']);
        $this->assertStringContainsString('mcampo("campo9801_|_campo9803");', $campo->eventos_frontend['focus']);
        $this->assertSame('retorno_opcao', $campo->eventos_frontend['dinamica']['tipo']);
        $this->assertCount(2, $campo->dependent_fill_configs);

        $sim = PeticaoModeloCampoOpcao::findOrFail(501);
        $this->assertSame(631, (int) $sim->legacy_dado_id);
        $this->assertSame('<p>O tramite em segredo.</p>', $sim->valores_extras['return_2']);
        $this->assertSame(2, PeticaoModeloCampoOpcao::where('campo_id', 9801)->count());
    }

    public function test_person_type_uses_the_option_codes_and_qualification_points_to_its_fields()
    {
        $this->save(9804, ['opcoes' => "FÍSICA|1\nJURÍDICA|2", 'dinamica_tipo' => 'tipo_pessoa', 'dinamica_alvo' => 9805])->assertRedirect();
        $this->assertSame('alteraInput(this,9805,353,354);', PeticaoModeloCampo::findOrFail(9804)->eventos_frontend['focus']);

        $this->save(9806, ['dinamica_tipo' => 'qualificacao', 'dinamica_pessoa' => 9804, 'dinamica_documento' => 9805])->assertRedirect();
        $this->assertSame('merge_fields("pessoa |9804|9805","9806");', PeticaoModeloCampo::findOrFail(9806)->eventos_frontend['focus']);
    }

    public function test_block_filing_adds_the_class_the_engine_looks_for()
    {
        $this->save(9802, ['dinamica_tipo' => 'nao_ajuizar', 'dinamica_modo' => 'excecao', 'dinamica_argumento' => 'RECEBIDA'])->assertRedirect();

        $campo = PeticaoModeloCampo::findOrFail(9802);
        $this->assertSame('validate_peticao("RECEBIDA");', $campo->eventos_frontend['focus']);
        $this->assertStringContainsString('new_required', $campo->classe_css);
    }

    public function test_quantity_copy_next_installment_and_show_by_value()
    {
        $this->save(9801, ['opcoes' => "SIM|DO SEGREDO\nNÃO|", 'dinamica_tipo' => 'quantidade', 'dinamica_por_item' => 2, 'dinamica_campos' => [9802, 9803]])->assertRedirect();
        $this->assertSame('nparcelas("campo9801_|_campo9802_|_campo9803");', PeticaoModeloCampo::findOrFail(9801)->eventos_frontend['focus']);

        $this->save(9802, ['dinamica_tipo' => 'copiar', 'dinamica_campos' => [9803]])->assertRedirect();
        $this->assertSame('$("#campo9803").val($(this).val());', PeticaoModeloCampo::findOrFail(9802)->eventos_frontend['blur']);

        $this->save(9803, ['dinamica_tipo' => 'proxima_parcela', 'dinamica_modo' => 'data', 'dinamica_alvo' => 9805])->assertRedirect();
        $this->assertSame('addDataCampo(9803,9805,1);', PeticaoModeloCampo::findOrFail(9803)->eventos_frontend['blur']);

        $this->save(9805, ['dinamica_tipo' => 'mostrar_por_valor', 'dinamica_valor' => 'PESQUISA', 'dinamica_campos' => [9806], 'dinamica_obrigatorio' => 1])->assertRedirect();
        $conditional = PeticaoModeloCampo::findOrFail(9805)->eventos_frontend['conditional'];
        $this->assertSame(['value' => 'PESQUISA', 'target_ids' => [9806], 'required' => true], $conditional);
    }

    public function test_date_in_words_survives_saving_a_text_field_without_mask()
    {
        $this->save(9803, ['input_blur' => 'data_extenso_out(this);'])->assertRedirect();

        $this->assertSame('data_extenso_out(this);', PeticaoModeloCampo::findOrFail(9803)->eventos_frontend['blur']);
    }

    public function test_legacy_scripts_are_recognised_only_when_they_are_a_single_dynamic()
    {
        $this->assertSame('quantidade', CampoDinamica::detect(['focus' => 'nbens("campo1_|_campo2","SELECT");'], 1)['tipo']);
        $this->assertSame('SELECT', CampoDinamica::detect(['focus' => 'nbens("campo1_|_campo2","SELECT");'], 1)['seletor']);
        $this->assertSame(['focus', 'load'], CampoDinamica::detect(['focus' => '$("#campo2").val($(this).val());', 'load' => '$("#campo2").val($(this).val());'], 1)['eventos']);
        // Mistura de dinamicas ou com lista/data: fica como script personalizado.
        $this->assertNull(CampoDinamica::detect(['focus' => 'validate_peticao();mcampo("campo1_|_campo2");'], 1));
        $this->assertNull(CampoDinamica::detect(['focus' => 'fc_ajax_comp("tp_lista_tb","return_1","campo2","unir","id_lista",this,1); mcampo("campo1_|_campo2");'], 1));
        $this->assertNull(CampoDinamica::detect(['blur' => 'fc_newstring(this); $("#campo2").val($(this).val());'], 1));
    }

    public function test_edit_screen_shows_the_dynamic_panel_with_the_recognised_dynamic()
    {
        DB::table('peticao_modelo_campos')->where('id', 9801)->update(['eventos_frontend' => json_encode([
            'focus' => 'fc_ajax_comp("tp_dados_tb","return_2","campo9803","unir","id_dados",this,1); mcampo("campo9801_|_campo9803");',
        ])]);

        $response = $this->actingAs($this->admin)->get('/admin/modelos-normalizados/98/edit')
            ->assertStatus(200)
            ->assertSee('Dinâmica do campo')
            ->assertSee('· A opção escolhida preenche outros campos', false)
            ->assertSee('SIM|DO SEGREDO|&lt;p&gt;O tramite em segredo.&lt;/p&gt;', false);
        $this->assertRegExp('/<option value="retorno_opcao"\s+selected\s*>/', $response->getContent());
    }
}
