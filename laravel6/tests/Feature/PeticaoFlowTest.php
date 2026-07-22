<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoFlowTest extends TestCase
{
    public function test_user_can_compose_preview_open_editor_save_and_export_peticao()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $modeloId = $this->seedModeloCompleto();

        $this->actingAs($user)
            ->get('/peticoes/' . $modeloId)
            ->assertStatus(200)
            ->assertSee('Montagem de peticao')
            ->assertSee('Cliente');

        $previewResponse = $this->actingAs($user)->post('/peticoes/' . $modeloId, [
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
            ->assertSee('deferimento imediato', false);

        $previewHtml = $previewResponse->viewData('preview')['html'];
        $this->assertStringNotContainsString('@CAMPO1@', $previewHtml);
        $this->assertStringNotContainsString('@campo2@', $previewHtml);
        $this->assertStringNotContainsString('@Campo3@', $previewHtml);

        $editorResponse = $this->actingAs($user)->post('/peticoes/' . $modeloId . '/editor', [
            'nome_cli' => 'Fulano da Silva',
            'content' => $previewHtml,
        ]);

        $editorResponse->assertStatus(200)
            ->assertSee('Editor final da peca')
            ->assertSee('Salvar peca');

        $saveResponse = $this->actingAs($user)->post('/peticoes/' . $modeloId . '/salvar', [
            'nome_cli' => 'Fulano da Silva',
            'cod_pecas' => $previewHtml,
        ]);

        $peca = DB::table('tp_pecas_tb')->where('nome_cli', 'Fulano da Silva')->first();

        $this->assertNotNull($peca);
        $this->assertSame($modeloId, (int) $peca->tipo_id);
        $this->assertStringContainsString('deferimento imediato', $peca->cod_pecas);

        $saveResponse->assertRedirect('/pecas/' . $peca->id_pecas . '/editar');

        $peticaoEspelho = DB::table('peticoes')->where('legacy_peca_id', $peca->id_pecas)->first();
        $this->assertNotNull($peticaoEspelho);
        $this->assertSame('Fulano da Silva', $peticaoEspelho->cliente_referencia);
        $this->assertStringContainsString('deferimento imediato', $peticaoEspelho->conteudo_html);

        $wordResponse = $this->actingAs($user)->post('/peticoes/' . $modeloId . '/exportar/word', [
            'nome_cli' => 'Fulano da Silva',
            'cod_pecas' => $previewHtml,
        ]);

        $wordResponse->assertStatus(200);
        $wordResponse->assertHeader('content-type', 'application/msword; charset=UTF-8');
        $this->assertStringContainsString('deferimento imediato', $wordResponse->getContent());

        $pdfResponse = $this->actingAs($user)->post('/peticoes/' . $modeloId . '/exportar/pdf', [
            'nome_cli' => 'Fulano da Silva',
            'cod_pecas' => '<p>Conteudo simples para PDF</p>',
        ]);

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
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
            'cabecalho_html' => '<p>Cabecalho @CAMPO1@</p>',
            'rodape_html' => '<p>Rodape @campo4@</p>',
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return 1;
    }
}
