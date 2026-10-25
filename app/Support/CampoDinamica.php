<?php

namespace App\Support;

/**
 * Dinamicas de campo configuraveis na tela de edicao do modelo.
 *
 * Cada dinamica vira o mesmo script do sistema antigo que o motor da montagem
 * (resources/views/peticao/assemble.blade.php) ja executa, e a configuracao fica
 * guardada em eventos_frontend['dinamica'] para a tela reabrir com os valores.
 * Campos vindos do legado, sem essa configuracao, sao reconhecidos pelo script
 * (detect), para a tela mostrar a dinamica que eles ja tem.
 *
 * Os ids de campo sao sempre o id_input (legacy_input_id ?: id), que e o numero
 * usado em @campoN@ e nos scripts.
 */
class CampoDinamica
{
    const TYPES = [
        '' => 'Nenhuma (ou script personalizado)',
        'retorno_opcao' => 'A opção escolhida preenche outros campos',
        'mostrar_sim_nao' => 'Mostrar campos quando a resposta for SIM',
        'mostrar_por_valor' => 'Mostrar campos quando o valor for…',
        'quantidade' => 'A quantidade escolhida mostra campos (parcelas, bens)',
        'copiar' => 'Copiar o valor para outros campos',
        'proxima_parcela' => 'Preencher a próxima parcela (data +1 mês ou mesmo valor)',
        'tipo_pessoa' => 'Tipo de pessoa troca o campo do documento (CPF/CNPJ)',
        'qualificacao' => 'Qualificação automática (pessoa física/jurídica + CPF/CNPJ)',
        'nao_ajuizar' => 'Bloquear o ajuizamento ("não ajuizar")',
    ];

    const RETURN_COLUMNS = ['return_1', 'return_2', 'return_3', 'return_4', 'return_5'];

    /**
     * Normaliza o que veio do formulario.
     */
    public static function fromRequest(array $data)
    {
        $config = self::configFromRequest($data);
        if (!$config) {
            return null;
        }

        // Detalhes do campo do legado que a tela devolve em campos ocultos, para
        // o script voltar igual: eventos de origem, seletor do nbens e argumento
        // do "nao ajuizar".
        $events = array_values(array_intersect(preg_split('/\s*,\s*/', (string) ($data['dinamica_eventos'] ?? ''), -1, PREG_SPLIT_NO_EMPTY), ['focus', 'load', 'blur']));
        if ($events && ($data['dinamica_tipo_original'] ?? '') === $config['tipo']) {
            $config['eventos'] = $events;
        }
        if ($config['tipo'] === 'quantidade' && !empty($data['dinamica_seletor'])) {
            $config['seletor'] = preg_replace('/[^A-Za-z]/', '', (string) $data['dinamica_seletor']);
        }
        if ($config['tipo'] === 'nao_ajuizar' && trim((string) ($data['dinamica_argumento'] ?? '')) !== '') {
            $config['argumento'] = trim((string) $data['dinamica_argumento']);
        }

        return $config;
    }

    protected static function configFromRequest(array $data)
    {
        $tipo = (string) ($data['dinamica_tipo'] ?? '');
        if (!array_key_exists($tipo, self::TYPES) || $tipo === '') {
            return null;
        }

        $ids = function ($value) {
            return array_values(array_unique(array_filter(array_map('intval', (array) $value))));
        };

        $config = ['tipo' => $tipo];
        switch ($tipo) {
            case 'retorno_opcao':
                $fills = [];
                foreach ((array) ($data['dinamica_alvos'] ?? []) as $index => $target) {
                    $target = (int) $target;
                    $column = (string) (($data['dinamica_colunas'] ?? [])[$index] ?? 'return_1');
                    if ($target > 0 && in_array($column, self::RETURN_COLUMNS, true)) {
                        $fills[] = ['alvo' => $target, 'coluna' => $column];
                    }
                }
                $config['preenche'] = $fills;
                $config['mostrar_alvos'] = !empty($data['dinamica_mostrar_alvos']);
                return $fills ? $config : null;
            case 'mostrar_sim_nao':
            case 'copiar':
                $config['alvos'] = $ids($data['dinamica_campos'] ?? []);
                return $config['alvos'] ? $config : null;
            case 'mostrar_por_valor':
                $config['valor'] = trim((string) ($data['dinamica_valor'] ?? ''));
                $config['alvos'] = $ids($data['dinamica_campos'] ?? []);
                $config['obrigatorio'] = !empty($data['dinamica_obrigatorio']);
                return ($config['valor'] !== '' && $config['alvos']) ? $config : null;
            case 'quantidade':
                $config['alvos'] = $ids($data['dinamica_campos'] ?? []);
                $config['por_item'] = (int) ($data['dinamica_por_item'] ?? 1) === 2 ? 2 : 1;
                return $config['alvos'] ? $config : null;
            case 'proxima_parcela':
                $config['alvo'] = (int) ($data['dinamica_alvo'] ?? 0);
                $config['modo'] = ($data['dinamica_modo'] ?? '') === 'data' ? 'data' : 'valor';
                return $config['alvo'] > 0 ? $config : null;
            case 'tipo_pessoa':
                $config['alvo'] = (int) ($data['dinamica_alvo'] ?? 0);
                return $config['alvo'] > 0 ? $config : null;
            case 'qualificacao':
                $config['pessoa'] = (int) ($data['dinamica_pessoa'] ?? 0);
                $config['documento'] = (int) ($data['dinamica_documento'] ?? 0);
                return ($config['pessoa'] > 0 && $config['documento'] > 0) ? $config : null;
            case 'nao_ajuizar':
                $config['modo'] = ($data['dinamica_modo'] ?? '') === 'excecao' ? 'excecao' : 'nao';
                return $config;
        }

        return null;
    }

    /**
     * Scripts (focus/load/blur) da dinamica, no formato que o motor executa.
     *
     * @param int   $selfId  id_input do proprio campo
     * @param array $options opcoes do campo: [['id' => id_dados, 'label' => rotulo], ...]
     */
    public static function scripts(array $config, $selfId, array $options = [])
    {
        $scripts = self::buildScripts($config, $selfId, $options);

        // Campo do legado: o script volta para os mesmos eventos de antes
        // (ex.: copia feita ao entrar no campo, e nao ao sair).
        $events = array_values(array_intersect((array) ($config['eventos'] ?? []), ['focus', 'load', 'blur']));
        $script = trim((string) (reset($scripts) ?: ''));
        if ($events && $script !== '') {
            return array_fill_keys($events, $script);
        }

        return $scripts;
    }

    protected static function buildScripts(array $config, $selfId, array $options = [])
    {
        $selfId = (int) $selfId;
        $spec = function (array $ids) use ($selfId) {
            return implode('_|_', array_map(function ($id) {
                return 'campo' . (int) $id;
            }, array_merge([$selfId], $ids)));
        };

        switch ($config['tipo'] ?? '') {
            case 'retorno_opcao':
                $parts = [];
                foreach ($config['preenche'] ?? [] as $fill) {
                    $parts[] = sprintf('fc_ajax_comp("tp_dados_tb","%s","campo%d","unir","id_dados",this,1);', $fill['coluna'], (int) $fill['alvo']);
                    if (!empty($config['mostrar_alvos'])) {
                        $parts[] = sprintf('mcampo("%s");', $spec([(int) $fill['alvo']]));
                    }
                }
                $script = implode(' ', $parts);
                return ['focus' => $script, 'load' => $script, 'blur' => $script];
            case 'mostrar_sim_nao':
                $script = sprintf('mcampo("%s");', $spec($config['alvos']));
                return ['focus' => $script, 'blur' => $script];
            case 'quantidade':
                $script = (int) ($config['por_item'] ?? 1) === 2
                    ? sprintf('nparcelas("%s");', $spec($config['alvos']))
                    : sprintf('nbens("%s","%s");', $spec($config['alvos']), preg_replace('/[^A-Za-z]/', '', $config['seletor'] ?? 'input') ?: 'input');
                return ['focus' => $script];
            case 'copiar':
                $script = implode(' ', array_map(function ($id) {
                    return sprintf('$("#campo%d").val($(this).val());', (int) $id);
                }, $config['alvos']));
                return ['blur' => $script];
            case 'proxima_parcela':
                return ($config['modo'] ?? '') === 'data'
                    ? ['blur' => sprintf('addDataCampo(%d,%d,1);', $selfId, (int) $config['alvo'])]
                    : ['focus' => sprintf('repeteValor(%d,%d);', $selfId, (int) $config['alvo'])];
            case 'tipo_pessoa':
                $cpf = $cnpj = 0;
                foreach ($options as $option) {
                    $label = self::plain($option['label'] ?? '');
                    if ($label === 'FISICA') $cpf = (int) $option['id'];
                    if ($label === 'JURIDICA') $cnpj = (int) $option['id'];
                }
                return ['focus' => sprintf('alteraInput(this,%d,%d,%d);', (int) $config['alvo'], $cpf, $cnpj)];
            case 'qualificacao':
                return ['focus' => sprintf('merge_fields("pessoa |%d|%d","%d");', (int) $config['pessoa'], (int) $config['documento'], $selfId)];
            case 'nao_ajuizar':
                $argument = trim(preg_replace('/[^\pL\pN \-]/u', '', (string) ($config['argumento'] ?? '')));
                return ['focus' => ($config['modo'] ?? '') === 'excecao' ? sprintf('validate_peticao("%s");', $argument !== '' ? $argument : 'excecao') : 'validate_peticao();'];
        }

        return [];
    }

    /**
     * Reconhece a dinamica pelos scripts de um campo (inclusive do legado).
     * Devolve null quando nao ha dinamica ou o script mistura coisas que a tela
     * nao representa (o script continua valendo, como personalizado).
     */
    public static function detect(array $events, $selfId)
    {
        if (!empty($events['dinamica']['tipo'])) {
            return $events['dinamica'];
        }

        $script = '';
        $present = [];
        foreach (['focus', 'load', 'blur'] as $name) {
            if (!empty($events[$name]) && is_string($events[$name]) && trim($events[$name]) !== '') {
                $script .= ' ' . $events[$name];
                $present[] = $name;
            }
        }
        $script = trim($script);
        if ($script === '' && !empty($events['conditional']['target_ids'])) {
            return [
                'tipo' => 'mostrar_por_valor',
                'valor' => (string) ($events['conditional']['value'] ?? ''),
                'alvos' => array_map('intval', (array) $events['conditional']['target_ids']),
                'obrigatorio' => !empty($events['conditional']['required']),
            ];
        }
        if ($script === '' || !self::isSingleDynamic($script)) {
            return null;
        }

        // Todos os eventos trazem o mesmo script; usa o primeiro para reconhecer.
        $config = self::detectType(trim($events[$present[0]]), $selfId);
        if (!$config) {
            return null;
        }
        $config['eventos'] = $present;
        if ($config['tipo'] === 'quantidade' && preg_match('/nbens\([^,]+,\s*["\']([A-Za-z]+)["\']/i', $script, $m)) {
            $config['seletor'] = $m[1];
        }
        if ($config['tipo'] === 'nao_ajuizar' && preg_match('/validate_peticao\(\s*["\']([^"\']+)["\']/i', $script, $m)) {
            $config['argumento'] = $m[1];
        }

        return $config;
    }

    protected static function detectType($script, $selfId)
    {
        $selfId = (int) $selfId;
        $fieldIds = function ($spec) use ($selfId) {
            preg_match_all('/campo(\d+)/i', $spec, $m);
            return array_values(array_filter(array_unique(array_map('intval', $m[1])), function ($id) use ($selfId) {
                return $id !== $selfId;
            }));
        };

        if (preg_match_all('/fc_ajax_comp\("tp_dados_tb","(return_\d)","campo(\d+)"/i', $script, $m, PREG_SET_ORDER)) {
            $fills = [];
            foreach ($m as $match) {
                $fills[$match[2] . $match[1]] = ['alvo' => (int) $match[2], 'coluna' => strtolower($match[1])];
            }
            return ['tipo' => 'retorno_opcao', 'preenche' => array_values($fills), 'mostrar_alvos' => stripos($script, 'mcampo(') !== false];
        }
        if (preg_match('/n(parcelas|bens)\(\s*["\']([^"\']+)["\']/i', $script, $m)) {
            return ['tipo' => 'quantidade', 'alvos' => $fieldIds($m[2]), 'por_item' => strtolower($m[1]) === 'parcelas' ? 2 : 1];
        }
        if (preg_match('/alteraInput\(\s*this\s*,\s*(\d+)/i', $script, $m)) {
            return ['tipo' => 'tipo_pessoa', 'alvo' => (int) $m[1]];
        }
        if (preg_match('/merge_fields\(\s*["\']pessoa\s*\|(\d+)\|(\d+)["\']/i', $script, $m)) {
            return ['tipo' => 'qualificacao', 'pessoa' => (int) $m[1], 'documento' => (int) $m[2]];
        }
        if (preg_match('/addDataCampo\(\s*\d+\s*,\s*(\d+)/i', $script, $m)) {
            return ['tipo' => 'proxima_parcela', 'alvo' => (int) $m[1], 'modo' => 'data'];
        }
        if (preg_match('/repeteValor\(\s*\d+\s*,\s*(\d+)/i', $script, $m)) {
            return ['tipo' => 'proxima_parcela', 'alvo' => (int) $m[1], 'modo' => 'valor'];
        }
        if (preg_match('/validate_peticao\(\s*(["\'][^"\']*["\'])?\s*\)/i', $script, $m)) {
            return ['tipo' => 'nao_ajuizar', 'modo' => !empty($m[1]) && trim($m[1], '"\' ') !== '' ? 'excecao' : 'nao'];
        }
        if (preg_match_all('/mcampo\(\s*["\']([^"\']+)["\']\s*\)/i', $script, $m)) {
            $targets = [];
            foreach ($m[1] as $spec) {
                $targets = array_merge($targets, $fieldIds($spec));
            }
            return ['tipo' => 'mostrar_sim_nao', 'alvos' => array_values(array_unique($targets))];
        }
        if (preg_match_all('/\$\(\s*["\']#campo(\d+)["\']\s*\)\.val\(\s*\$\(\s*this\s*\)\.val\(\s*\)\s*\)/i', $script, $m)) {
            return ['tipo' => 'copiar', 'alvos' => array_values(array_unique(array_map('intval', $m[1])))];
        }

        return null;
    }

    /**
     * So reconhece um script que seja inteiro de uma unica dinamica. Scripts que
     * misturam mais coisas (lista associada, data por extenso, duas dinamicas)
     * ficam como personalizados: a tela nao os reescreve ao salvar.
     */
    protected static function isSingleDynamic($script)
    {
        $families = [
            'retorno_opcao' => '/fc_ajax_comp\(\s*"tp_dados_tb"[^)]*\)\s*;?/i',
            'quantidade' => '/n(?:parcelas|bens)\([^)]*\)\s*;?/i',
            'tipo_pessoa' => '/alteraInput\([^)]*\)\s*;?/i',
            'qualificacao' => '/merge_fields\([^)]*\)\s*;?/i',
            'proxima_parcela' => '/(?:addDataCampo|repeteValor)\([^)]*\)\s*;?/i',
            'nao_ajuizar' => '/validate_peticao\([^)]*\)\s*;?/i',
            'copiar' => '/\$\(\s*["\']#campo\d+["\']\s*\)\.val\(\s*\$\(\s*this\s*\)\.val\(\s*\)\s*\)\s*;?/i',
        ];
        $mcampo = '/mcampo\([^)]*\)\s*;?/i';
        $focus = '/\$\(\s*["\']#campo\d+["\']\s*\)\.focus\(\s*\)\s*;?/i';

        $present = [];
        $rest = $script;
        foreach ($families as $name => $pattern) {
            if (preg_match($pattern, $rest)) {
                $present[] = $name;
                $rest = preg_replace($pattern, ' ', $rest);
            }
        }
        $hasMcampo = (bool) preg_match($mcampo, $rest);
        $rest = preg_replace([$mcampo, $focus], ' ', $rest);

        if (trim(str_replace(';', '', $rest)) !== '') {
            return false; // sobrou outra coisa (lista, data por extenso, script livre)
        }
        if (count($present) > 1) {
            return false;
        }
        if ($hasMcampo && $present && $present !== ['retorno_opcao']) {
            return false; // mcampo so acompanha o retorno da opcao
        }

        return $present || $hasMcampo;
    }

    /**
     * Resumo em uma linha, para o titulo do campo na tela.
     */
    public static function summary(array $config = null)
    {
        if (!$config || empty($config['tipo'])) {
            return '';
        }

        return self::TYPES[$config['tipo']] ?? '';
    }

    /**
     * Linha da opcao na caixa de opcoes: "Rotulo|Retorno 1|...|Retorno N" (ate o
     * ultimo retorno preenchido). Quebras de linha viram espaco: a caixa usa uma
     * linha por opcao, e os retornos sao HTML (a quebra nao muda o resultado).
     */
    public static function optionLine($opcao)
    {
        $extras = is_array($opcao->valores_extras) ? $opcao->valores_extras : [];
        $parts = [(string) $opcao->rotulo, (string) $opcao->valor_retorno];
        $last = 1;
        foreach ([2, 3, 4, 5] as $n) {
            $parts[$n] = (string) ($extras['return_' . $n] ?? '');
            if ($parts[$n] !== '') {
                $last = $n;
            }
        }

        $parts = array_slice($parts, 0, $last + 1);

        return implode('|', array_map(function ($part) {
            return trim(preg_replace('/\s*[\r\n]+\s*/', ' ', $part));
        }, $parts));
    }

    protected static function plain($value)
    {
        $value = mb_strtoupper(trim((string) $value), 'UTF-8');
        $value = strtr($value, ['Á' => 'A', 'Í' => 'I', 'Ú' => 'U', 'É' => 'E', 'Ó' => 'O', 'Ã' => 'A', 'Ç' => 'C']);

        return preg_replace('/[^A-Z]/', '', $value);
    }
}
