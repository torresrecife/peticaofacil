<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Eventos do legado usados nos formularios Volkswagen: copia de valor em
 * jQuery (inclusive para campos HIDDEN) e alteraInput (TIPO DE PESSOA troca
 * o campo do documento entre CPF e CNPJ).
 */
class PeticaoLegacyFieldEventsTest extends TestCase
{
    protected function seedModelo(array $campos, array $opcoes = [])
    {
        DB::table('setores')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 90,
            'legacy_tipo_id' => 90,
            'legacy_setor_id' => 1,
            'nome' => 'MODELO EVENTOS',
            'slug' => 'modelo-eventos-90',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($campos as $index => $campo) {
            DB::table('peticao_modelo_campos')->insert(array_merge([
                'modelo_id' => 90,
                'token' => '@campo' . $campo['legacy_input_id'] . '@',
                'tipo' => 'TEXT',
                'ordem' => $index + 1,
                'colunas_layout' => 1,
                'linhas_layout' => 0,
                'visivel' => 1,
                'obrigatorio' => 0,
                'gera_nome_arquivo' => 0,
                'eventos_frontend' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ], $campo));
        }

        foreach ($opcoes as $opcao) {
            DB::table('peticao_modelo_campo_opcoes')->insert(array_merge([
                'created_at' => now(),
                'updated_at' => now(),
            ], $opcao));
        }

        return factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);
    }

    public function test_hidden_field_gets_id_and_load_event_so_other_fields_can_fill_it()
    {
        $user = $this->seedModelo([
            ['id' => 9001, 'legacy_input_id' => 9001, 'rotulo' => 'ESTADO'],
            [
                'id' => 9002,
                'legacy_input_id' => 9002,
                'rotulo' => 'UF',
                'tipo' => 'HIDDEN',
                'eventos_frontend' => json_encode(['load' => '$(this).val($("#campo9001").val());']),
            ],
            ['id' => 9003, 'legacy_input_id' => 9003, 'rotulo' => 'COMARCA', 'tipo' => 'HIDDEN'],
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/90')
            ->assertStatus(200)
            ->assertSee('<input type="hidden" id="campo9002" name="campo_9002"', false)
            ->assertSee('data-event-load="$(this).val($(&amp;quot;#campo9001&amp;quot;).val());"', false)
            ->assertSee('<input type="hidden" id="campo9003" name="campo_9003"', false)
            ->assertSee('function copyFieldValue(target, source)', false);
    }

    public function test_cpfcnpj_mask_event_is_handled()
    {
        $user = $this->seedModelo([
            [
                'id' => 9201,
                'legacy_input_id' => 9201,
                'rotulo' => 'CPF/CNPJ',
                'eventos_frontend' => json_encode(['blur' => 'cpfcnpj_mask(9201);']),
            ],
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/90')
            ->assertStatus(200)
            ->assertSee('data-event-blur="cpfcnpj_mask(9201);"', false)
            ->assertSee('var documentMaskPattern = /cpfcnpj_mask', false);
    }

    public function test_person_type_select_exposes_option_codes_for_alteraInput()
    {
        $user = $this->seedModelo([
            [
                'id' => 9101,
                'legacy_input_id' => 9101,
                'rotulo' => 'TIPO DE PESSOA',
                'tipo' => 'SELECT',
                'eventos_frontend' => json_encode(['focus' => 'alteraInput(this,9102,353,354);']),
            ],
            ['id' => 9102, 'legacy_input_id' => 9102, 'rotulo' => 'CPF', 'comportamento' => 'cpf'],
        ], [
            ['campo_id' => 9101, 'legacy_dado_id' => 353, 'rotulo' => 'FÍSICA', 'valor_retorno' => '1', 'ordem' => 1],
            ['campo_id' => 9101, 'legacy_dado_id' => 354, 'rotulo' => 'JURÍDICA', 'valor_retorno' => '2', 'ordem' => 2],
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/90')
            ->assertStatus(200)
            ->assertSee('data-ident="353"', false)
            ->assertSee('data-ident="354"', false)
            ->assertSee('data-event-focus="alteraInput(this,9102,353,354);"', false)
            ->assertSee('function applyPersonType(select, targetId, cpfIdent, cnpjIdent)', false);
    }
}
