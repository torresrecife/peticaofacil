<?php

namespace Tests\Feature;

use App\Configuracao;
use App\PeticaoModelo;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminModeloCabecalhoPadraoTest extends TestCase
{
    public function test_migration_starts_with_the_office_header()
    {
        $this->assertStringContainsString('cabecalho.jpg', Configuracao::cabecalhoModelo());
        $this->assertStringContainsString('E-mail: contato@bvaa.adv.br', Configuracao::cabecalhoModelo());
        $this->assertNull(Configuracao::rodapeModelo());
    }

    public function test_saved_header_and_footer_go_into_every_new_model()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 10, 'nome_setor' => 'Recuperacao', 'cod_setor' => 'REC', 'data_cad' => now()]);

        $this->actingAs($admin)->get('/admin/modelos-normalizados/cabecalho-padrao')
            ->assertStatus(200)
            ->assertSee('Cabeçalho padrão', false);
        $this->actingAs($admin)->put('/admin/modelos-normalizados/cabecalho-padrao', [
            'cod_cabec' => '<p>CABECALHO NOVO</p>',
            'cod_rodap' => '<p>RODAPE NOVO</p>',
        ])->assertRedirect('/admin/modelos-normalizados/cabecalho-padrao');

        // "Novo modelo" ja abre com eles.
        $this->actingAs($admin)->get('/admin/modelos-normalizados/create')
            ->assertStatus(200)
            ->assertSee('CABECALHO NOVO', false)
            ->assertSee('RODAPE NOVO', false);

        // Importacao do Word com IA: o Word nao traz cabecalho, entra o padrao.
        $analysis = [
            'nome' => 'Modelo IA', 'descricao' => 'Teste', 'cabecalho_html' => '', 'rodape_html' => '',
            'campos' => [],
            'paragrafos' => [['titulo' => 'CORPO', 'conteudo_html' => '<p>Texto</p>', 'ordem' => 1]],
        ];
        $this->actingAs($admin)
            ->withSession(['ai_model_analysis' => $analysis])
            ->post('/admin/modelos-normalizados/criar-com-ia', ['id_setor' => 10, 'tipo_arq' => 'pdf'])
            ->assertRedirect();

        $modelo = PeticaoModelo::where('nome', 'Modelo IA')->firstOrFail();
        $this->assertSame('<p>CABECALHO NOVO</p>', $modelo->cabecalho_html);
        $this->assertSame('<p>RODAPE NOVO</p>', $modelo->rodape_html);
    }
}
