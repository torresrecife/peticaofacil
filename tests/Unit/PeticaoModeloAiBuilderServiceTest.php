<?php

namespace Tests\Unit;

use App\Services\OpenAIResponsesClient;
use App\Services\PeticaoModeloAiBuilderService;
use App\Services\WordImportService;
use PHPUnit\Framework\TestCase;

class PeticaoModeloAiBuilderServiceTest extends TestCase
{
    public function test_analysis_is_normalized_to_runtime_tokens_and_ordered_fields()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function normalizeForTest(array $data) { return $this->normalize($data); }
        };
        $result = $service->normalizeForTest([
            'nome' => 'Cobranca', 'descricao' => 'Acao', 'cabecalho_html' => '', 'rodape_html' => '',
            'campos' => [
                ['rotulo' => 'Autor', 'token' => '@VAR_AUTOR@', 'tipo' => 'TEXT', 'obrigatorio' => true, 'opcoes' => []],
                ['rotulo' => 'Tipo', 'token' => '@VAR_TIPO@', 'tipo' => 'SELECT', 'obrigatorio' => false, 'opcoes' => ['A']],
                ['rotulo' => 'Telefone profissional do advogado', 'token' => '@VAR_FONE@', 'tipo' => 'TEXT', 'obrigatorio' => false, 'opcoes' => []],
            ],
            'paragrafos' => [['titulo' => 'Fatos', 'conteudo_html' => '<p>@VAR_AUTOR@ escolheu @VAR_TIPO@.</p>']],
        ]);
        $this->assertSame('@campo1@', $result['campos'][0]['token']);
        $this->assertSame('@campo2@', $result['campos'][1]['token']);
        $this->assertStringContainsString('@campo1@', $result['paragrafos'][0]['conteudo_html']);
        $this->assertStringContainsString('@campo2@', $result['paragrafos'][0]['conteudo_html']);
        $this->assertCount(2, $result['campos']);
    }

    public function test_word_html_replacement_respects_words_order_and_longest_excerpt()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function replaceForTest($html, array $originals) { return $this->replaceOriginals($html, $originals); }
        };
        $html = '<p class="PE">DA COMARCA DE RECIFE - PE</p><p>contra JOÃO, pessoa física, inscrita no CPF sob o nº 123.456.789-01, residente em Recife - PE, vem</p>'
            . '<table><tr><td>R$ 1.500,00</td></tr><tr><td>R$ 1.500,00</td></tr><tr><td>R$ 1.500,00</td></tr></table><p>Nestes termos, pede deferimento. Recife, 24 de outubro.</p>';

        $result = $service->replaceForTest($html, [
            ['text' => 'RECIFE', 'token' => '@CAMPO2@', 'order' => 2],
            ['text' => 'PE', 'token' => '@CAMPO3@', 'order' => 3],
            ['text' => '123.456.789-01', 'token' => '@campo9@', 'order' => 9],
            ['text' => 'pessoa física, inscrita no CPF sob o nº 123.456.789-01', 'token' => '@campo10@', 'order' => 10],
            ['text' => 'Recife', 'token' => '@Campo13@', 'order' => 13],
            ['text' => 'PE', 'token' => '@CAMPO14@', 'order' => 14],
            ['text' => '1.500,00', 'token' => '@campo17@', 'order' => 17],
            ['text' => '1.500,00', 'token' => '@campo19@', 'order' => 19],
            ['text' => '1.500,00', 'token' => '@campo21@', 'order' => 21],
            ['text' => 'Recife', 'token' => '@Campo24@', 'order' => 24],
        ]);

        $this->assertSame(
            '<p class="PE">DA COMARCA DE @CAMPO2@ - @CAMPO3@</p><p>contra JOÃO, @campo10@, residente em @Campo13@ - @CAMPO14@, vem</p>'
            . '<table><tr><td>R$ @campo17@</td></tr><tr><td>R$ @campo19@</td></tr><tr><td>R$ @campo21@</td></tr></table><p>Nestes termos, pede deferimento. @Campo24@, 24 de outubro.</p>',
            $result
        );
    }

    public function test_word_html_replacement_crosses_bold_and_keeps_tags_closed()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function replaceForTest($html, array $originals) { return $this->replaceOriginals($html, $originals); }
        };
        $html = '<p><span style="font-size: 10pt"><b>BANCO X S/A (BX)</b>, pessoa jurídica, CEP 04344-020, por seu advogado</span></p>';

        $result = $service->replaceForTest($html, [
            ['text' => 'BANCO X S/A (BX), pessoa jurídica, CEP 04344-020', 'token' => '@campo3@', 'order' => 3],
            ['text' => 'BANCO X S/A', 'token' => '@CAMPO4@', 'order' => 4],
        ]);

        $this->assertSame('<p><span style="font-size: 10pt"><b>@campo3@</b>, por seu advogado</span></p>', $result);
    }

    public function test_footnotes_are_never_replaced_by_fields()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function replaceForTest($html, array $originals) { return $this->replaceOriginals($html, $originals); }
        };
        $html = '<p>notificado (doc. 03) para regularizar</p>'
            . '<div class="peticao-notas"><hr><p><sup>1</sup> <a href="https://x.com/2022/03/25/a">https://x.com/2022/03/25/a</a></p></div>';

        $result = $service->replaceForTest($html, [['text' => '03', 'token' => '@campo1@', 'order' => 1]]);

        $this->assertStringContainsString('(doc. @campo1@)', $result);
        $this->assertStringContainsString('https://x.com/2022/03/25/a</a>', $result);
    }

    public function test_repeated_mention_across_formatting_tiny_sample_font_and_empty_tags()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function replaceForTest($html, array $originals) { return $this->replaceOriginals($html, $originals); }
        };
        $html = '<p>OAB/RJ 228627-S</p><p>Dr. FULANO, OAB/</span></b><i><b>RJ </b></i><b>228627-S</b> independente</p>'
            . '<p>contrato n. <span style="font-size: 7pt"><b>55496080</b></span>, valor</p>';

        $result = $service->replaceForTest($html, [
            ['text' => 'OAB/RJ 228627-S', 'token' => '@campo1@', 'order' => 1],
            ['text' => '55496080', 'token' => '@campo2@', 'order' => 2],
        ]);

        // As duas mencoes da OAB viram o campo (a 2a atravessa negrito/italico).
        $this->assertSame(2, substr_count($result, '@campo1@'));
        $this->assertStringNotContainsString('228627', $result);
        // O exemplo em 7pt nao deixa o campo miudo; tags vazias saem.
        $this->assertStringContainsString('contrato n. <b>@campo2@</b>, valor', $result);

        // Com texto em 10pt antes, o campo fica em 10pt (e nao na letra maior do paragrafo).
        $sized = $service->replaceForTest(
            '<p style="font-size: 12pt"><span style="font-size: 10pt">contrato n. </span><span style="font-size: 7pt"><b>55496080</b></span><span style="font-size: 10pt">, oriundo</span></p>',
            [['text' => '55496080', 'token' => '@campo2@', 'order' => 1]]
        );
        $this->assertStringContainsString('<span style="font-size: 10pt"><b>@campo2@</b></span>', $sized);
        $this->assertStringNotContainsString('<b></b>', $result);
        $this->assertStringNotContainsString('<i></i>', $result);
    }

    public function test_blank_cells_and_lines_become_fields_or_return_to_blank()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function markForTest($html) { return $this->markBlankSlots($html); }
            public function normalizeForTest(array $data) { return $this->normalize($data); }
        };
        $html = '<table><tr><td><p><b>PLACA:</b></p></td><td><p></p></td><td><p><b>COR:</b></p></td><td><p> </p></td></tr>'
            . '<tr><td><p>OBS</p></td><td><p></p></td></tr></table><p>Assinatura: ______</p>';

        $marked = str_replace(["\r", "\n"], '', $service->markForTest($html)); // saveHTML quebra linha entre blocos
        $this->assertStringContainsString('<td><p>[[EM_BRANCO_1]]</p></td>', $marked);
        $this->assertStringContainsString('<td><p>[[EM_BRANCO_2]]</p></td>', $marked);
        $this->assertStringContainsString('<td><p>OBS</p></td><td><p></p></td>', $marked); // sem ":" no rotulo
        $this->assertStringContainsString('Assinatura: [[EM_BRANCO_3]]', $marked);

        $result = $service->normalizeForTest([
            'nome' => 'Inicial', 'descricao' => '', 'cabecalho_html' => '', 'rodape_html' => '', 'corpo_html' => '', 'paragrafos' => [],
            'campos' => [['rotulo' => 'Placa', 'token' => '@VAR_PLACA@', 'tipo' => 'TEXT', 'obrigatorio' => true, 'opcoes' => [], 'texto_original' => '[[EM_BRANCO_1]]', 'marcador' => 'MAIUSCULAS']],
            '_source_html' => $marked,
        ]);
        $body = $result['paragrafos'][0]['conteudo_html'];
        $this->assertStringContainsString('<p><b>PLACA:</b></p></td><td><p>@CAMPO1@</p>', $body);
        $this->assertStringContainsString('<p><b>COR:</b></p></td><td><p></p>', $body);
        $this->assertStringContainsString('Assinatura: ______', $body);
        $this->assertStringNotContainsString('EM_BRANCO', $body);
    }

    public function test_body_is_the_word_html_even_without_tables()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function normalizeForTest(array $data) { return $this->normalize($data); }
        };
        $result = $service->normalizeForTest([
            'nome' => 'Inicial', 'descricao' => '', 'cabecalho_html' => '', 'rodape_html' => '', 'corpo_html' => '<p>copia da IA</p>', 'paragrafos' => [],
            'campos' => [['rotulo' => 'Reu', 'token' => '@VAR_REU@', 'tipo' => 'TEXT', 'obrigatorio' => true, 'opcoes' => [], 'texto_original' => 'JOSE', 'marcador' => 'MAIUSCULAS']],
            '_source_html' => '<p style="text-align: center"><b>Em face de JOSE</b><sup>1</sup></p><div class="peticao-notas"><hr><p><sup>1</sup> Nota</p></div>',
        ]);

        $this->assertSame('<p style="text-align: center"><b>Em face de @CAMPO1@</b><sup>1</sup></p><div class="peticao-notas"><hr><p><sup>1</sup> Nota</p></div>', $result['paragrafos'][0]['conteudo_html']);
    }

    public function test_dynamics_marker_case_and_option_returns_are_normalized()
    {
        $service = new class(new WordImportService(), new OpenAIResponsesClient()) extends PeticaoModeloAiBuilderService {
            public function normalizeForTest(array $data) { return $this->normalize($data); }
        };
        $none = ['tipo' => '', 'alvos' => [], 'colunas' => [], 'mostrar_alvos' => false, 'valor' => '', 'obrigatorio' => false, 'por_item' => 1, 'modo' => '', 'pessoa' => '', 'documento' => ''];
        $field = function ($rotulo, $token, $tipo, array $extra = []) use ($none) {
            return array_merge(['rotulo' => $rotulo, 'token' => $token, 'tipo' => $tipo, 'obrigatorio' => true, 'opcoes' => [], 'marcador' => 'COMO_DIGITADO', 'extenso' => '', 'dinamica' => $none], $extra);
        };

        $result = $service->normalizeForTest([
            'nome' => 'Inicial', 'descricao' => '', 'cabecalho_html' => '', 'rodape_html' => '',
            'campos' => [
                $field('REU', '@VAR_REU@', 'TEXT', ['marcador' => 'MAIUSCULAS']),
                $field('PESSOA', '@VAR_PESSOA@', 'SELECT', ['opcoes' => [['rotulo' => 'FISICA', 'retornos' => []], ['rotulo' => 'JURIDICA', 'retornos' => []]]]),
                $field('CPF/CNPJ', '@VAR_DOC@', 'TEXT'),
                $field('QUALIFICACAO', '@VAR_QUAL@', 'TEXT', ['dinamica' => array_merge($none, ['tipo' => 'qualificacao', 'pessoa' => '@VAR_PESSOA@', 'documento' => '@VAR_DOC@'])]),
                $field('SEGREDO DE JUSTICA', '@VAR_SEGREDO@', 'SELECT', ['opcoes' => [
                    ['rotulo' => 'SIM', 'retornos' => ['<p>Requer segredo de justica em favor de @VAR_REU@.</p>']],
                    ['rotulo' => 'NAO', 'retornos' => ['']],
                ]]),
                $field('QUANTIDADE', '@VAR_QTD@', 'SELECT', ['dinamica' => array_merge($none, ['tipo' => 'quantidade', 'alvos' => ['@VAR_V1@', '@VAR_INEXISTENTE@'], 'por_item' => 2])]),
                $field('VENCIMENTO 1', '@VAR_V1@', 'TEXT', ['comportamento' => 'date', 'extenso' => 'data_por_extenso']),
                $field('ANTIGO', '@VAR_ANTIGO@', 'SELECT', ['opcoes' => ['A', 'B']]),
            ],
            'corpo_html' => '<p>Contra @VAR_REU@, @VAR_QUAL@.</p>@VAR_SEGREDO@',
            'paragrafos' => [],
        ]);

        $campos = $result['campos'];
        $this->assertSame('<p>Contra @CAMPO1@, @campo4@.</p>@campo5@', $result['paragrafos'][0]['conteudo_html']);
        $this->assertSame('@campo1@', $campos[0]['token']);
        $this->assertSame(['tipo' => 'qualificacao', 'pessoa' => '@campo2@', 'documento' => '@campo3@'], array_intersect_key($campos[3]['dinamica'], array_flip(['tipo', 'pessoa', 'documento'])));
        $this->assertSame('<p>Requer segredo de justica em favor de @CAMPO1@.</p>', $campos[4]['opcoes'][0]['retornos'][0]);
        // Referencia a campo inexistente e descartada.
        $this->assertSame(['@campo7@'], $campos[5]['dinamica']['alvos']);
        $this->assertSame(2, $campos[5]['dinamica']['por_item']);
        $this->assertSame('data_por_extenso', $campos[6]['extenso']);
        $this->assertSame([], $campos[6]['dinamica']);
        // Resposta no formato antigo (opcoes so texto) continua aceita.
        $this->assertSame([['rotulo' => 'A', 'retornos' => ['A']], ['rotulo' => 'B', 'retornos' => ['B']]], $campos[7]['opcoes']);
    }
}
