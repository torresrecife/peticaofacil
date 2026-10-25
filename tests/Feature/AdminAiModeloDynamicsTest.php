<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\Services\PeticaoComposerService;
use App\Services\PeticaoModeloRuntimeFactory;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Importacao com IA: as dinamicas propostas viram campos que a montagem usa.
 */
class AdminAiModeloDynamicsTest extends TestCase
{
    public function test_ai_analysis_creates_fields_with_dynamics_and_the_petition_comes_out_right()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 10, 'nome_setor' => 'Recuperacao', 'cod_setor' => 'REC', 'data_cad' => now()]);

        $none = ['tipo' => '', 'alvos' => [], 'colunas' => [], 'mostrar_alvos' => false, 'valor' => '', 'obrigatorio' => false, 'por_item' => 1, 'modo' => '', 'pessoa' => null, 'documento' => null];
        $field = function ($n, $rotulo, $tipo, array $extra = []) use ($none) {
            return array_merge(['rotulo' => $rotulo, 'token' => '@campo' . $n . '@', 'tipo' => $tipo, 'obrigatorio' => false, 'origem_coluna' => '', 'lista_grupo' => '', 'lista_retorno' => '', 'comportamento' => '', 'prefixo' => '', 'sufixo' => '', 'opcoes' => [], 'marcador' => 'COMO_DIGITADO', 'extenso' => '', 'dinamica' => $none, 'ordem' => $n], $extra);
        };
        $analysis = [
            'nome' => 'Modelo IA Dinamico', 'descricao' => 'Teste', 'cabecalho_html' => '', 'rodape_html' => '',
            'campos' => [
                $field(1, 'RÉU', 'TEXT', ['marcador' => 'MAIUSCULAS']),
                $field(2, 'PESSOA', 'SELECT', ['opcoes' => [['rotulo' => 'FÍSICA', 'retornos' => ['']], ['rotulo' => 'JURÍDICA', 'retornos' => ['']]]]),
                $field(3, 'CPF/CNPJ', 'TEXT', ['comportamento' => 'cpf_cnpj']),
                $field(4, 'QUALIFICAÇÃO', 'TEXT', ['dinamica' => array_merge($none, ['tipo' => 'qualificacao', 'pessoa' => '@campo2@', 'documento' => '@campo3@'])]),
                $field(5, 'SEGREDO DE JUSTIÇA', 'SELECT', ['opcoes' => [
                    ['rotulo' => 'SIM', 'retornos' => ['<p>Requer o segredo de justiça em favor de @CAMPO1@.</p>']],
                    ['rotulo' => 'NÃO', 'retornos' => ['']],
                ]]),
                $field(6, 'QUANTIDADE DE PARCELAS', 'SELECT', [
                    'opcoes' => [['rotulo' => '01', 'retornos' => ['01']], ['rotulo' => '02', 'retornos' => ['02']]],
                    'dinamica' => array_merge($none, ['tipo' => 'quantidade', 'alvos' => ['@campo7@', '@campo8@'], 'por_item' => 2]),
                ]),
                $field(7, 'VENCIMENTO 1', 'TEXT', ['comportamento' => 'date', 'dinamica' => array_merge($none, ['tipo' => 'proxima_parcela', 'alvos' => ['@campo8@'], 'modo' => 'data'])]),
                $field(8, 'VALOR 1', 'TEXT', ['comportamento' => 'decimal', 'extenso' => 'valor_por_extenso']),
                $field(9, 'DATA DA PETIÇÃO', 'TEXT', ['extenso' => 'data_atual_por_extenso']),
            ],
            'paragrafos' => [['titulo' => 'CORPO', 'conteudo_html' => '<p>Contra @CAMPO1@, @campo4@.</p>@campo5@<p>@Campo9@</p>', 'ordem' => 1]],
        ];

        $this->actingAs($admin)
            ->withSession(['ai_model_analysis' => $analysis])
            ->post('/admin/modelos-normalizados/criar-com-ia', ['id_setor' => 10, 'tipo_arq' => 'pdf'])
            ->assertRedirect();

        $modelo = PeticaoModelo::where('nome', 'Modelo IA Dinamico')->firstOrFail();
        $id = function ($rotulo) use ($modelo) {
            return (int) PeticaoModeloCampo::where('modelo_id', $modelo->id)->where('rotulo', $rotulo)->firstOrFail()->id_input;
        };
        $campo = function ($rotulo) use ($modelo) {
            return PeticaoModeloCampo::where('modelo_id', $modelo->id)->where('rotulo', $rotulo)->firstOrFail();
        };

        // Marcadores com a forma da analise e os ids do banco.
        $texto = $modelo->paragrafos()->first()->conteudo_html;
        $this->assertSame('<p>Contra @CAMPO' . $id('RÉU') . '@, @campo' . $id('QUALIFICAÇÃO') . '@.</p>@campo' . $id('SEGREDO DE JUSTIÇA') . '@<p>@Campo' . $id('DATA DA PETIÇÃO') . '@</p>', $texto);

        // Dinamicas gravadas como scripts do motor.
        $this->assertSame(sprintf('merge_fields("pessoa |%d|%d","%d");', $id('PESSOA'), $id('CPF/CNPJ'), $id('QUALIFICAÇÃO')), $campo('QUALIFICAÇÃO')->eventos_frontend['focus']);
        $this->assertSame(sprintf('nparcelas("campo%d_|_campo%d_|_campo%d");', $id('QUANTIDADE DE PARCELAS'), $id('VENCIMENTO 1'), $id('VALOR 1')), $campo('QUANTIDADE DE PARCELAS')->eventos_frontend['focus']);
        $this->assertSame(sprintf('addDataCampo(%d,%d,1);', $id('VENCIMENTO 1'), $id('VALOR 1')), $campo('VENCIMENTO 1')->eventos_frontend['blur']);
        $this->assertSame('fc_newstring(this);', $campo('VALOR 1')->eventos_frontend['blur']);
        $this->assertSame('data_atual(this); data_extenso_out(this);', $campo('DATA DA PETIÇÃO')->eventos_frontend['load']);
        $this->assertFalse((bool) $campo('VENCIMENTO 1')->visivel);
        $this->assertTrue((bool) $campo('RÉU')->visivel);

        // A peca: trecho opcional pelo retorno da opcao, com o marcador do reu dentro.
        $runtime = app(PeticaoModeloRuntimeFactory::class)->fromNormalized($modelo->fresh());
        $html = app(PeticaoComposerService::class)->compose($runtime, [
            'campo_' . $id('RÉU') => 'José da Silva',
            'campo_' . $id('QUALIFICAÇÃO') => 'pessoa física, inscrita no CPF sob o nº 123.456.789-01',
            'campo_' . $id('SEGREDO DE JUSTIÇA') => 'SIM',
            'campo_' . $id('DATA DA PETIÇÃO') => '24 DE OUTUBRO DE 2026',
        ])['html'];

        $this->assertStringContainsString('<p>Contra JOSÉ DA SILVA, pessoa física, inscrita no CPF sob o nº 123.456.789-01.</p>', $html);
        $this->assertStringContainsString('<p>Requer o segredo de justiça em favor de JOSÉ DA SILVA.</p>', $html);
        $this->assertStringContainsString('<p>24 de Outubro de 2026</p>', $html);
    }
}
