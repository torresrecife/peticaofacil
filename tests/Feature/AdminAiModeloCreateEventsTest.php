<?php

namespace Tests\Feature;

use App\PeticaoModeloCampo;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAiModeloCreateEventsTest extends TestCase
{
    public function test_date_field_created_by_ai_keeps_the_typed_date()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 10, 'nome_setor' => 'Recuperacao', 'cod_setor' => 'REC', 'data_cad' => now()]);

        $analysis = [
            'nome' => 'Modelo IA', 'descricao' => 'Teste', 'cabecalho_html' => '', 'rodape_html' => '',
            'campos' => [
                ['rotulo' => 'DATA DO CONTRATO', 'token' => '@campo1@', 'tipo' => 'TEXT', 'obrigatorio' => true, 'origem_coluna' => '', 'lista_grupo' => '', 'lista_retorno' => '', 'comportamento' => 'date', 'prefixo' => '', 'sufixo' => '', 'opcoes' => [], 'ordem' => 1],
                ['rotulo' => 'VALOR', 'token' => '@campo2@', 'tipo' => 'TEXT', 'obrigatorio' => true, 'origem_coluna' => '', 'lista_grupo' => '', 'lista_retorno' => '', 'comportamento' => 'decimal', 'prefixo' => 'R$ ', 'sufixo' => '', 'opcoes' => [], 'ordem' => 2],
            ],
            'paragrafos' => [['titulo' => 'CORPO', 'conteudo_html' => '<p>@campo1@ @campo2@</p>', 'ordem' => 1]],
        ];

        $this->actingAs($admin)
            ->withSession(['ai_model_analysis' => $analysis])
            ->post('/admin/modelos-normalizados/criar-com-ia', ['id_setor' => 10, 'tipo_arq' => 'pdf'])
            ->assertRedirect();

        $data = PeticaoModeloCampo::where('rotulo', 'DATA DO CONTRATO')->firstOrFail();
        $valor = PeticaoModeloCampo::where('rotulo', 'VALOR')->firstOrFail();

        $this->assertSame('date', $data->comportamento);
        $this->assertStringNotContainsString('data_atual', json_encode($data->eventos_frontend));
        $this->assertSame('fc_newstring(this);', $valor->eventos_frontend['blur']);
    }
}
