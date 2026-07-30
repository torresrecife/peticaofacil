<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNormalizedCampoListaAssociationTest extends TestCase
{
    public function test_admin_can_store_field_with_associated_list_and_target_return()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
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

        DB::table('peticao_modelo_campos')->insert([
            'id' => 1997,
            'modelo_id' => 48,
            'legacy_input_id' => 1997,
            'rotulo' => 'ENDERECO DO AUTOR',
            'token' => '@campo1997@',
            'tipo' => 'TEXT',
            'ordem' => 2,
            'colunas_layout' => 1,
            'linhas_layout' => 0,
            'visivel' => 1,
            'obrigatorio' => 1,
            'gera_nome_arquivo' => 0,
            'eventos_frontend' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/modelos-normalizados/48/campos', [
                'input_title' => 'NOME DO AUTOR',
                'input_tipo' => 'SELECT',
                'input_pre' => '',
                'input_pos' => '',
                'input_db' => '',
                'input_val' => 'autor',
                'input_alt' => '',
                'input_cols' => 1,
                'input_rols' => 0,
                'input_focu' => '',
                'input_load' => '',
                'input_blur' => '',
                'input_width' => 265,
                'input_req' => 1,
                'input_order' => 1,
                'nomepet' => 'N',
                'hide' => 'true',
                'texto_padrao' => '',
                'add_class' => '',
                'opcoes' => '',
                'input_list_group_id' => 2,
                'input_list_return_column' => 'return_1',
                'input_list_target_field' => 1997,
            ])
            ->assertRedirect('/admin/modelos-normalizados/48/edit');

        $campo = DB::table('peticao_modelo_campos')
            ->where('modelo_id', 48)
            ->where('rotulo', 'NOME DO AUTOR')
            ->first();

        $this->assertNotNull($campo);
        $this->assertSame('tp_lista_tb_|_nome_lista_|_return_1_|_id_grupo=2_|_vert', $campo->origem_alias);

        $eventos = json_decode($campo->eventos_frontend, true);
        $this->assertStringContainsString('fc_ajax_comp("tp_lista_tb","return_1","campo1997"', $eventos['focus']);
        $this->assertStringContainsString('campo' . $campo->id . '_|_campo1997', $eventos['focus']);
    }
}
