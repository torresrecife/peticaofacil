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
