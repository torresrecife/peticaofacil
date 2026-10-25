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
            ['role' => 'system', 'content' => 'Voce analisa modelos juridicos brasileiros. Nao copie o corpo do documento: retorne corpo_html vazio e paragrafos como lista vazia. O sistema usa o HTML original do Word (com toda a formatacao e as notas de rodape) e troca, ele mesmo, cada texto_original pelo token do campo. Por isso texto_original deve ser o trecho exato do documento, como aparece no texto (mesmas maiusculas, acentos e pontuacao), sem tags HTML e dentro de um unico paragrafo. texto_original e so a parte que muda de uma peca para outra, nunca a frase inteira: no enderecamento "EXCELENTISSIMO(A) SENHOR(A) ... DA COMARCA DE RECIFE-PE", o campo da comarca e "RECIFE" (e a UF "PE", se for outro campo); o texto fixo em volta continua no documento. As notas de rodape do documento (lista no fim, numeradas) sao texto fixo: nao crie campos nelas. Ignore completamente cabecalhos e rodapes nativos do arquivo Word: nao copie conteudo deles para cabecalho_html ou rodape_html e nunca coloque tokens nesses campos. Todo enderecamento juridico deve permanecer como primeiro paragrafo do corpo. Retorne cabecalho_html e rodape_html vazios; o sistema aplicara o cabecalho institucional padrao separadamente. Nao crie campos para dados institucionais fixos do escritorio, incluindo endereco profissional do advogado, telefone profissional, site, e-mail institucional, logotipo, nome do escritorio ou dados de contato presentes no cabecalho/rodape. Esses dados devem ser preservados apenas como texto fixo ou ignorados quando vierem do cabecalho/rodape. Identifique no corpo os trechos que devem receber dados externos e crie um campo para cada trecho variavel. Para cada campo, preencha origem_coluna usando exclusivamente um alias do dicionario NEO abaixo quando houver correspondencia semantica. Nunca invente aliases. Se nao houver correspondencia segura, deixe origem_coluna vazia para revisao manual. Nao associe automaticamente autor e reu apenas pela posicao: considere o sentido da acao, o papel processual e o contexto do trecho. Quando o texto representar uma entidade reutilizavel cadastrada, use lista_grupo e lista_retorno: CLIENTES (id 2, nome_lista como rotulo e return_1 como qualificacao/endereco do cliente), LOCALIZADORES (id 1, return_1 a return_6 para dados do localizador), OAB POR ESTADO (id 3, return_1 para a inscricao), TIPO DE ACORDO (id 4), DIA DA SEMANA (id 5) e DADOS BANCARIOS (id 6). Para autor em acao ativa, prefira CLIENTES; crie o campo principal como SELECT e indique lista_retorno=return_1 quando o texto exigir a qualificacao/endereco cadastrado. Nao use lista para reu, salvo se houver evidencia de que o reu pertence a um cadastro reutilizavel. Use tokens temporarios como @VAR_NOME@. Dicionario NEO: ' . $neoGuide . ' ' . $this->dynamicsGuide()],
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
                    'required' => ['rotulo', 'token', 'tipo', 'obrigatorio', 'texto_original', 'origem_coluna', 'lista_grupo', 'lista_retorno', 'comportamento', 'prefixo', 'sufixo', 'opcoes', 'marcador', 'extenso', 'dinamica'],
                    'properties' => [
                        'rotulo' => ['type' => 'string'], 'token' => ['type' => 'string'], 'texto_original' => ['type' => 'string', 'description' => 'Trecho exato do documento que sera substituido pelo token, preservando acentos e pontuacao.'],
                        'tipo' => ['type' => 'string', 'enum' => ['TEXT', 'TEXTAREA', 'SELECT']],
                        'obrigatorio' => ['type' => 'boolean'],
                        'origem_coluna' => ['type' => 'string', 'description' => 'Alias exato de uma coluna do dicionario NEO. Use vazio quando nao houver correspondencia segura.'],
                        'lista_grupo' => ['type' => 'string', 'description' => 'Nome exato de um grupo de lista pre-definida ou vazio.'],
                        'lista_retorno' => ['type' => 'string', 'enum' => ['', 'return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6']],
                        'comportamento' => ['type' => 'string', 'enum' => ['', 'date', 'decimal', 'cpf', 'cnpj', 'cpf_cnpj', 'fone', 'cep', 'integer', 'processo']],
                        'prefixo' => ['type' => 'string'], 'sufixo' => ['type' => 'string'],
                        'opcoes' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false,
                            'required' => ['rotulo', 'retornos'],
                            'properties' => [
                                'rotulo' => ['type' => 'string', 'description' => 'Texto da opcao que o usuario escolhe (ex.: SIM, NAO, FISICA).'],
                                'retornos' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Retorno 1, 2... da opcao. O retorno 1 e o texto que entra no lugar do token do SELECT quando a opcao e escolhida (HTML do trecho, ou vazio). Retornos seguintes alimentam outros campos pela dinamica retorno_opcao.'],
                            ],
                        ]],
                        'marcador' => ['type' => 'string', 'enum' => ['MAIUSCULAS', 'INICIAIS', 'COMO_DIGITADO'], 'description' => 'Como o trecho aparece no documento: tudo em maiusculas, Iniciais Maiusculas, ou como sera digitado.'],
                        'extenso' => ['type' => 'string', 'enum' => ['', 'data_por_extenso', 'data_atual_por_extenso', 'valor_por_extenso'], 'description' => 'Data/valor escritos por extenso no documento. data_atual_por_extenso = data da peticao (preenche sozinha com hoje).'],
                        'dinamica' => ['type' => 'object', 'additionalProperties' => false,
                            'required' => ['tipo', 'alvos', 'colunas', 'mostrar_alvos', 'valor', 'obrigatorio', 'por_item', 'modo', 'pessoa', 'documento'],
                            'properties' => [
                                'tipo' => ['type' => 'string', 'enum' => array_keys(\App\Support\CampoDinamica::TYPES)],
                                'alvos' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Tokens temporarios (@VAR_...@) dos campos afetados.'],
                                'colunas' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => \App\Support\CampoDinamica::RETURN_COLUMNS], 'description' => 'retorno_opcao: retorno que vai para cada alvo, na mesma ordem.'],
                                'mostrar_alvos' => ['type' => 'boolean'],
                                'valor' => ['type' => 'string', 'description' => 'mostrar_por_valor: valor que exibe os alvos.'],
                                'obrigatorio' => ['type' => 'boolean'],
                                'por_item' => ['type' => 'integer', 'enum' => [1, 2], 'description' => 'quantidade: campos por item (2 = parcela com vencimento e valor).'],
                                'modo' => ['type' => 'string', 'enum' => ['', 'data', 'valor', 'nao', 'excecao']],
                                'pessoa' => ['type' => 'string', 'description' => 'qualificacao: token do campo PESSOA.'],
                                'documento' => ['type' => 'string', 'description' => 'qualificacao: token do campo CPF/CNPJ.'],
                            ],
                        ],
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
        $references = [];
        $originals = [];
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
            $number = count($fields) + 1;
            $token = '@campo' . $number . '@';
            // No texto, a forma do marcador decide maiusculas/iniciais/como digitado.
            $marcador = (string) ($field['marcador'] ?? '');
            $textToken = '@' . ($marcador === 'MAIUSCULAS' ? 'CAMPO' : ($marcador === 'INICIAIS' ? 'Campo' : 'campo')) . $number . '@';
            $source = trim((string) ($field['token'] ?? ''));
            if ($source !== '') {
                $tokens[$source] = $textToken;
                $references[strtolower($source)] = $token;
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
                'opcoes' => $this->normalizeOptions($field['opcoes'] ?? []),
                'marcador' => in_array($marcador, ['MAIUSCULAS', 'INICIAIS', 'COMO_DIGITADO'], true) ? $marcador : 'COMO_DIGITADO',
                'extenso' => in_array($field['extenso'] ?? '', ['data_por_extenso', 'data_atual_por_extenso', 'valor_por_extenso'], true) ? $field['extenso'] : '',
                'dinamica' => is_array($field['dinamica'] ?? null) ? $field['dinamica'] : [],
                'ordem' => $number,
            ];
            $original = trim((string) ($field['texto_original'] ?? ''));
            if ($original !== '') {
                $originals[] = ['text' => $original, 'token' => $textToken, 'order' => $number];
            }
        }
        if ($sourceHtml !== '' && $originals) {
            $sourceHtml = $this->replaceOriginals($sourceHtml, $originals);
        }

        // As dinamicas citam os campos pelos tokens temporarios da IA: troca
        // pelos tokens finais (@campoN@), descartando o que nao for campo.
        foreach ($fields as $index => $field) {
            $fields[$index]['dinamica'] = $this->normalizeDynamic($field['dinamica'], $references, $field['token']);
        }
        $replace = function ($value) use ($tokens) {
            return $tokens ? str_ireplace(array_keys($tokens), array_values($tokens), (string) $value) : (string) $value;
        };
        // O texto de um trecho opcional (retorno da opcao) tambem pode citar campos.
        foreach ($fields as $index => $field) {
            foreach ($field['opcoes'] as $optionIndex => $option) {
                $fields[$index]['opcoes'][$optionIndex]['retornos'] = array_map($replace, $option['retornos']);
            }
        }
        $paragraphs = [];
        // O corpo e sempre o HTML do Word (formatacao e notas preservadas); a
        // copia feita pela IA so vale quando nao ha o HTML de origem.
        $bodyHtml = trim($sourceHtml) !== ''
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

    /**
     * Troca no HTML do Word cada trecho original pelo token do campo.
     *
     * - so palavra inteira (a UF "PE" nao vira token dentro de "pessoa"/"pede");
     * - fora das tags HTML;
     * - na ordem do documento: a 1a ocorrencia de "R$ 1.500,00" vai para a
     *   parcela 1, a 2a para a parcela 2...; quando dois trechos comecam no mesmo
     *   ponto, vence o mais longo (a frase da qualificacao engole o CPF dela);
     * - depois, novas mencoes de um campo ja usado tambem viram o token dele.
     * Primeiro busca com a mesma caixa do documento; sem achar, ignora a caixa.
     *
     * @param array $originals [['text' => ..., 'token' => ..., 'order' => n], ...]
     */
    protected function replaceOriginals($html, array $originals)
    {
        $tagRanges = [];
        if (preg_match_all('/<[^>]*>/', $html, $tags, PREG_OFFSET_CAPTURE)) {
            foreach ($tags[0] as $tag) {
                $tagRanges[] = [$tag[1], $tag[1] + strlen($tag[0])];
            }
        }
        $insideTag = function ($start, $end) use ($tagRanges) {
            foreach ($tagRanges as $range) {
                if ($start < $range[1] && $end > $range[0]) {
                    return true;
                }
            }
            return false;
        };

        $candidates = [];
        foreach ($originals as $index => $original) {
            $quoted = preg_quote($original['text'], '/');
            $pattern = '/(?<![\pL\pN])' . $quoted . '(?![\pL\pN])/u';
            if (!preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
                preg_match_all($pattern . 'i', $html, $matches, PREG_OFFSET_CAPTURE);
            }
            $found = false;
            foreach ($matches[0] ?? [] as $match) {
                $start = $match[1];
                $end = $start + strlen($match[0]);
                if (!$insideTag($start, $end)) {
                    $candidates[] = ['start' => $start, 'end' => $end, 'field' => $index, 'order' => $original['order'], 'tags' => ''];
                    $found = true;
                }
            }
            if ($found) {
                continue;
            }
            // Trecho que atravessa formatacao (ex.: "<b>BANCO X</b>, pessoa
            // juridica..."): aceita tags de letra no meio. O token entra no
            // lugar do texto e as tags do meio ficam depois dele, vazias, para
            // o HTML continuar fechado do mesmo jeito.
            $inlineTags = '(?:<\/?(?:b|strong|i|em|u|span|font|sup|sub|a)\b[^>]*>)*';
            $characters = preg_split('//u', $original['text'], -1, PREG_SPLIT_NO_EMPTY);
            if (count($characters) < 4 || count($characters) > 600) {
                continue;
            }
            $crossPattern = '/(?<![\pL\pN])' . implode($inlineTags, array_map(function ($character) {
                return preg_quote($character, '/');
            }, $characters)) . '(?![\pL\pN])/u';
            if (!@preg_match_all($crossPattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
                @preg_match_all($crossPattern . 'i', $html, $matches, PREG_OFFSET_CAPTURE);
            }
            foreach ($matches[0] ?? [] as $match) {
                $start = $match[1];
                $end = $start + strlen($match[0]);
                if (!$insideTag($start, $start + 1) && !$insideTag($end - 1, $end)) {
                    preg_match_all('/<[^>]*>/', $match[0], $innerTags);
                    $candidates[] = ['start' => $start, 'end' => $end, 'field' => $index, 'order' => $original['order'], 'tags' => implode('', $innerTags[0])];
                }
            }
        }
        usort($candidates, function ($a, $b) {
            if ($a['start'] !== $b['start']) return $a['start'] - $b['start'];
            if ($a['end'] !== $b['end']) return $b['end'] - $a['end'];
            return $a['order'] - $b['order'];
        });

        $chosen = [];
        $used = [];
        $overlaps = function ($candidate) use (&$chosen) {
            foreach ($chosen as $span) {
                if ($candidate['start'] < $span['end'] && $candidate['end'] > $span['start']) {
                    return true;
                }
            }
            return false;
        };
        // 1a passada: cada campo pega a sua primeira ocorrencia livre.
        foreach ($candidates as $candidate) {
            if (!isset($used[$candidate['field']]) && !$overlaps($candidate)) {
                $chosen[] = $candidate;
                $used[$candidate['field']] = true;
            }
        }
        // 2a passada: mencoes repetidas de campos ja usados.
        foreach ($candidates as $candidate) {
            if (isset($used[$candidate['field']]) && !$overlaps($candidate)) {
                $chosen[] = $candidate;
            }
        }

        usort($chosen, function ($a, $b) {
            return $b['start'] - $a['start'];
        });
        foreach ($chosen as $span) {
            $html = substr_replace($html, $originals[$span['field']]['token'] . $span['tags'], $span['start'], $span['end'] - $span['start']);
        }

        return $html;
    }

    /**
     * Opcoes como [['rotulo' => ..., 'retornos' => [...]]]. Aceita tambem a
     * resposta antiga, com as opcoes so como texto.
     */
    protected function normalizeOptions($options)
    {
        $result = [];
        foreach ((array) $options as $option) {
            if (is_array($option)) {
                $label = trim((string) ($option['rotulo'] ?? ''));
                $returns = array_map(function ($value) {
                    return trim((string) $value);
                }, array_slice((array) ($option['retornos'] ?? []), 0, 5));
            } else {
                $label = trim((string) $option);
                $returns = [$label];
            }
            if ($label !== '') {
                $result[] = ['rotulo' => $label, 'retornos' => $returns];
            }
        }

        return $result;
    }

    /**
     * Dinamica proposta pela IA com referencias ja nos tokens finais. Devolve []
     * quando nao ha dinamica utilizavel.
     */
    protected function normalizeDynamic(array $dynamic, array $references, $selfToken)
    {
        $type = (string) ($dynamic['tipo'] ?? '');
        if ($type === '' || !array_key_exists($type, \App\Support\CampoDinamica::TYPES)) {
            return [];
        }

        $ref = function ($token) use ($references, $selfToken) {
            $key = strtolower(trim((string) $token));
            $final = $references[$key] ?? null;
            return ($final && $final !== $selfToken) ? $final : null;
        };

        $targets = [];
        $columns = [];
        foreach (array_values((array) ($dynamic['alvos'] ?? [])) as $index => $token) {
            $final = $ref($token);
            if ($final) {
                $targets[] = $final;
                $column = (string) (($dynamic['colunas'] ?? [])[$index] ?? 'return_1');
                $columns[] = in_array($column, \App\Support\CampoDinamica::RETURN_COLUMNS, true) ? $column : 'return_1';
            }
        }

        $result = [
            'tipo' => $type,
            'alvos' => $targets,
            'colunas' => $columns,
            'mostrar_alvos' => !empty($dynamic['mostrar_alvos']),
            'valor' => trim((string) ($dynamic['valor'] ?? '')),
            'obrigatorio' => !empty($dynamic['obrigatorio']),
            'por_item' => (int) ($dynamic['por_item'] ?? 1) === 2 ? 2 : 1,
            'modo' => (string) ($dynamic['modo'] ?? ''),
            'pessoa' => $ref($dynamic['pessoa'] ?? ''),
            'documento' => $ref($dynamic['documento'] ?? ''),
        ];

        $needsTargets = ['retorno_opcao', 'mostrar_sim_nao', 'mostrar_por_valor', 'quantidade', 'copiar', 'proxima_parcela', 'tipo_pessoa'];
        if (in_array($type, $needsTargets, true) && !$targets) {
            return [];
        }
        if ($type === 'qualificacao' && (!$result['pessoa'] || !$result['documento'])) {
            return [];
        }
        if ($type === 'mostrar_por_valor' && $result['valor'] === '') {
            return [];
        }

        return $result;
    }

    /**
     * Dinamicas que o agente deve propor (mesmas da tela de edicao, CampoDinamica).
     */
    protected function dynamicsGuide()
    {
        return 'DINAMICAS DOS CAMPOS (o formulario do escritorio e "inteligente": os campos agem uns sobre os outros para a peca sair certa com pouca digitacao). '
            . 'Para cada campo, preencha marcador (como o trecho aparece no documento: MAIUSCULAS, INICIAIS ou COMO_DIGITADO), extenso e dinamica (tipo vazio quando nao houver). '
            . 'Regras: '
            . '1) Trecho opcional (paragrafo ou frase que so entra em certos casos, ex.: pedido de segredo de justica, gratuidade, avalista): crie um SELECT com opcoes SIM (retornos[0] = HTML exato do trecho) e NAO (retornos[0] vazio) e coloque o token do SELECT no lugar do trecho; nao crie campo para o texto fixo do trecho. '
            . 'Trechos alternativos (uma redacao ou outra, ex.: pessoa fisica x juridica) seguem a mesma ideia: uma opcao por redacao, com o texto no retornos[0]. '
            . '2) Quando a resposta SIM tambem exige dados (ex.: dados do avalista), crie esses campos e no SELECT use dinamica mostrar_sim_nao com alvos = tokens desses campos. Para exibir campos por outro valor, use mostrar_por_valor com valor. '
            . '3) Qualificacao da parte ("pessoa fisica, inscrita no CPF sob o no ..." / "pessoa juridica de direito privado, inscrita no CNPJ sob o no ..."): crie PESSOA (SELECT com opcoes FISICA e JURIDICA), CPF/CNPJ (TEXT, comportamento cpf_cnpj) e QUALIFICACAO (TEXT) no lugar da frase, com dinamica qualificacao, pessoa = token de PESSOA e documento = token do CPF/CNPJ. Se so houver o numero do documento variando entre CPF e CNPJ, use no PESSOA a dinamica tipo_pessoa com alvos = [token do documento]. '
            . '4) Itens repetidos (parcelas com vencimento e valor, bens, executados): crie um SELECT de quantidade (opcoes 01, 02, ...) e os campos de cada item, no maximo 12 itens, na ordem; no SELECT use dinamica quantidade, alvos = tokens dos campos dos itens na ordem e por_item = 2 para parcelas (vencimento e valor) ou 1. Para vencimentos mensais, use proxima_parcela (modo data) do vencimento de um item para o seguinte. '
            . '5) Datas e valores: data -> comportamento date; data escrita por extenso no documento -> extenso data_por_extenso; data da peticao/local e data no fecho -> extenso data_atual_por_extenso; valor com extenso entre parenteses -> comportamento decimal e extenso valor_por_extenso (o extenso e gerado, nao crie campo para ele). '
            . '6) Use a dinamica nao_ajuizar so se o documento disser que a peticao nao deve ser ajuizada em certa resposta. Use copiar quando o mesmo valor deve aparecer em dois campos diferentes. '
            . 'Nas dinamicas, refira-se aos campos sempre pelos tokens temporarios @VAR_...@. Campos sem dinamica: tipo vazio, listas vazias, por_item 1, modo vazio.';
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
