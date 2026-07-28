<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoFlowTest extends TestCase
{
    public function test_user_can_compose_preview_open_editor_save_and_export_peticao()
    {
        config()->set('legacy.mirror_legacy_pecas', false);

        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $modeloId = $this->seedModeloCompleto();

        $this->actingAs($user)
            ->get('/peticoes/modelos/' . $modeloId)
            ->assertStatus(200);

        $previewResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId, [
            'action_type' => 'preview',
            'campo_1' => 'Fulano da Silva',
            'campo_2' => 'Urgente',
            'campo_3' => "Linha 1\nLinha 2",
            'campo_4' => 'Deferir',
        ]);

        $previewResponse->assertStatus(200)
            ->assertSee('Preview da peticao')
            ->assertSee('Fulano da Silva')
            ->assertSee('Urgente')
            ->assertSee('Linha 1<br', false)
            ->assertSee('Cabecalho Normalizado', false)
            ->assertSee('Pedido Normalizado', false)
            ->assertSee('Rodape Normalizado', false)
            ->assertSee('deferimento imediato', false)
            ->assertSee('/peticoes/modelos/' . $modeloId . '/peticao-normalizada', false)
            ->assertSee('/peticoes/modelos/' . $modeloId . '/editor', false);

        $previewHtml = $previewResponse->viewData('preview')['html'];
        $this->assertStringNotContainsString('@CAMPO1@', $previewHtml);
        $this->assertStringNotContainsString('@campo2@', $previewHtml);
        $this->assertStringNotContainsString('@Campo3@', $previewHtml);

        $editorResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/editor', [
            'nome_cli' => 'Fulano da Silva',
            'content' => $previewHtml,
        ]);

        $editorResponse->assertStatus(200)
            ->assertSee('Editor final da peca')
            ->assertSee('Salvar peca');

        $saveResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/salvar', [
            'nome_cli' => 'Fulano da Silva',
            'cod_pecas' => $previewHtml,
        ]);

        $this->assertSame(0, DB::table('tp_pecas_tb')->count());

        $peticaoEspelho = DB::table('peticoes')
            ->where('modelo_id', $modeloId)
            ->where('cliente_referencia', 'Fulano da Silva')
            ->first();
        $this->assertNotNull($peticaoEspelho);
        $this->assertNull($peticaoEspelho->legacy_peca_id);
        $this->assertSame('Fulano da Silva', $peticaoEspelho->cliente_referencia);
        $this->assertStringContainsString('deferimento imediato', $peticaoEspelho->conteudo_html);
        $saveResponse->assertRedirect('/peticoes-salvas/' . $peticaoEspelho->id . '/editar');

        $wordResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/exportar/word', [
            'nome_cli' => 'Fulano da Silva',
            'cod_pecas' => $previewHtml,
        ]);

        $wordResponse->assertStatus(200);
        $wordResponse->assertHeader('content-type', 'application/msword; charset=UTF-8');
        $this->assertStringContainsString('deferimento imediato', $wordResponse->getContent());

        $pdfResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/exportar/pdf', [
            'nome_cli' => 'Fulano da Silva',
            'cod_pecas' => '<p>Conteudo simples para PDF</p>',
        ]);

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
    }

    public function test_legacy_editor_residual_flow_accepts_normalized_model_routes()
    {
        config()->set('legacy.mirror_legacy_pecas', false);

        $user = factory(User::class)->create([
            'id_usu' => 88,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $modeloId = $this->seedModeloCompleto();

        $previewResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId, [
            'action_type' => 'preview',
            'campo_1' => 'Beltrano',
            'campo_2' => 'Preferencial',
            'campo_3' => 'Observacao simples',
            'campo_4' => 'Indeferir',
        ]);

        $previewHtml = $previewResponse->viewData('preview')['html'];

        DB::table('tp_tipo_tb')->where('tipo_id', $modeloId)->delete();

        $this->actingAs($user)
            ->post('/peticoes/modelos/' . $modeloId . '/editor', [
                'nome_cli' => 'Beltrano',
                'content' => $previewHtml,
            ])
            ->assertStatus(200)
            ->assertSee('Editor final da peca');

        $saveResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/salvar', [
            'nome_cli' => 'Beltrano',
            'cod_pecas' => $previewHtml,
        ]);

        $this->assertSame(0, DB::table('tp_pecas_tb')->count());

        $peticaoEspelho = DB::table('peticoes')
            ->where('modelo_id', $modeloId)
            ->where('cliente_referencia', 'Beltrano')
            ->first();
        $this->assertNotNull($peticaoEspelho);
        $this->assertNull($peticaoEspelho->legacy_peca_id);
        $saveResponse->assertRedirect('/peticoes-salvas/' . $peticaoEspelho->id . '/editar');

        $wordResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/exportar/word', [
            'nome_cli' => 'Beltrano',
            'cod_pecas' => $previewHtml,
        ]);

        $wordResponse->assertStatus(200);
        $wordResponse->assertHeader('content-type', 'application/msword; charset=UTF-8');

        $pdfResponse = $this->actingAs($user)->post('/peticoes/modelos/' . $modeloId . '/exportar/pdf', [
            'nome_cli' => 'Beltrano',
            'cod_pecas' => '<p>Residual PDF</p>',
        ]);

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_normalized_model_prefers_sql_server_profile_relation_for_runtime_lookup_source()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $modeloId = $this->seedModeloCompleto();

        DB::table('tp_config_db')->insert([
            'id_db' => 90,
            'nome_db' => 'Servidor Legado Runtime',
            'ip_db' => '192.168.10.10',
            'data_db' => 'juridico',
            'usu_db' => 'legacy',
            'senha_db' => '123',
            'table_db' => 'Processos',
            'chave_db' => 'CodigoLegado',
            'query_db' => 'SELECT * FROM Processos',
            'where_db' => 'where 1=1',
            'stt' => 'Y',
        ]);

        DB::table('sql_server_profiles')->insert([
            'id' => 90,
            'legacy_config_id' => 90,
            'nome' => 'Servidor Normalizado Runtime',
            'host' => '10.10.10.10',
            'database_name' => 'juridico_novo',
            'username' => 'normalizado',
            'password' => '456',
            'table_name' => 'ProcessosNovo',
            'lookup_key' => 'CodigoNormalizado',
            'base_query' => 'SELECT * FROM ProcessosNovo',
            'where_clause' => 'where 1=1',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tp_tipo_tb')->where('tipo_id', $modeloId)->update(['id_db' => 90]);
        DB::table('peticao_modelos')->where('id', $modeloId)->update(['legacy_sql_config_id' => 90]);

        $response = $this->actingAs($user)->get('/peticoes/modelos/' . $modeloId);

        $response->assertStatus(200)
            ->assertSee('CodigoNormalizado')
            ->assertDontSee('CodigoLegado');
    }

    protected function seedModeloCompleto()
    {
        DB::table('tp_setor_tb')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('tp_clientes_db')->insert([
            'cliente_id' => 1,
            'cliente_name' => 'Cliente Base',
            'cliente_cod' => 'CLI001',
            'cliente_area' => 1,
            'cliente_status' => 'Y',
            'cliente_creator' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 1,
            'id_db' => null,
            'tipo_nome' => 'Peticao de Teste',
            'id_cliente' => 1,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'id_setor' => 1,
            'cod_cabec' => '<p>Cabecalho @CAMPO1@</p>',
            'cod_rodap' => '<p>Rodape @campo4@</p>',
            'tipo_arq' => 'doc',
        ]);

        DB::table('tp_funda_tb')->insert([
            [
                'fund_id' => 1,
                'tipo_id' => 1,
                'fund_titulo' => 'Primeiro',
                'fund_text' => '<p>Pedido @campo2@</p>',
                'fund_order' => 1,
                'fund_data' => now(),
                'fund_visi' => 'Y',
                'fund_stt' => 'Y',
            ],
            [
                'fund_id' => 2,
                'tipo_id' => 1,
                'fund_titulo' => 'Segundo',
                'fund_text' => '<p>Detalhe @Campo3@</p>',
                'fund_order' => 2,
                'fund_data' => now(),
                'fund_visi' => 'Y',
                'fund_stt' => 'Y',
            ],
        ]);

        DB::table('tp_inputs_tb')->insert([
            [
                'id_input' => 1,
                'tipo_id' => 1,
                'input_title' => 'Cliente',
                'input_tipo' => 'TEXT',
                'input_order' => 1,
                'listsel' => 'N',
                'nomepet' => 'Y',
                'hide' => 'N',
            ],
            [
                'id_input' => 2,
                'tipo_id' => 1,
                'input_title' => 'Pedido',
                'input_tipo' => 'TEXT',
                'input_order' => 2,
                'listsel' => 'N',
                'nomepet' => 'N',
                'hide' => 'N',
            ],
            [
                'id_input' => 3,
                'tipo_id' => 1,
                'input_title' => 'Observacoes',
                'input_tipo' => 'TEXTAREA',
                'input_order' => 3,
                'listsel' => 'N',
                'nomepet' => 'N',
                'hide' => 'N',
            ],
            [
                'id_input' => 4,
                'tipo_id' => 1,
                'input_title' => 'Resultado',
                'input_tipo' => 'SELECT',
                'input_order' => 4,
                'listsel' => 'N',
                'nomepet' => 'N',
                'hide' => 'N',
            ],
        ]);

        DB::table('tp_dados_tb')->insert([
            [
                'id_dados' => 1,
                'id_input' => 4,
                'nome_dados' => 'Deferir',
                'return_1' => 'deferimento imediato',
                'data_cad' => now(),
                'dados_order' => 1,
                'listsel' => 'N',
            ],
            [
                'id_dados' => 2,
                'id_input' => 4,
                'nome_dados' => 'Indeferir',
                'return_1' => 'indeferimento',
                'data_cad' => now(),
                'dados_order' => 2,
                'listsel' => 'N',
            ],
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 1,
            'legacy_tipo_id' => 1,
            'legacy_cliente_id' => 1,
            'legacy_setor_id' => 1,
            'legacy_sql_config_id' => null,
            'nome' => 'Peticao de Teste',
            'slug' => 'peticao-de-teste-1',
            'status' => 'ativo',
            'arquivo_padrao' => 'doc',
            'cabecalho_html' => '<p>Cabecalho Normalizado @CAMPO1@</p>',
            'rodape_html' => '<p>Rodape Normalizado @campo4@</p>',
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_paragrafos')->insert([
            [
                'id' => 1,
                'modelo_id' => 1,
                'legacy_fund_id' => 1,
                'titulo' => 'Primeiro',
                'conteudo_html' => '<p>Pedido Normalizado @campo2@</p>',
                'ordem' => 1,
                'visivel' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'modelo_id' => 1,
                'legacy_fund_id' => 2,
                'titulo' => 'Segundo',
                'conteudo_html' => '<p>Detalhe Normalizado @Campo3@</p>',
                'ordem' => 2,
                'visivel' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('peticao_modelo_campos')->insert([
            [
                'id' => 1,
                'modelo_id' => 1,
                'legacy_input_id' => 1,
                'rotulo' => 'Cliente',
                'token' => '@campo1@',
                'tipo' => 'TEXT',
                'origem_coluna' => null,
                'ordem' => 1,
                'visivel' => 1,
                'gera_nome_arquivo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'modelo_id' => 1,
                'legacy_input_id' => 2,
                'rotulo' => 'Pedido',
                'token' => '@campo2@',
                'tipo' => 'TEXT',
                'origem_coluna' => null,
                'ordem' => 2,
                'visivel' => 1,
                'gera_nome_arquivo' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'modelo_id' => 1,
                'legacy_input_id' => 3,
                'rotulo' => 'Observacoes',
                'token' => '@campo3@',
                'tipo' => 'TEXTAREA',
                'origem_coluna' => null,
                'ordem' => 3,
                'visivel' => 1,
                'gera_nome_arquivo' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'modelo_id' => 1,
                'legacy_input_id' => 4,
                'rotulo' => 'Resultado',
                'token' => '@campo4@',
                'tipo' => 'SELECT',
                'origem_coluna' => 'RESULTADO',
                'ordem' => 4,
                'visivel' => 1,
                'gera_nome_arquivo' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('peticao_modelo_campo_opcoes')->insert([
            [
                'id' => 1,
                'campo_id' => 4,
                'legacy_dado_id' => 1,
                'rotulo' => 'Deferir',
                'valor_retorno' => 'deferimento imediato',
                'ordem' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'campo_id' => 4,
                'legacy_dado_id' => 2,
                'rotulo' => 'Indeferir',
                'valor_retorno' => 'indeferimento',
                'ordem' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return 1;
    }
}
