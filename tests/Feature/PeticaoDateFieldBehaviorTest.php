<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoDateFieldBehaviorTest extends TestCase
{
    public function test_mounting_renders_frontend_date_event_attributes_for_text_field()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('setores')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 70,
            'legacy_tipo_id' => 70,
            'legacy_setor_id' => 1,
            'nome' => 'MODELO DATA',
            'slug' => 'modelo-data-70',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            'id' => 7001,
            'modelo_id' => 70,
            'legacy_input_id' => 7001,
            'rotulo' => 'DATA DO DOCUMENTO',
            'token' => '@campo7001@',
            'tipo' => 'TEXT',
            'comportamento' => 'date',
            'ordem' => 1,
            'colunas_layout' => 1,
            'linhas_layout' => 0,
            'visivel' => 1,
            'obrigatorio' => 1,
            'gera_nome_arquivo' => 0,
            'eventos_frontend' => json_encode([
                'load' => 'data_atual(this);',
                'blur' => 'data_extenso_out(this);',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/peticoes/modelos/70');

        $response->assertStatus(200)
            ->assertSee('class="js-frontend-event-field"', false)
            ->assertSee('data-event-load="data_atual(this);"', false)
            ->assertSee('data-event-blur="data_extenso_out(this);"', false)
            ->assertSee('data-input-behavior="date"', false)
            ->assertSee('function formatDate(value)', false)
            ->assertSee("behavior === 'date'", false);
    }

    public function test_mounting_renders_combined_cpf_cnpj_mask_behavior()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('setores')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 71,
            'legacy_tipo_id' => 71,
            'legacy_setor_id' => 1,
            'nome' => 'MODELO CPF CNPJ',
            'slug' => 'modelo-cpf-cnpj-71',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            'id' => 7101,
            'modelo_id' => 71,
            'legacy_input_id' => 7101,
            'rotulo' => 'CPF/CNPJ',
            'token' => '@campo7101@',
            'tipo' => 'TEXT',
            'comportamento' => 'cpf_cnpj',
            'ordem' => 1,
            'colunas_layout' => 1,
            'linhas_layout' => 0,
            'visivel' => 1,
            'obrigatorio' => 1,
            'gera_nome_arquivo' => 0,
            'eventos_frontend' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/71')
            ->assertStatus(200)
            ->assertSee('data-input-behavior="cpf_cnpj"', false)
            ->assertSee("behavior === 'cpf_cnpj'", false)
            ->assertSee("formatCpf(cpfCnpjDigits) : formatCnpj(cpfCnpjDigits)", false);
    }

    public function test_mounting_renders_live_brazilian_currency_mask_behavior()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('setores')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 72,
            'legacy_tipo_id' => 72,
            'legacy_setor_id' => 1,
            'nome' => 'MODELO VALOR',
            'slug' => 'modelo-valor-72',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            'id' => 7201,
            'modelo_id' => 72,
            'legacy_input_id' => 7201,
            'rotulo' => 'VALOR DO CONTRATO',
            'token' => '@campo7201@',
            'tipo' => 'TEXT',
            'comportamento' => 'decimal',
            'ordem' => 1,
            'colunas_layout' => 1,
            'linhas_layout' => 0,
            'visivel' => 1,
            'obrigatorio' => 1,
            'gera_nome_arquivo' => 0,
            'eventos_frontend' => json_encode([
                'blur' => 'fillCurrencyWords(this, 7202);',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/72')
            ->assertStatus(200)
            ->assertSee('data-input-behavior="decimal"', false)
            ->assertSee('data-event-blur="fillCurrencyWords(this, 7202);"', false)
            ->assertSee('inputmode="decimal"', false)
            ->assertSee('function formatCurrencyInput(value)', false)
            ->assertSee("field.value = formatCurrencyInput(field.value)", false)
            ->assertSee('function currencyToWords(value)', false)
            ->assertSee('function fillCurrencyWords(sourceField, targetFieldId)', false);
    }
}
