<?php

namespace App\Services;

use App\ListaGrupo;
use App\PeticaoModelo;
use Illuminate\Support\Str;

/**
 * Monta o pacote JSON de um modelo para levar a outro ambiente.
 *
 * Os IDs dos campos mudam de um banco para outro, entao nada no pacote pode
 * citar um ID. Cada campo recebe uma chave textual e toda citacao a ele vira
 * uma referencia, que o importador (peticao:modelo-stone-import) troca pelos
 * IDs criados no destino:
 *
 *   @campo123@                  ->  {{field:chave}}
 *   campo123, repeteValor(123)  ->  campo{{field_id:chave}}, {{field_id:chave}}
 */
class PeticaoModeloPackageService
{
    const VERSION = 2;

    const MAX_KEY_LENGTH = 80;

    public function export(PeticaoModelo $modelo)
    {
        $modelo->loadMissing(['campos.opcoes', 'paragrafos', 'cliente', 'setor', 'servidor']);

        $keys = $this->fieldKeys($modelo->campos);
        $lists = [];
        $fields = [];

        foreach ($modelo->campos as $campo) {
            $row = $campo->toArray();
            unset(
                $row['id'],
                $row['modelo_id'],
                $row['legacy_input_id'],
                $row['token'],
                $row['created_at'],
                $row['updated_at'],
                $row['opcoes']
            );

            $row['key'] = $keys[(int) $campo->id_input];
            $row['prefixo'] = $this->portableText($campo->prefixo, $keys);
            $row['sufixo'] = $this->portableText($campo->sufixo, $keys);
            $row['valor_padrao'] = $this->portableText($campo->valor_padrao, $keys);
            // Opcao citada por numero num script (alteraInput): {{option_id:campo:posicao}}.
            $optionRefs = [];
            foreach (array_values($campo->opcoes->all()) as $position => $opcao) {
                $optionRefs[(int) $opcao->id_dados] = '{{option_id:' . $row['key'] . ':' . $position . '}}';
            }
            $row['eventos_frontend'] = $this->portableEvents((array) ($campo->eventos_frontend ?: []), $keys, $optionRefs);

            $listName = $this->listName($campo);
            if ($listName !== null) {
                $row['lista_grupo'] = $listName;
                $lists[$listName] = true;
            }

            $options = [];
            foreach ($campo->opcoes as $opcao) {
                $options[] = [
                    'rotulo' => $opcao->rotulo,
                    'valor_retorno' => $this->portableText($opcao->valor_retorno, $keys),
                    'valores_extras' => $opcao->valores_extras,
                    'ordem' => $opcao->ordem,
                ];
            }
            $row['options'] = $options;

            $fields[] = $row;
        }

        $paragraphs = [];
        foreach ($modelo->paragrafos as $paragrafo) {
            $paragraphs[] = [
                'titulo' => $paragrafo->titulo,
                'conteudo_html' => $this->portableText($paragrafo->conteudo_html, $keys),
                'ordem' => $paragrafo->ordem,
                'visivel' => (bool) $paragrafo->visivel,
                'ativo' => (bool) $paragrafo->ativo,
            ];
        }

        return [
            'version' => self::VERSION,
            'dependencies' => [
                'client' => optional($modelo->cliente)->cliente_name,
                'client_code' => optional($modelo->cliente)->cliente_cod,
                'sector_name' => optional($modelo->setor)->nome_setor,
                'sector_code' => optional($modelo->setor)->cod_setor,
                'server' => optional($modelo->servidor)->nome,
                'lists' => array_keys($lists),
            ],
            'model' => [
                'nome' => $modelo->nome,
                'slug' => $modelo->slug,
                'arquivo_padrao' => $modelo->arquivo_padrao,
                'cabecalho_html' => $this->portableText($modelo->cabecalho_html, $keys),
                'rodape_html' => $this->portableText($modelo->rodape_html, $keys),
                'metadata' => $this->portableDeep($modelo->metadata ?: [], $keys),
            ],
            'fields' => $fields,
            'paragraphs' => $paragraphs,
        ];
    }

    /**
     * Uma chave por campo, derivada do rotulo e unica dentro do modelo.
     * Devolve [id do campo => chave].
     */
    protected function fieldKeys($campos)
    {
        $keys = [];
        $used = [];

        foreach ($campos as $campo) {
            $base = substr(Str::slug((string) $campo->rotulo, '_'), 0, self::MAX_KEY_LENGTH);
            if ($base === '' || $base === false) {
                $base = 'campo';
            }

            $key = $base;
            $suffix = 2;
            while (isset($used[$key])) {
                $key = $base . '_' . $suffix;
                $suffix++;
            }

            $used[$key] = true;
            $keys[(int) $campo->id_input] = $key;
        }

        return $keys;
    }

    /**
     * Troca os marcadores @campoN@ dos campos deste modelo por {{field:chave}}.
     * Marcadores de campos que nao existem no modelo ficam como estao.
     */
    protected function portableText($text, array $keys)
    {
        if (!is_string($text) || $text === '') {
            return $text;
        }

        // A forma do marcador decide maiusculas/iniciais (@CAMPO1@, @Campo1@) e
        // _pre/_pos traz o prefixo/sufixo: nesses casos so o numero vira
        // referencia, para a forma chegar igual no destino.
        return preg_replace_callback('/@(campo)(\d+)(_pre|_pos)?@/i', function ($matches) use ($keys) {
            $id = (int) $matches[2];
            if (!isset($keys[$id])) {
                return $matches[0];
            }
            $suffix = isset($matches[3]) ? $matches[3] : '';
            if ($matches[1] === 'campo' && $suffix === '') {
                return '{{field:' . $keys[$id] . '}}';
            }

            return '@' . $matches[1] . '{{field_id:' . $keys[$id] . '}}' . $suffix . '@';
        }, $text);
    }

    /**
     * Configuracao da dinamica (tela de edicao) guarda os campos pelo numero:
     * alvo, pessoa, documento, alvos[] e preenche[].alvo.
     */
    protected function portableDynamic(array $config, array $keys)
    {
        $reference = function ($id) use ($keys) {
            return isset($keys[(int) $id]) ? '{{field_id:' . $keys[(int) $id] . '}}' : $id;
        };
        foreach (['alvo', 'pessoa', 'documento'] as $name) {
            if (isset($config[$name]) && is_numeric($config[$name])) {
                $config[$name] = $reference($config[$name]);
            }
        }
        if (isset($config['alvos']) && is_array($config['alvos'])) {
            $config['alvos'] = array_map($reference, $config['alvos']);
        }
        if (isset($config['preenche']) && is_array($config['preenche'])) {
            foreach ($config['preenche'] as $index => $fill) {
                if (is_array($fill) && isset($fill['alvo'])) {
                    $config['preenche'][$index]['alvo'] = $reference($fill['alvo']);
                }
            }
        }

        return $config;
    }

    protected function portableDeep($value, array $keys)
    {
        if (is_array($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = $this->portableDeep($item, $keys);
            }

            return $value;
        }

        return $this->portableText($value, $keys);
    }

    protected function portableEvents(array $events, array $keys, array $optionRefs = [])
    {
        foreach ($events as $name => $value) {
            if ($name === 'dinamica' && is_array($value)) {
                $events[$name] = $this->portableDynamic($value, $keys);
                continue;
            }
            if ($name === 'conditional' && is_array($value)) {
                $targets = [];
                foreach ((array) ($value['target_ids'] ?? []) as $targetId) {
                    $targetId = (int) $targetId;
                    $targets[] = isset($keys[$targetId]) ? '{{field_id:' . $keys[$targetId] . '}}' : $targetId;
                }
                $value['target_ids'] = $targets;
                $events[$name] = $value;
                continue;
            }

            $events[$name] = is_string($value)
                ? $this->portableScript($value, $keys, $optionRefs)
                : $this->portableDeep($value, $keys);
        }

        return $events;
    }

    /**
     * Os eventos legados citam campos de tres formas: campoN (em seletores e
     * listas), numeros soltos em funcoes conhecidas e o marcador @campoN@.
     */
    protected function portableScript($script, array $keys, array $optionRefs = [])
    {
        $reference = function ($id) use ($keys) {
            $id = (int) $id;

            return isset($keys[$id]) ? '{{field_id:' . $keys[$id] . '}}' : (string) $id;
        };

        // Qualificacao automatica: merge_fields("pessoa |PESSOA|DOCUMENTO","ALVO").
        $script = preg_replace_callback('/\bmerge_fields\(([^)]*)\)/i', function ($matches) use ($reference) {
            return 'merge_fields(' . preg_replace_callback('/(?<![\w{])(\d+)(?![\w}])/', function ($number) use ($reference) {
                return $reference($number[1]);
            }, $matches[1]) . ')';
        }, $script);

        // Tipo de pessoa: alteraInput(this, CAMPO_DOCUMENTO, OPCAO_FISICA, OPCAO_JURIDICA).
        // As opcoes tambem ganham outro numero no destino.
        $script = preg_replace_callback('/\balteraInput\(\s*this\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i', function ($matches) use ($reference, $optionRefs) {
            $option = function ($id) use ($optionRefs) {
                return isset($optionRefs[(int) $id]) ? $optionRefs[(int) $id] : $id;
            };

            return 'alteraInput(this,' . $reference($matches[1]) . ',' . $option($matches[2]) . ',' . $option($matches[3]) . ')';
        }, $script);

        // "campo12" como o formulario o conhece: em seletores (#campo12, .dis_campo12)
        // e em listas ("campo12_|_campo13"). Ficam de fora os marcadores (@campo12@,
        // tratados adiante) e o token interno @modelo5_campo12@.
        $script = preg_replace_callback('/(@modelo\d+_)?(?<![A-Za-z0-9@])(campo)(\d+)(?![0-9@])/i', function ($matches) use ($reference) {
            return $matches[1] !== '' ? $matches[0] : $matches[2] . $reference($matches[3]);
        }, $script);

        // Funcoes que recebem o numero do campo sem o prefixo, com ou sem aspas.
        $script = preg_replace_callback(
            '/\b(repeteValor|addDataCampo|fc_numextenso)(\(\s*)(["\']?)(\d+)\3(\s*,\s*)(["\']?)(\d+)\6/i',
            function ($matches) use ($reference) {
                return $matches[1] . $matches[2] . $matches[3] . $reference($matches[4]) . $matches[3]
                    . $matches[5] . $matches[6] . $reference($matches[7]) . $matches[6];
            },
            $script
        );

        return $this->portableText($script, $keys);
    }

    protected function listName($campo)
    {
        $config = $campo->associated_list_config;
        if (!$config || empty($config['group_id'])) {
            return null;
        }

        $grupo = ListaGrupo::where('id_grupo', $config['group_id'])->first();
        $name = $grupo ? trim((string) $grupo->nome_grupo) : '';

        return $name !== '' ? $name : null;
    }
}
