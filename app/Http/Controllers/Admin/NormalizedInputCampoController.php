<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\Support\CampoDinamica;
use Illuminate\Http\Request;

class NormalizedInputCampoController extends Controller
{
    public function store(Request $request, PeticaoModelo $modeloNormalizado)
    {
        $data = $this->validateData($request);

        $campo = PeticaoModeloCampo::create([
            'modelo_id' => $modeloNormalizado->id,
            'legacy_input_id' => null,
            'rotulo' => $data['input_title'],
            'token' => '@campo_novo@',
            'tipo' => $data['input_tipo'],
            'comportamento' => $this->normalizeBehaviorValue($data['input_behavior'] ?? ''),
            'origem_coluna' => $data['input_val'] ?? null,
            'origem_alias' => $data['input_db'] ?? null,
            'prefixo' => $data['input_pre'] ?? null,
            'sufixo' => $data['input_pos'] ?? null,
            'valor_padrao' => $data['texto_padrao'] ?? null,
            'classe_css' => $data['add_class'] ?? null,
            'largura' => ($data['input_width'] ?? null) ?: $this->resolveWidth((int) $data['input_cols']),
            'colunas_layout' => ($data['input_cols'] ?? null) ?: 1,
            'linhas_layout' => $data['input_rols'] ?? 0,
            'ordem' => ($data['input_order'] ?? null) ?: (((int) PeticaoModeloCampo::where('modelo_id', $modeloNormalizado->id)->max('ordem')) + 1),
            'obrigatorio' => (int) ($data['input_req'] ?? 0) === 1,
            'visivel' => ($data['hide'] ?? 'true') !== 'none',
            'gera_nome_arquivo' => ($data['nomepet'] ?? 'N') === 'Y',
            'eventos_frontend' => [],
        ]);

        $campo->origem_alias = $this->resolveOrigemAlias($campo, $data);
        $campo->token = $campo->placeholder;
        $campo->save();

        // As opcoes vem antes dos eventos: tipo de pessoa usa o codigo delas.
        $this->syncOpcoes($campo, $data['opcoes'] ?? '', $data);
        $this->applyFrontendEvents($campo, $data);

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Campo criado.');
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado, PeticaoModeloCampo $campo)
    {
        abort_unless((int) $campo->modelo_id === (int) $modeloNormalizado->id, 404);

        $data = $this->validateData($request);

        $campo->fill([
            'rotulo' => $data['input_title'],
            'tipo' => $data['input_tipo'],
            'comportamento' => $this->normalizeBehaviorValue($data['input_behavior'] ?? ''),
            'origem_coluna' => $data['input_val'] ?? null,
            'origem_alias' => $data['input_db'] ?? null,
            'prefixo' => $data['input_pre'] ?? null,
            'sufixo' => $data['input_pos'] ?? null,
            'valor_padrao' => $data['texto_padrao'] ?? null,
            'classe_css' => $data['add_class'] ?? null,
            'largura' => ($data['input_width'] ?? null) ?: $this->resolveWidth((int) $data['input_cols']),
            'colunas_layout' => ($data['input_cols'] ?? null) ?: 1,
            'linhas_layout' => $data['input_rols'] ?? 0,
            'ordem' => ($data['input_order'] ?? null) ?: $campo->ordem,
            'obrigatorio' => (int) ($data['input_req'] ?? 0) === 1,
            'visivel' => ($data['hide'] ?? 'true') !== 'none',
            'gera_nome_arquivo' => ($data['nomepet'] ?? 'N') === 'Y',
            'eventos_frontend' => [],
        ])->save();

        $campo->origem_alias = $this->resolveOrigemAlias($campo, $data);
        $campo->save();

        $this->syncOpcoes($campo, $data['opcoes'] ?? '', $data);
        $this->applyFrontendEvents($campo, $data);

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Campo atualizado.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'input_title' => 'required|string|max:500',
            'input_tipo' => 'required|in:TEXT,SELECT,TEXTAREA,HIDDEN,TITLE,RADIO2',
            'input_behavior' => 'nullable|in:,date,decimal,cpf,cnpj,cpf_cnpj,fone,cep,integer,processo',
            'input_pre' => 'nullable|string',
            'input_pos' => 'nullable|string',
            'input_db' => 'nullable|string|max:100',
            'input_val' => 'nullable|string|max:100',
            'input_alt' => 'nullable|string|max:50',
            // Existing models may still use the legacy three-column layout. New
            // forms expose only 1 and 2, but updates must not invalidate old data.
            'input_cols' => 'required|integer|min:1|max:3',
            'input_rols' => 'nullable|integer|min:0',
            'input_focu' => 'nullable|string|max:2000',
            'input_load' => 'nullable|string|max:2000',
            'input_blur' => 'nullable|string|max:2000',
            'input_focu_preset' => 'nullable|in:,data_extenso_out,data_atual,dia_semana,valor_extenso',
            'input_load_preset' => 'nullable|in:,data_extenso_out,data_atual,dia_semana,valor_extenso',
            'input_blur_preset' => 'nullable|in:,data_extenso_out,data_atual,dia_semana,valor_extenso',
            'input_width' => 'nullable|integer|min:10|max:5000',
            'input_req' => 'nullable|integer|min:0|max:1',
            'input_order' => 'nullable|integer|min:1',
            'nomepet' => 'nullable|in:Y,N',
            'hide' => 'nullable|in:true,none',
            'texto_padrao' => 'nullable|string',
            'add_class' => 'nullable|string|max:500',
            'opcoes' => 'nullable|string',
            'input_list_group_id' => 'nullable|integer|min:1',
            'input_list_return_column' => 'nullable',
            'input_list_target_field' => 'nullable|integer|min:1',
            'conditional_value' => 'nullable|string|max:200',
            'conditional_targets' => 'nullable|array',
            'conditional_targets.*' => 'integer|min:1',
            'conditional_required' => 'nullable|in:0,1',
            'dinamica_tipo' => 'nullable|in:' . implode(',', array_keys(CampoDinamica::TYPES)),
            'dinamica_alvos' => 'nullable|array',
            'dinamica_alvos.*' => 'nullable|integer|min:1',
            'dinamica_colunas' => 'nullable|array',
            'dinamica_colunas.*' => 'nullable|in:' . implode(',', CampoDinamica::RETURN_COLUMNS),
            'dinamica_mostrar_alvos' => 'nullable|in:0,1',
            'dinamica_campos' => 'nullable|array',
            'dinamica_campos.*' => 'integer|min:1',
            'dinamica_valor' => 'nullable|string|max:200',
            'dinamica_obrigatorio' => 'nullable|in:0,1',
            'dinamica_por_item' => 'nullable|in:1,2',
            'dinamica_alvo' => 'nullable|integer|min:1',
            'dinamica_modo' => 'nullable|in:data,valor,nao,excecao',
            'dinamica_pessoa' => 'nullable|integer|min:1',
            'dinamica_documento' => 'nullable|integer|min:1',
            'dinamica_eventos' => 'nullable|string|max:40',
            'dinamica_tipo_original' => 'nullable|string|max:40',
            'dinamica_seletor' => 'nullable|string|max:20',
            'dinamica_argumento' => 'nullable|string|max:60',
        ]);
    }

    /**
     * Grava os eventos do campo. Com uma dinamica escolhida na tela, os scripts
     * dela substituem os escritos a mao; os presets de data/valor continuam.
     */
    protected function applyFrontendEvents(PeticaoModeloCampo $campo, array $data)
    {
        $campo->load('opcoes');
        $events = $this->buildFrontendEvents($campo, $data);

        $dinamica = CampoDinamica::fromRequest($data);
        if ($dinamica) {
            $dinamica = $this->keepOnlyModelFields($campo, $dinamica);
            $options = $campo->opcoes->map(function ($opcao) {
                return ['id' => $opcao->id_dados, 'label' => $opcao->rotulo];
            })->all();
            $generated = CampoDinamica::scripts($dinamica, $campo->id_input, $options);

            foreach (['focus' => 'input_focu_preset', 'load' => 'input_load_preset', 'blur' => 'input_blur_preset'] as $event => $presetKey) {
                $preset = $this->presetScript(trim((string) ($data[$presetKey] ?? '')));
                $script = trim(($generated[$event] ?? '') . ' ' . $preset);
                if ($script !== '') {
                    $events[$event] = $script;
                } else {
                    unset($events[$event]);
                }
            }

            if ($dinamica['tipo'] === 'mostrar_por_valor') {
                $events['conditional'] = [
                    'value' => $dinamica['valor'],
                    'target_ids' => $dinamica['alvos'],
                    'required' => (bool) $dinamica['obrigatorio'],
                ];
            }

            // "Nao ajuizar": o motor procura os campos pela classe new_required.
            if ($dinamica['tipo'] === 'nao_ajuizar' && stripos((string) $campo->classe_css, 'new_required') === false) {
                $campo->classe_css = trim($campo->classe_css . ' new_required');
            }

            $events['dinamica'] = $dinamica;
        }

        $campo->eventos_frontend = $events;
        $campo->save();
    }

    /**
     * Descarta alvos que nao sao campos deste modelo.
     */
    protected function keepOnlyModelFields(PeticaoModeloCampo $campo, array $dinamica)
    {
        $valid = PeticaoModeloCampo::where('modelo_id', $campo->modelo_id)->get()->map(function ($item) {
            return (int) $item->id_input;
        })->all();
        $isValid = function ($id) use ($valid, $campo) {
            return in_array((int) $id, $valid, true) && (int) $id !== (int) $campo->id_input;
        };

        foreach (['alvos'] as $key) {
            if (isset($dinamica[$key])) {
                $dinamica[$key] = array_values(array_filter($dinamica[$key], $isValid));
            }
        }
        if (isset($dinamica['preenche'])) {
            $dinamica['preenche'] = array_values(array_filter($dinamica['preenche'], function ($fill) use ($isValid) {
                return $isValid($fill['alvo']);
            }));
        }
        foreach (['alvo', 'pessoa', 'documento'] as $key) {
            if (isset($dinamica[$key]) && !$isValid($dinamica[$key])) {
                $dinamica[$key] = 0;
            }
        }

        return $dinamica;
    }

    /**
     * Uma linha por opcao: "Rotulo|Retorno 1|Retorno 2|...|Retorno 5".
     *
     * As opcoes que ja existiam (mesmo rotulo) sao atualizadas, nao recriadas:
     * assim o codigo do legado (usado pelo tipo de pessoa) e os retornos 2 a 5
     * que a linha nao trouxer continuam valendo.
     */
    protected function syncOpcoes(PeticaoModeloCampo $campo, $opcoes, array $data = [])
    {
        $existing = PeticaoModeloCampoOpcao::where('campo_id', $campo->id)->orderBy('ordem')->get();

        if (!in_array($campo->input_tipo, ['SELECT', 'RADIO2'], true) || !empty($data['input_list_group_id'])) {
            PeticaoModeloCampoOpcao::where('campo_id', $campo->id)->delete();
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $opcoes);
        $order = 1;
        $kept = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $rotulo = $parts[0];
            $opcao = $existing->first(function ($item) use ($rotulo, $kept) {
                return !in_array($item->id, $kept, true) && trim((string) $item->rotulo) === $rotulo;
            });

            $extras = $opcao && is_array($opcao->valores_extras) ? $opcao->valores_extras : [];
            foreach ([2, 3, 4, 5] as $index => $number) {
                if (array_key_exists($number, $parts)) {
                    $extras['return_' . $number] = $parts[$number] !== '' ? $parts[$number] : null;
                }
            }

            $values = [
                'rotulo' => $rotulo,
                'valor_retorno' => $parts[1] ?? ($opcao ? $opcao->valor_retorno : $rotulo),
                'valores_extras' => $extras ?: null,
                'ordem' => $order,
            ];

            if ($opcao) {
                $opcao->fill($values)->save();
                $kept[] = $opcao->id;
            } else {
                $kept[] = PeticaoModeloCampoOpcao::create($values + ['campo_id' => $campo->id, 'legacy_dado_id' => null])->id;
            }

            $order++;
        }

        PeticaoModeloCampoOpcao::where('campo_id', $campo->id)->whereNotIn('id', $kept ?: [0])->delete();
    }

    protected function resolveWidth($cols)
    {
        if ($cols === 2) {
            return 560;
        }
        if ($cols === 3) {
            return 860;
        }

        return 265;
    }

    protected function resolveOrigemAlias(PeticaoModeloCampo $campo, array $data)
    {
        $returnColumns = $data['input_list_return_column'] ?? [];
        if (!is_array($returnColumns)) {
            $returnColumns = preg_split('/\s*[,;+|]\s*/', (string) $returnColumns, -1, PREG_SPLIT_NO_EMPTY);
        }
        $returnColumns = array_values(array_filter($returnColumns, function ($column) {
            return in_array($column, ['return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'], true);
        }));
        if (
            $campo->input_tipo === 'SELECT'
            && !empty($data['input_list_group_id'])
            && !empty($returnColumns)
        ) {
            return sprintf(
                'tp_lista_tb_|_nome_lista_|_%s_|_id_grupo=%d_|_vert',
                implode('+', $returnColumns),
                (int) $data['input_list_group_id']
            );
        }

        return $data['input_db'] ?? null;
    }

    protected function buildFrontendEvents(PeticaoModeloCampo $campo, array $data)
    {
        $returnColumns = $data['input_list_return_column'] ?? [];
        if (!is_array($returnColumns)) {
            $returnColumns = preg_split('/\s*[,;+|]\s*/', (string) $returnColumns, -1, PREG_SPLIT_NO_EMPTY);
        }
        $returnColumns = array_values(array_filter($returnColumns, function ($column) {
            return in_array($column, ['return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'], true);
        }));
        $behavior = $this->normalizeBehaviorValue($data['input_behavior'] ?? '');
        $focus = $this->sanitizeEventScriptForBehavior(
            $this->resolveEventScript($data, 'input_focu', 'input_focu_preset'),
            $behavior
        );
        $load = $this->sanitizeEventScriptForBehavior(
            $this->resolveEventScript($data, 'input_load', 'input_load_preset'),
            $behavior
        );
        $blur = $this->sanitizeEventScriptForBehavior(
            $this->resolveEventScript($data, 'input_blur', 'input_blur_preset'),
            $behavior
        );

        $dependentReturnColumn = count($returnColumns) === 1 ? $returnColumns[0] : null;
        if (
            $campo->input_tipo === 'SELECT'
            && !empty($data['input_list_target_field'])
            && $dependentReturnColumn
        ) {
            $generated = sprintf(
                'fc_ajax_comp("tp_lista_tb","%s","campo%d","unir","id_lista",this,1); mcampo("campo%d_|_campo%d"); $("#campo%d").focus();',
                $dependentReturnColumn,
                (int) $data['input_list_target_field'],
                (int) $campo->id_input,
                (int) $data['input_list_target_field'],
                (int) $data['input_list_target_field']
            );

            if ($focus === '') {
                $focus = $generated;
            }
            if ($load === '') {
                $load = $generated;
            }
            if ($blur === '') {
                $blur = $generated;
            }
        }

        $events = array_filter([
            'focus' => $focus !== '' ? $focus : null,
            'load' => $load !== '' ? $load : null,
            'blur' => $blur !== '' ? $blur : null,
        ]);

        $conditionalValue = trim((string) ($data['conditional_value'] ?? ''));
        $conditionalTargets = array_values(array_unique(array_map('intval', (array) ($data['conditional_targets'] ?? []))));
        $conditionalTargets = PeticaoModeloCampo::where('modelo_id', $campo->modelo_id)
            ->where(function ($query) use ($conditionalTargets) {
                $query->whereIn('legacy_input_id', $conditionalTargets)
                    ->orWhereIn('id', $conditionalTargets);
            })
            ->where('id', '<>', $campo->id)
            ->get(['id', 'legacy_input_id'])
            ->map(function (PeticaoModeloCampo $target) {
                return (int) $target->id_input;
            })
            ->values()
            ->all();

        if ($conditionalValue !== '' && !empty($conditionalTargets)) {
            $events['conditional'] = [
                'value' => $conditionalValue,
                'target_ids' => $conditionalTargets,
                'required' => (int) ($data['conditional_required'] ?? 0) === 1,
            ];
        }

        return $events;
    }

    protected function resolveEventScript(array $data, $inputKey, $presetKey)
    {
        $preset = trim((string) ($data[$presetKey] ?? ''));
        if ($preset !== '') {
            return $this->presetScript($preset);
        }

        return trim((string) ($data[$inputKey] ?? ''));
    }

    protected function presetScript($preset)
    {
        switch ($preset) {
            case 'data_extenso_out':
                return 'data_extenso_out(this);';
            case 'data_atual':
                return 'data_atual(this);';
            case 'dia_semana':
                return 'dia_semana(this);';
            case 'valor_extenso':
                return 'fc_newstring(this);';
            default:
                return '';
        }
    }

    protected function sanitizeEventScriptForBehavior($script, $behavior)
    {
        $script = trim((string) $script);
        if ($script === '') {
            return '';
        }

        // So descarta o que e incompativel com a mascara (data num campo de
        // valor e vice-versa). Campo sem mascara guarda os dois: no legado ha
        // campos de texto sem mascara com data_extenso_out, e salvar o campo
        // apagava o evento.
        if ($behavior === 'date') {
            return $this->isSupportedValueOnlyScript($script) ? '' : $script;
        }
        if ($behavior === 'decimal') {
            return $this->isSupportedDateOnlyScript($script) ? '' : $script;
        }

        return $script;
    }

    protected function isSupportedDateOnlyScript($script)
    {
        $normalized = strtolower(trim((string) $script));
        $normalized = preg_replace('/\s+/', '', $normalized);

        return in_array($normalized, [
            'data_atual(this);',
            'data_extenso_out(this);',
            'dia_semana(this);',
        ], true);
    }

    protected function isSupportedValueOnlyScript($script)
    {
        $normalized = strtolower(trim((string) $script));
        $normalized = preg_replace('/\s+/', '', $normalized);

        return $normalized === 'fc_newstring(this);';
    }

    protected function normalizeBehaviorValue($behavior)
    {
        $behavior = strtolower(trim((string) $behavior));

        $allowed = ['date', 'decimal', 'cpf', 'cnpj', 'cpf_cnpj', 'fone', 'cep', 'integer', 'processo'];

        return in_array($behavior, $allowed, true) ? $behavior : '';
    }
}
