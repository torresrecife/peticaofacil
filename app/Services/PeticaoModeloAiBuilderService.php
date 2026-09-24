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
        $html = $this->prepareAiInput($html);
        $result = $this->client->createStructuredResponse([
            ['role' => 'system', 'content' => 'Voce analisa modelos juridicos brasileiros. Preserve o HTML original, incluindo tabelas, estilos inline, negrito, fonte, tamanho, alinhamento e recuos. Associe campos as colunas NEO somente quando houver seguranca. Nunca invente colunas. Colunas NEO conhecidas: numero_integracao, reu, autor, advo_autor, reu_cpfcnpj, autor_cpfcnpj, logradouro, bairro, cidade, estado, cep, marca, modelo, ano, placa, chassi, renavam, cor, numero_processo, comarca, resultado, cpf, cnpj, valor, data, data_citacao. Use tokens temporarios como @VAR_NOME@.'],
            ['role' => 'user', 'content' => "Analise o documento Word abaixo e estruture um modelo de peticao.\n\n" . $html],
        ], $this->schema(), 'peticao_modelo_analysis');

        if (!$result['ok']) {
            throw new RuntimeException($result['error'] ?: 'Nao foi possivel analisar o modelo com IA.');
        }

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
            'required' => ['nome', 'descricao', 'cabecalho_html', 'rodape_html', 'campos', 'paragrafos'],
            'properties' => [
                'nome' => ['type' => 'string'], 'descricao' => ['type' => 'string'],
                'cabecalho_html' => ['type' => 'string'], 'rodape_html' => ['type' => 'string'],
                'campos' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false,
                    'required' => ['rotulo', 'token', 'tipo', 'obrigatorio', 'origem_coluna', 'comportamento', 'prefixo', 'sufixo', 'opcoes'],
                    'properties' => [
                        'rotulo' => ['type' => 'string'], 'token' => ['type' => 'string'],
                        'tipo' => ['type' => 'string', 'enum' => ['TEXT', 'TEXTAREA', 'SELECT']],
                        'obrigatorio' => ['type' => 'boolean'],
                        'origem_coluna' => ['type' => 'string'],
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

    protected function normalize(array $data)
    {
        $tokens = [];
        $fields = [];
        foreach (array_values($data['campos'] ?? []) as $index => $field) {
            $token = '@campo' . ($index + 1) . '@';
            $source = trim((string) ($field['token'] ?? ''));
            if ($source !== '') {
                $tokens[$source] = $token;
            }
            $fields[] = [
                'rotulo' => trim($field['rotulo'] ?: 'Campo ' . ($index + 1)),
                'token' => $token, 'tipo' => $field['tipo'],
                'obrigatorio' => (bool) $field['obrigatorio'],
                'origem_coluna' => trim((string) ($field['origem_coluna'] ?? '')),
                'comportamento' => (string) ($field['comportamento'] ?? ''),
                'prefixo' => (string) ($field['prefixo'] ?? ''), 'sufixo' => (string) ($field['sufixo'] ?? ''),
                'opcoes' => array_values(array_filter(array_map('trim', $field['opcoes'] ?? []))),
                'ordem' => $index + 1,
            ];
        }
        $replace = function ($value) use ($tokens) {
            return $tokens ? str_ireplace(array_keys($tokens), array_values($tokens), (string) $value) : (string) $value;
        };
        $paragraphs = [];
        foreach (array_values($data['paragrafos'] ?? []) as $index => $paragraph) {
            $paragraphs[] = ['titulo' => trim($paragraph['titulo'] ?: 'Paragrafo ' . ($index + 1)), 'conteudo_html' => $replace($paragraph['conteudo_html']), 'ordem' => $index + 1];
        }
        return ['nome' => trim($data['nome']), 'descricao' => trim($data['descricao']), 'cabecalho_html' => $replace($data['cabecalho_html']), 'rodape_html' => $replace($data['rodape_html']), 'campos' => $fields, 'paragrafos' => $paragraphs];
    }
}
