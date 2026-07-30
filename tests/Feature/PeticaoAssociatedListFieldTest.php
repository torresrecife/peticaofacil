<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoAssociatedListFieldTest extends TestCase
{
    public function test_associated_list_select_renders_list_options_and_preview_keeps_label_value()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('lista_grupos')->insert([
            'id_grupo' => 2,
            'legacy_grupo_id' => 2,
            'nome_grupo' => 'CLIENTES',
            'data_cad' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lista_itens')->insert([
            'id_lista' => 7001,
            'legacy_lista_id' => 7001,
            'id_grupo' => 2,
            'nome_lista' => 'JOAO DA SILVA',
            'return_1' => 'RUA UM, 123',
            'data_cad' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 48,
            'legacy_tipo_id' => 48,
            'legacy_setor_id' => 1,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento-48',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_paragrafos')->insert([
            'id' => 1,
            'modelo_id' => 48,
            'legacy_fund_id' => 1,
            'titulo' => 'Corpo',
            'conteudo_html' => '<p>Autor: @campo1996@</p><p>Endereco: @campo1997@</p>',
            'ordem' => 1,
            'visivel' => 1,
            'ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            [
                'id' => 1996,
                'modelo_id' => 48,
                'legacy_input_id' => 1996,
                'rotulo' => 'NOME DO AUTOR',
                'token' => '@campo1996@',
                'tipo' => 'SELECT',
                'origem_alias' => 'tp_lista_tb_|_nome_lista_|_return_1_|_id_grupo=2_|_vert',
                'origem_coluna' => 'autor',
                'colunas_layout' => 1,
                'linhas_layout' => 0,
                'ordem' => 1,
                'obrigatorio' => 1,
                'visivel' => 1,
                'gera_nome_arquivo' => 0,
                'eventos_frontend' => json_encode([
                    'focus' => 'fc_ajax_comp("tp_lista_tb","return_1","campo1997","unir","id_lista",this,1); mcampo("campo1996_|_campo1997"); $("#campo1997").focus();',
                    'load' => 'fc_ajax_comp("tp_lista_tb","return_1","campo1997","unir","id_lista",this,1); mcampo("campo1996_|_campo1997"); $("#campo1997").focus();',
                    'blur' => 'fc_ajax_comp("tp_lista_tb","return_1","campo1997","unir","id_lista",this,1); mcampo("campo1996_|_campo1997"); $("#campo1997").focus();',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1997,
                'modelo_id' => 48,
                'legacy_input_id' => 1997,
                'rotulo' => 'ENDERECO DO AUTOR',
                'token' => '@campo1997@',
                'tipo' => 'TEXT',
                'origem_alias' => null,
                'origem_coluna' => null,
                'colunas_layout' => 1,
                'linhas_layout' => 0,
                'ordem' => 2,
                'obrigatorio' => 1,
                'visivel' => 1,
                'gera_nome_arquivo' => 0,
                'eventos_frontend' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->get('/peticoes/modelos/48')
            ->assertStatus(200)
            ->assertSee('JOAO DA SILVA')
            ->assertSee('data-target-field="1997"', false)
            ->assertSee('data-return-column="return_1"', false)
            ->assertSee('data-return-1="RUA UM, 123"', false);

        $preview = $this->actingAs($user)->post('/peticoes/modelos/48', [
            'action_type' => 'preview',
            'campo_1996' => 'JOAO DA SILVA',
            'campo_1997' => 'RUA UM, 123',
        ]);

        $preview->assertStatus(200)
            ->assertSee('Autor: JOAO DA SILVA', false)
            ->assertSee('Endereco: RUA UM, 123', false)
            ->assertDontSee('Autor: RUA UM, 123', false);
    }
}
