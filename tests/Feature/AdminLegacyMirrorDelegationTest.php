<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminLegacyMirrorDelegationTest extends TestCase
{
    public function test_legacy_admin_routes_delegate_writes_to_normalized_models_without_legacy_writeback_by_default()
    {
        config()->set('legacy.compat_admin_model_routes', true);

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 11, 'nome_setor' => 'Bancario', 'cod_setor' => 'BAN', 'data_cad' => now()],
            ['id_setor' => 12, 'nome_setor' => 'Execucao', 'cod_setor' => 'EXE', 'data_cad' => now()],
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 111,
            'tipo_nome' => 'Modelo Delegado',
            'nome_pre' => 'Descricao Legada',
            'id_setor' => 11,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
            'cod_cabec' => '<p>Cabecalho Legado</p>',
            'cod_rodap' => '<p>Rodape Legado</p>',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 111,
            'legacy_tipo_id' => 111,
            'legacy_setor_id' => 11,
            'nome' => 'Modelo Delegado',
            'slug' => 'modelo-delegado-111',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'cabecalho_html' => '<p>Cabecalho Legado</p>',
            'rodape_html' => '<p>Rodape Legado</p>',
            'metadata' => json_encode(['nome_pre' => 'Descricao Legada']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put('/admin/modelos/111', [
                'tipo_nome' => 'Modelo Delegado Atualizado',
                'nome_pre' => 'Descricao Delegada',
                'nome_pos' => 'Pos Delegado',
                'id_db' => '',
                'id_cliente' => '',
                'id_setor' => 12,
                'tipo_stt' => 'N',
                'tipo_arq' => 'word',
                'cod_cabec' => '<p>Cabecalho Delegado</p>',
                'cod_rodap' => '<p>Rodape Delegado</p>',
            ])
            ->assertRedirect('/admin/modelos-normalizados/111/edit');

        $this->actingAs($admin)
            ->post('/admin/modelos/111/paragrafos', [
                'fund_titulo' => 'Paragrafo Delegado',
                'fund_text' => '<p>Texto delegado</p>',
            ])
            ->assertRedirect('/admin/modelos-normalizados/111/edit');

        $this->actingAs($admin)
            ->post('/admin/modelos/111/campos', [
                'input_title' => 'Campo Delegado',
                'input_tipo' => 'SELECT',
                'input_pre' => '',
                'input_pos' => '',
                'input_db' => '',
                'input_val' => 'COLUNA_X',
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
                'opcoes' => "Sim|SIM\nNao|NAO",
            ])
            ->assertRedirect('/admin/modelos-normalizados/111/edit');

        $modelo = DB::table('peticao_modelos')->where('id', 111)->first();
        $tipo = DB::table('tp_tipo_tb')->where('tipo_id', 111)->first();
        $paragrafo = DB::table('peticao_modelo_paragrafos')->where('modelo_id', 111)->first();
        $campo = DB::table('peticao_modelo_campos')->where('modelo_id', 111)->first();
        $opcoes = DB::table('peticao_modelo_campo_opcoes')->where('campo_id', $campo->id)->orderBy('ordem')->get();

        $this->assertSame('Modelo Delegado Atualizado', $modelo->nome);
        $this->assertSame('PARAGRAFO DELEGADO', $paragrafo->titulo);
        $this->assertSame('Campo Delegado', $campo->rotulo);
        $this->assertCount(2, $opcoes);
        $this->assertSame('Sim', $opcoes[0]->rotulo);
        $this->assertSame('SIM', $opcoes[0]->valor_retorno);

        $this->assertSame('Modelo Delegado', $tipo->tipo_nome);
        $this->assertNull(DB::table('tp_funda_tb')->where('tipo_id', 111)->first());
        $this->assertNull(DB::table('tp_inputs_tb')->where('tipo_id', 111)->first());
    }
}
