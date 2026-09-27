<?php

namespace App\Services;

use RuntimeException;

class PeticaoModeloAiBuilderService
{
    protected $wordImport;
    protected $client;

    public function __construct(WordImportService $wordImport, OpenAIResponsesClient $client)
    {
        $this->wordImport = $wordImport;
        $this->client = $client;
    }

    public function analyze($file)
    {
        $html = $this->wordImport->importUploadedFile($file);
        $sourceHtml = $html;
        $html = $this->prepareAiInput($html);
        $neoGuide = $this->neoGuide();
        $result = $this->client->createStructuredResponse([
            ['role' => 'system', 'content' => 'Voce analisa modelos juridicos brasileiros. Preserve o HTML original do corpo, incluindo tabelas, estilos inline, negrito, fonte, tamanho, alinhamento, bordas, larguras, celulas mescladas e recuos. Alem dos paragrafos estruturados, retorne corpo_html com uma copia fiel do HTML do corpo recebido, mantendo a hierarquia de table, tr, td, p e todos os estilos; altere somente os trechos variaveis pelos tokens identificados. Nunca transforme uma tabela em texto corrido nem duplique seu conteudo em paragrafos. Ignore completamente cabecalhos e rodapes nativos do arquivo Word: nao copie conteudo deles para cabecalho_html ou rodape_html e nunca coloque tokens nesses campos. Todo enderecamento juridico deve permanecer como primeiro paragrafo do corpo. Retorne cabecalho_html e rodape_html vazios; o sistema aplicara o cabecalho institucional padrao separadamente. Nao crie campos para dados institucionais fixos do escritorio, incluindo endereco profissional do advogado, telefone profissional, site, e-mail institucional, logotipo, nome do escritorio ou dados de contato presentes no cabecalho/rodape. Esses dados devem ser preservados apenas como texto fixo ou ignorados quando vierem do cabecalho/rodape. Identifique no corpo os trechos que devem receber dados externos e crie um campo para cada trecho variavel. Para cada campo, preencha origem_coluna usando exclusivamente um alias do dicionario NEO abaixo quando houver correspondencia semantica. Nunca invente aliases. Se nao houver correspondencia segura, deixe origem_coluna vazia para revisao manual. Nao associe automaticamente autor e reu apenas pela posicao: considere o sentido da acao, o papel processual e o contexto do trecho. Quando o texto representar uma entidade reutilizavel cadastrada, use lista_grupo e lista_retorno: CLIENTES (id 2, nome_lista como rotulo e return_1 como qualificacao/endereco do cliente), LOCALIZADORES (id 1, return_1 a return_6 para dados do localizador), OAB POR ESTADO (id 3, return_1 para a inscricao), TIPO DE ACORDO (id 4), DIA DA SEMANA (id 5) e DADOS BANCARIOS (id 6). Para autor em acao ativa, prefira CLIENTES; crie o campo principal como SELECT e indique lista_retorno=return_1 quando o texto exigir a qualificacao/endereco cadastrado. Nao use lista para reu, salvo se houver evidencia de que o reu pertence a um cadastro reutilizavel. Use tokens temporarios como @VAR_NOME@. Dicionario NEO: ' . $neoGuide],
            ['role' => 'user', 'content' => "Analise o documento Word abaixo e estruture um modelo de peticao.\n\n" . $html],
        ], $this->schema(), 'peticao_modelo_analysis');

        if (!$result['ok']) {
            throw new RuntimeException($result['error'] ?: 'Nao foi possivel analisar o modelo com IA.');
        }

        $result['data']['_source_html'] = $sourceHtml;
        return $this->normalize($result['data']);
    }

    protected function prepareAiInput($html)
    {
        $html = preg_replace('/<(script|style|meta|link)\b[^>]*>.*?<\/\1>/is', '', (string) $html);
        $html = preg_replace('/\s+/u', ' ', $html);
        // Keep the request bounded; very large Word exports contain duplicated CSS
        // and layout metadata that do not help identify legal fields.
        $limit = 180000;
        if (function_exists('mb_strlen') && mb_strlen($html, 'UTF-8') > $limit) {
            $html = mb_substr($html, 0, $limit, 'UTF-8') . '\n[Documento truncado para analise; revise o resultado.]';
        } elseif (strlen($html) > $limit) {
            $html = substr($html, 0, $limit) . '\n[Documento truncado para analise; revise o resultado.]';
        }

        return $html;
    }

    public function schema()
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['nome', 'descricao', 'corpo_html', 'cabecalho_html', 'rodape_html', 'campos', 'paragrafos'],
            'properties' => [
                'nome' => ['type' => 'string'], 'descricao' => ['type' => 'string'], 'corpo_html' => ['type' => 'string'],
                'cabecalho_html' => ['type' => 'string'], 'rodape_html' => ['type' => 'string'],
                'campos' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false,
                    'required' => ['rotulo', 'token', 'tipo', 'obrigatorio', 'texto_original', 'origem_coluna', 'lista_grupo', 'lista_retorno', 'comportamento', 'prefixo', 'sufixo', 'opcoes'],
                    'properties' => [
                        'rotulo' => ['type' => 'string'], 'token' => ['type' => 'string'], 'texto_original' => ['type' => 'string', 'description' => 'Trecho exato do documento que sera substituido pelo token, preservando acentos e pontuacao.'],
                        'tipo' => ['type' => 'string', 'enum' => ['TEXT', 'TEXTAREA', 'SELECT']],
                        'obrigatorio' => ['type' => 'boolean'],
                        'origem_coluna' => ['type' => 'string', 'description' => 'Alias exato de uma coluna do dicionario NEO. Use vazio quando nao houver correspondencia segura.'],
                        'lista_grupo' => ['type' => 'string', 'description' => 'Nome exato de um grupo de lista pre-definida ou vazio.'],
                        'lista_retorno' => ['type' => 'string', 'enum' => ['', 'return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6']],
                        'comportamento' => ['type' => 'string', 'enum' => ['', 'date', 'decimal', 'cpf', 'cnpj', 'cpf_cnpj', 'fone', 'cep', 'integer', 'processo']],
                        'prefixo' => ['type' => 'string'], 'sufixo' => ['type' => 'string'],
                        'opcoes' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ]],
                'paragrafos' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false,
                    'required' => ['titulo', 'conteudo_html'],
                    'properties' => ['titulo' => ['type' => 'string'], 'conteudo_html' => ['type' => 'string']],
                ]],
            ],
        ];
    }

    protected function neoGuide()
    {
        return 'codigo_neo: identificador unico do processo (CodigoProcesso); numero_integracao: numero de integracao; reu: parte adversa; autor: cliente normalmente representado pelo escritorio, como banco autor; advo_autor: advogado do autor ou, conforme o tipo de acao, advogado da parte contraria; reu_cpfcnpj: documento da parte adversa; autor_cpfcnpj: documento do autor; logradouro, bairro, cidade, estado, cep: endereco da parte contraria em acoes ativas e do autor em acoes passivas; marca, modelo, ano, placa, chassi, renavam, cor: dados do bem, normalmente veiculo; numero_processo: numero judicial do processo; comarca: comarca de tramitacao; uf_extenso: estado da comarca; dataevento: data do evento; vara: vara; mfase: fase processual; datacad: data de cadastro no NEO; tipo_acao: tipo da acao; veiculo: descricao completa do bem.';
    }

    protected function normalize(array $data)
    {
        $tokens = [];
        $sourceHtml = (string) ($data['_source_html'] ?? '');
        $fields = [];
        foreach (array_values($data['campos'] ?? []) as $index => $field) {
            if ($this->isInstitutionalContactField($field)) {
                $source = trim((string) ($field['token'] ?? ''));
                if ($source !== '') {
                    $tokens[$source] = '';
                }
                continue;
            }
            $token = '@campo' . (count($fields) + 1) . '@';
            $source = trim((string) ($field['token'] ?? ''));
            if ($source !== '') {
                $tokens[$source] = $token;
            }
            $fields[] = [
                'rotulo' => trim($field['rotulo'] ?: 'Campo ' . ($index + 1)),
                'token' => $token, 'tipo' => $field['tipo'],
                'obrigatorio' => (bool) $field['obrigatorio'],
                'origem_coluna' => trim((string) ($field['origem_coluna'] ?? '')),
                'lista_grupo' => trim((string) ($field['lista_grupo'] ?? '')),
                'lista_retorno' => trim((string) ($field['lista_retorno'] ?? '')),
                'comportamento' => (string) ($field['comportamento'] ?? ''),
                'prefixo' => (string) ($field['prefixo'] ?? ''), 'sufixo' => (string) ($field['sufixo'] ?? ''),
                'opcoes' => array_values(array_filter(array_map('trim', $field['opcoes'] ?? []))),
                'ordem' => count($fields) + 1,
            ];
            $original = trim((string) ($field['texto_original'] ?? ''));
            if ($original !== '' && $sourceHtml !== '') {
                $sourceHtml = str_ireplace($original, $token, $sourceHtml);
            }
        }
        $replace = function ($value) use ($tokens) {
            return $tokens ? str_ireplace(array_keys($tokens), array_values($tokens), (string) $value) : (string) $value;
        };
        $paragraphs = [];
        $bodyHtml = $sourceHtml !== '' && stripos($sourceHtml, '<table') !== false
            ? $sourceHtml
            : trim((string) ($data['corpo_html'] ?? ''));
        if ($bodyHtml !== '') {
            $paragraphs[] = ['titulo' => 'CORPO DA PETICAO', 'conteudo_html' => $replace($bodyHtml), 'ordem' => 1];
        }
        if ($bodyHtml !== '') {
            return ['nome' => trim($data['nome']), 'descricao' => trim($data['descricao']), 'cabecalho_html' => '', 'rodape_html' => '', 'campos' => $fields, 'paragrafos' => $paragraphs];
        }
        foreach (array_values($data['paragrafos'] ?? []) as $index => $paragraph) {
            $paragraphs[] = ['titulo' => trim($paragraph['titulo'] ?: 'Paragrafo ' . ($index + 1)), 'conteudo_html' => $replace($paragraph['conteudo_html']), 'ordem' => $index + 1];
        }
        return ['nome' => trim($data['nome']), 'descricao' => trim($data['descricao']), 'cabecalho_html' => '', 'rodape_html' => '', 'campos' => $fields, 'paragrafos' => $paragraphs];
    }

    protected function isInstitutionalContactField(array $field)
    {
        $label = strtolower(trim((string) ($field['rotulo'] ?? '')));
        $label = strtr($label, ['á' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'ú' => 'u', 'ç' => 'c']);
        foreach (['endereco profissional', 'telefone profissional', 'site profissional', 'e-mail profissional', 'email profissional', 'endereco do escritorio', 'telefone do escritorio', 'site do escritorio', 'email do escritorio', 'contato do escritorio', 'nome do advogado', 'advogado responsavel', 'oab do advogado', 'numero da oab', 'inscricao na oab'] as $marker) {
            if (strpos($label, $marker) !== false) {
                return true;
            }
        }
        return false;
    }
}
