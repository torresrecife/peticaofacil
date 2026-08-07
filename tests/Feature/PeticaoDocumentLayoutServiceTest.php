<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\PeticaoVersao;
use App\Services\PeticaoDocumentLayoutService;
use Tests\TestCase;

class PeticaoDocumentLayoutServiceTest extends TestCase
{
    public function test_it_builds_layout_contract_for_saved_peticao()
    {
        $modelo = new PeticaoModelo([
            'id' => 705,
            'nome' => 'SUBSTABELECIMENTO',
            'cabecalho_html' => '<p>Cabecalho</p>',
            'rodape_html' => '<p>Rodape</p>',
        ]);

        $peticao = new PeticaoNormalizada([
            'id' => 34832,
            'modelo_id' => 705,
            'codigo_externo' => '32216005',
            'cliente_referencia' => 'Cliente Teste',
            'conteudo_html' => '<p>Corpo</p>',
        ]);
        $peticao->setRelation('modelo', $modelo);

        $layout = app(PeticaoDocumentLayoutService::class)->fromSavedPeticao($peticao);

        $this->assertSame('Cliente Teste', $layout['title']);
        $this->assertSame('<p>Corpo</p>', $layout['body_html']);
        $this->assertSame('<p>Cabecalho</p>', $layout['header_html']);
        $this->assertSame('<p>Rodape</p>', $layout['footer_html']);
        $this->assertSame('SUBSTABELECIMENTO', $layout['meta']['modelo']);
        $this->assertSame('32216005', $layout['meta']['codigo']);
    }

    public function test_it_builds_layout_contract_for_version_snapshot()
    {
        $modelo = new PeticaoModelo([
            'id' => 705,
            'nome' => 'SUBSTABELECIMENTO',
            'cabecalho_html' => '<p>Cabecalho</p>',
            'rodape_html' => '<p>Rodape</p>',
        ]);

        $peticao = new PeticaoNormalizada([
            'id' => 34832,
            'modelo_id' => 705,
        ]);
        $peticao->setRelation('modelo', $modelo);

        $versao = new PeticaoVersao([
            'id' => 99,
            'peticao_id' => 34832,
            'versao_numero' => 2,
            'codigo_externo_snapshot' => '32216005',
            'cliente_referencia_snapshot' => 'Cliente Versao',
            'conteudo_html_snapshot' => '<p>Corpo v2</p>',
        ]);

        $layout = app(PeticaoDocumentLayoutService::class)->fromVersion($peticao, $versao);

        $this->assertSame('Cliente Versao', $layout['title']);
        $this->assertSame('<p>Corpo v2</p>', $layout['body_html']);
        $this->assertSame('<p>Cabecalho</p>', $layout['header_html']);
        $this->assertSame('<p>Rodape</p>', $layout['footer_html']);
        $this->assertSame(2, $layout['meta']['versao_numero']);
    }
}
