<?php

namespace App\Services;

use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\SqlServerProfile;
use Illuminate\Support\Str;

class PeticaoAssistantService
{
    protected $lookup;
    protected $state;
    protected $assistantAi;
    protected $jurisprudencia;
    protected $conflicts;

    public function __construct(
        SqlServerLookupService $lookup,
        PeticaoAssistantStateService $state,
        PeticaoAssistantAiService $assistantAi,
        JurisprudenciaSuggestionService $jurisprudencia,
        PeticaoAssistantConflictService $conflicts
    ) {
        $this->lookup = $lookup;
        $this->state = $state;
        $this->assistantAi = $assistantAi;
        $this->jurisprudencia = $jurisprudencia;
        $this->conflicts = $conflicts;
    }

    public function processMessage(array $state, $message)
    {
        $message = trim((string) $message);
        $state = $this->state->appendMessage($state, 'user', $message);

        if (!$state['process_code']) {
            $state = $this->setConversationStage($state, 'process_lookup');
            $state = $this->handleProcessLookup($state, $message);

            return $this->applyAiAnalysis($state, $message);
        }

        if (!$state['selected_model_id']) {
            $state = $this->setConversationStage($state, 'model_selection');
            $state = $this->handleModelSelection($state, $message);

            return $this->applyAiAnalysis($state, $message);
        }

        if (($state['conversation_stage'] ?? null) === 'data_completion' && !empty($state['current_pending_field'])) {
            $state = $this->capturePendingFieldAnswer($state, $message);

            return $this->applyAiAnalysis($state, $message);
        }

        $state = $this->setConversationStage($state, 'data_completion');
        $state = $this->state->appendMessage(
            $state,
            'assistant',
            'Processo e modelo ja estao definidos. Use o botao de abertura da montagem assistida para continuar no formulario.'
        );

        $state = $this->refreshSelectedModelAnalysis($state);

        return $this->applyAiAnalysis($state, $message);
    }

    public function selectModel(array $state, PeticaoModelo $modelo)
    {
        $state = $this->setConversationStage($state, 'data_completion');
        $state['selected_model_id'] = $modelo->id;
        $state['selected_model_name'] = $modelo->nome;
        $state['model_suggestions'] = $this->serializeModelSuggestions(collect([$modelo]));

        $reply = 'Modelo selecionado: ' . $modelo->nome . '. ';
        $reply .= 'Agora voce pode abrir a montagem assistida para carregar o processo e continuar no formulario normal.';

        $state = $this->state->appendMessage($state, 'assistant', $reply);
        $state = $this->refreshSelectedModelAnalysis($state);

        return $this->applyAiAnalysis($state, $modelo->nome);
    }

    public function answerCurrentField(array $state, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $this->state->appendMessage($state, 'assistant', 'Envie um valor para o campo em coleta.');
        }

        $state = $this->state->appendMessage($state, 'user', $value);

        if (empty($state['current_pending_field'])) {
            return $this->applyAiAnalysis($state, $value);
        }

        $state = $this->capturePendingFieldAnswer($state, $value);

        return $this->applyAiAnalysis($state, $value);
    }

    protected function handleProcessLookup(array $state, $message)
    {
        $code = $this->extractProcessCode($message);
        if ($code === '') {
            return $this->state->appendMessage(
                $state,
                'assistant',
                'Nao identifiquei um codigo de processo valido. Envie o numero ou codigo exato do processo.'
            );
        }

        $match = $this->lookupProcessAcrossProfiles($code);
        if (!$match['found']) {
            $reply = $match['error'] ?: 'Nao localizei o processo informado em nenhum servidor SQL ativo.';

            return $this->state->appendMessage($state, 'assistant', $reply);
        }

        $state['process_code'] = $code;
        $state['sql_profile_id'] = $match['profile']->id;
        $state['process_data'] = $match['data'];
        $state['duplicate_petitions'] = $this->detectDuplicates($code);
        $state = $this->setConversationStage($state, 'model_selection');

        $suggestions = $this->suggestModels();
        $state['model_suggestions'] = $this->serializeModelSuggestions($suggestions);

        $summary = $this->summarizeProcessData($match['data']);
        $reply = 'Encontrei o processo `' . $code . '` no banco `' . $match['profile']->nome . '`. ';
        $reply .= 'Resumo: ' . $summary . '. ';

        if (!empty($state['duplicate_petitions'])) {
            $reply .= 'Atencao: ja existem ' . count($state['duplicate_petitions']) . ' peticao(oes) salva(s) com esse codigo no historico. ';
        }

        $reply .= 'Agora me diga qual peticao voce quer elaborar, ou escolha uma das sugestoes abaixo.';

        return $this->state->appendMessage($state, 'assistant', $reply);
    }

    protected function handleModelSelection(array $state, $message)
    {
        $modelo = $this->matchModel($message);
        if (!$modelo) {
            $suggestions = $this->suggestModels($message);
            $state['model_suggestions'] = $this->serializeModelSuggestions($suggestions);

            if ($suggestions->isEmpty()) {
                return $this->state->appendMessage(
                    $state,
                    'assistant',
                    'Nao encontrei um modelo compativel com essa descricao. Tente informar o nome da peticao com mais precisao.'
                );
            }

            return $this->state->appendMessage(
                $state,
                'assistant',
                'Encontrei alguns modelos compativeis. Escolha um deles para continuar na montagem assistida.'
            );
        }

        return $this->selectModel($state, $modelo);
    }

    protected function lookupProcessAcrossProfiles($code)
    {
        $profiles = SqlServerProfile::active()->orderBy('id')->get();
        $connectionErrors = [];

        foreach ($profiles as $profile) {
            $status = $this->lookup->connectionStatus($profile);
            if (!($status['available'] ?? false)) {
                $connectionErrors[] = $status['message'] ?? ('Falha de conexao em ' . $profile->nome . '.');
                continue;
            }

            $data = $this->lookup->fetchByCode($profile, $code);
            if (is_array($data) && !empty($data)) {
                return [
                    'found' => true,
                    'profile' => $profile,
                    'data' => $data,
                    'error' => null,
                ];
            }
        }

        return [
            'found' => false,
            'profile' => null,
            'data' => null,
            'error' => !empty($connectionErrors)
                ? implode(' ', array_unique($connectionErrors))
                : 'Nao localizei o processo informado em nenhum servidor SQL ativo.',
        ];
    }

    protected function detectDuplicates($code, $selectedModelId = null, array $processData = [])
    {
        return PeticaoNormalizada::with('modelo')
            ->where(function ($builder) use ($code, $selectedModelId, $processData) {
                $builder->where('codigo_externo', $code);

                $mainName = $this->extractMainPartyName($processData);
                if ($mainName !== '') {
                    $builder->orWhere('cliente_referencia', 'like', '%' . $mainName . '%');
                }

                if ($selectedModelId) {
                    $builder->orWhere(function ($inner) use ($code, $selectedModelId) {
                        $inner->where('modelo_id', $selectedModelId)
                            ->where('codigo_externo', $code);
                    });
                }
            })
            ->orderByDesc('salvo_em')
            ->limit(8)
            ->get()
            ->map(function ($peticao) use ($code, $selectedModelId) {
                $reasons = [];
                if ((string) $peticao->codigo_externo === (string) $code) {
                    $reasons[] = 'Mesmo codigo de processo';
                }
                if ($selectedModelId && (int) $peticao->modelo_id === (int) $selectedModelId) {
                    $reasons[] = 'Mesmo modelo';
                }

                return [
                    'id' => $peticao->id,
                    'cliente' => $peticao->cliente_referencia,
                    'modelo' => optional($peticao->modelo)->nome,
                    'salvo_em' => optional($peticao->salvo_em)->format('d/m/Y H:i'),
                    'reasons' => $reasons,
                ];
            })
            ->values()
            ->all();
    }

    protected function suggestModels($message = '')
    {
        $message = trim((string) $message);

        return PeticaoModelo::query()
            ->where('status', 'ativo')
            ->when($message !== '', function ($query) use ($message) {
                $query->where(function ($builder) use ($message) {
                    $builder->where('nome', 'like', '%' . $message . '%')
                        ->orWhere('slug', 'like', '%' . Str::slug($message) . '%')
                        ->orWhere('legacy_tipo_id', 'like', '%' . $message . '%');
                });
            })
            ->orderBy('nome')
            ->limit(8)
            ->get(['id', 'nome', 'slug', 'legacy_tipo_id']);
    }

    protected function matchModel($message)
    {
        $message = trim((string) $message);
        if ($message === '') {
            return null;
        }

        if (ctype_digit($message)) {
            return PeticaoModelo::where('status', 'ativo')
                ->where(function ($query) use ($message) {
                    $query->where('id', (int) $message)
                        ->orWhere('legacy_tipo_id', (int) $message);
                })
                ->first();
        }

        return PeticaoModelo::where('status', 'ativo')
            ->where(function ($query) use ($message) {
                $query->where('nome', 'like', '%' . $message . '%')
                    ->orWhere('slug', 'like', '%' . Str::slug($message) . '%');
            })
            ->orderByRaw('CASE WHEN nome = ? THEN 0 ELSE 1 END', [$message])
            ->orderBy('nome')
            ->first();
    }

    protected function serializeModelSuggestions($suggestions)
    {
        return $suggestions->map(function ($modelo) {
            return [
                'id' => $modelo->id,
                'nome' => $modelo->nome,
                'slug' => $modelo->slug,
                'legacy_tipo_id' => $modelo->legacy_tipo_id,
            ];
        })->values()->all();
    }

    protected function summarizeProcessData(array $data)
    {
        $priorityColumns = [
            'NOME', 'NOME_AUTOR', 'AUTOR', 'CLIENTE', 'PARTE', 'REU', 'CPF', 'CNPJ',
            'PROCESSO', 'NUMERO', 'COMARCA', 'VARA', 'CIDADE', 'ENDERECO',
        ];

        $parts = [];

        foreach ($priorityColumns as $column) {
            foreach ($data as $key => $value) {
                if (count($parts) >= 4) {
                    break 2;
                }

                if (stripos((string) $key, $column) === false) {
                    continue;
                }

                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $parts[] = $key . ': ' . $value;
            }
        }

        if (empty($parts)) {
            foreach ($data as $key => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $parts[] = $key . ': ' . $value;
                if (count($parts) >= 4) {
                    break;
                }
            }
        }

        return !empty($parts) ? implode(' | ', $parts) : 'dados recebidos do processo';
    }

    protected function extractProcessCode($message)
    {
        if (preg_match('/([A-Za-z0-9\\.\\-\\/]{4,})/', $message, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    protected function refreshSelectedModelAnalysis(array $state)
    {
        if (empty($state['selected_model_id'])) {
            $state['assistant_field_answers'] = [];
            $state['pending_fields'] = [];
            $state['current_pending_field'] = null;
            $state['missing_fields'] = [];
            $state['consistency_checks'] = [];
            $state['model_rationale'] = null;
            $state['jurisprudencia_suggestions'] = [];
            $state['conflict_alerts'] = $this->conflicts->analyze($state);
            $state = $this->setConversationStage($state, !empty($state['process_code']) ? 'model_selection' : 'process_lookup');

            return $state;
        }

        $modelo = PeticaoModelo::with('campos')->find($state['selected_model_id']);
        if (!$modelo) {
            return $state;
        }

        $orderedCampos = $modelo->campos->sortBy(function ($campo) {
            return [(int) $campo->input_order, (int) $campo->id_input];
        })->values();

        $fieldContexts = $this->buildFieldContexts($orderedCampos);
        $assistantAnswers = $this->applyDeterministicFieldValues(
            $modelo,
            $orderedCampos,
            $fieldContexts,
            $state['assistant_field_answers'] ?? [],
            $state['process_data'] ?? []
        );
        $requiredCampos = $orderedCampos->where('obrigatorio', true)->filter(function ($campo) {
            return strtoupper((string) $campo->input_tipo) !== 'TITLE';
        });
        $missingFields = [];
        $pendingFields = [];

        foreach ($requiredCampos as $campo) {
            $label = $campo->rotulo;
            if (!$this->campoHasResolvedValue($campo, $assistantAnswers, $state['process_data'] ?? [])) {
                $missingFields[] = $label;
                $pendingFields[] = $this->buildPendingFieldDescriptor($campo, $modelo, $fieldContexts);
            }
        }

        $pendingFields = collect($pendingFields)->sortBy(function ($field) {
            return [
                (int) ($field['group_order'] ?? 9999),
                (int) ($field['field_order'] ?? 9999),
                (int) ($field['field_id'] ?? 999999),
            ];
        })->values()->all();

        $checks = [];
        if (!empty($state['duplicate_petitions'])) {
            $checks[] = 'Encontradas peticoes anteriores potencialmente duplicadas para este processo.';
        }
        if (!empty($missingFields)) {
            $checks[] = 'Existem campos obrigatorios do modelo que nao vieram preenchidos pelo processo.';
        }

        $state['missing_fields'] = array_values(array_unique($missingFields));
        $state['pending_fields'] = $pendingFields;
        $state['current_pending_field'] = $pendingFields[0] ?? null;
        $state['assistant_field_answers'] = $assistantAnswers;
        $state['consistency_checks'] = array_values(array_unique($checks));
        $state['duplicate_petitions'] = $this->detectDuplicates(
            $state['process_code'],
            $state['selected_model_id'],
            $state['process_data']
        );
        $state['model_rationale'] = 'Modelo selecionado para o processo atual. Revise os campos obrigatorios antes de abrir a montagem.';
        $state['jurisprudencia_suggestions'] = $this->jurisprudencia->suggest($state);
        $state['conflict_alerts'] = $this->conflicts->analyze($state);
        $state = $this->setConversationStage($state, !empty($state['missing_fields']) ? 'data_completion' : 'ready_for_handoff');

        return $state;
    }

    protected function applyAiAnalysis(array $state, $lastUserMessage)
    {
        if (empty($state['process_code'])) {
            return $state;
        }

        $analysis = $this->assistantAi->enrich($state, $lastUserMessage);

        $state['assistant_mode'] = $analysis['mode'];
        $state['assistant_warnings'] = $analysis['warnings'];
        $state['assistant_questions'] = $analysis['questions'];
        $state['assistant_stage_guidance'] = $analysis['stage_guidance'] ?? null;
        $state['missing_fields'] = array_values(array_unique(array_merge(
            $state['missing_fields'] ?? [],
            $analysis['missing_fields']
        )));
        $state['consistency_checks'] = array_values(array_unique(array_merge(
            $state['consistency_checks'] ?? [],
            $analysis['consistency_checks'],
            $state['conflict_alerts'] ?? []
        )));
        $state['model_rationale'] = $analysis['model_rationale'] ?: ($state['model_rationale'] ?? null);

        if ($analysis['assistant_message'] !== '') {
            $state = $this->replaceLastAssistantMessage($state, $analysis['assistant_message']);
        }

        if (!empty($analysis['questions'])) {
            $state = $this->state->appendMessage(
                $state,
                'assistant',
                'Perguntas objetivas: ' . implode(' | ', $analysis['questions'])
            );
        }

        $state = $this->setConversationStageFromState($state);

        return $state;
    }

    protected function replaceLastAssistantMessage(array $state, $content)
    {
        for ($index = count($state['messages']) - 1; $index >= 0; $index--) {
            if (($state['messages'][$index]['role'] ?? null) !== 'assistant') {
                continue;
            }

            $state['messages'][$index]['content'] = trim((string) $content);

            return $state;
        }

        return $this->state->appendMessage($state, 'assistant', $content);
    }

    protected function extractMainPartyName(array $processData)
    {
        foreach ($processData as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $key = (string) $key;
            if (stripos($key, 'AUTOR') !== false || stripos($key, 'CLIENTE') !== false || stripos($key, 'NOME') !== false) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    protected function capturePendingFieldAnswer(array $state, $message)
    {
        $currentField = $state['current_pending_field'] ?? null;
        if (!$currentField) {
            return $state;
        }

        $normalized = $this->normalizePendingFieldAnswer($currentField, $message);
        if (!$normalized['ok']) {
            return $this->state->appendMessage($state, 'assistant', $normalized['message']);
        }

        $state['assistant_field_answers'][$currentField['field_key']] = [
            'field_id' => $currentField['field_id'],
            'label' => $currentField['label'],
            'type' => $currentField['type'],
            'value' => $normalized['value'],
        ];

        if (!empty($normalized['dependent_fill'])) {
            $dependent = $normalized['dependent_fill'];
            $state['assistant_field_answers'][$dependent['field_key']] = [
                'field_id' => $dependent['field_id'],
                'label' => $dependent['label'],
                'type' => $dependent['type'],
                'value' => $dependent['value'],
            ];
        }

        $state = $this->state->appendMessage(
            $state,
            'assistant',
            'Campo confirmado: ' . $currentField['label'] . ' = ' . $normalized['value'] . '.'
        );

        if (!empty($normalized['dependent_fill'])) {
            $dependent = $normalized['dependent_fill'];
            $state = $this->state->appendMessage(
                $state,
                'assistant',
                'Preenchimento automatico aplicado: ' . $dependent['label'] . ' = ' . $dependent['value'] . '.'
            );
        }

        return $this->refreshSelectedModelAnalysis($state);
    }

    protected function buildPendingFieldDescriptor($campo, $modelo, array $fieldContexts)
    {
        $options = $this->buildPendingFieldOptions($campo);
        $dependentFill = $campo->dependent_fill_config;
        $dependentTarget = null;
        $context = $fieldContexts[$campo->id_input] ?? [
            'group_label' => null,
            'group_order' => 9999,
            'field_order' => 9999,
        ];

        if ($dependentFill && !empty($dependentFill['target_field_id'])) {
            $targetCampo = $modelo->campos->first(function ($item) use ($dependentFill) {
                return (int) $item->id_input === (int) $dependentFill['target_field_id'];
            });

            if ($targetCampo) {
                $dependentTarget = [
                    'field_id' => $targetCampo->id_input,
                    'field_key' => 'campo_' . $targetCampo->id_input,
                    'label' => $targetCampo->input_title,
                    'type' => strtoupper((string) $targetCampo->input_tipo),
                    'return_column' => $dependentFill['return_column'],
                ];
            }
        }

        return [
            'field_id' => $campo->id_input,
            'field_key' => 'campo_' . $campo->id_input,
            'label' => $campo->input_title,
            'type' => strtoupper((string) $campo->input_tipo),
            'group_label' => $context['group_label'],
            'group_order' => $context['group_order'],
            'field_order' => $context['field_order'],
            'options' => $options,
            'is_searchable' => strtoupper((string) $campo->input_tipo) === 'SELECT' && count($options) > 6,
            'dependent_target' => $dependentTarget,
        ];
    }

    protected function buildPendingFieldOptions($campo)
    {
        if (strtoupper((string) $campo->input_tipo) !== 'SELECT') {
            return [];
        }

        return collect($campo->select_options)
            ->map(function ($option, $index) {
                $extras = $option['extras'] ?? [];
                $helper = '';
                if (!empty($extras['return_1']) && (string) $extras['return_1'] !== (string) ($option['label'] ?? '')) {
                    $helper = (string) $extras['return_1'];
                }

                return [
                    'index' => $index + 1,
                    'label' => (string) ($option['label'] ?? ''),
                    'value' => (string) ($option['value'] ?? $option['label'] ?? ''),
                    'helper' => $helper,
                    'extras' => $extras,
                ];
            })
            ->filter(function ($option) {
                return $option['label'] !== '';
            })
            ->values()
            ->all();
    }

    protected function normalizePendingFieldAnswer(array $field, $message)
    {
        $message = trim((string) $message);
        if ($message === '') {
            return [
                'ok' => false,
                'message' => 'Envie um valor para o campo `' . $field['label'] . '`.',
            ];
        }

        if ($field['type'] !== 'SELECT') {
            return [
                'ok' => true,
                'value' => $message,
            ];
        }

        $directMatch = $this->matchPendingSelectOption($field, $message);
        if ($directMatch) {
            return $directMatch;
        }

        $normalizedMessage = mb_strtolower($message);
        $candidates = collect($field['options'])->filter(function ($option) use ($normalizedMessage) {
            $haystack = mb_strtolower(trim(implode(' ', array_filter([
                $option['label'] ?? '',
                $option['helper'] ?? '',
                $option['value'] ?? '',
            ]))));

            return $haystack !== '' && mb_strpos($haystack, $normalizedMessage) !== false;
        })->values();

        if ($candidates->count() === 1) {
            return $this->buildPendingSelectSuccess($field, $candidates->first());
        }

        if ($candidates->count() > 1) {
            $optionsText = $candidates->take(5)->map(function ($option) {
                return $option['index'] . '. ' . $option['label'] . (!empty($option['helper']) ? ' - ' . $option['helper'] : '');
            })->implode(' | ');

            return [
                'ok' => false,
                'message' => 'Encontrei mais de uma opcao para `' . $field['label'] . '`. Seja mais especifico ou escolha uma destas: ' . $optionsText . '.',
            ];
        }

        $optionsText = collect($field['options'])->map(function ($option) {
            return $option['index'] . '. ' . $option['label'] . (!empty($option['helper']) ? ' - ' . $option['helper'] : '');
        })->implode(' | ');

        return [
            'ok' => false,
            'message' => 'Opcao invalida para `' . $field['label'] . '`. Use uma destas: ' . $optionsText . '.',
        ];
    }

    protected function matchPendingSelectOption(array $field, $message)
    {
        foreach ($field['options'] as $option) {
            if ((string) $option['index'] === $message) {
                return $this->buildPendingSelectSuccess($field, $option);
            }

            $variants = [
                mb_strtolower((string) ($option['label'] ?? '')),
                mb_strtolower((string) ($option['value'] ?? '')),
                mb_strtolower((string) ($option['helper'] ?? '')),
            ];

            if (in_array(mb_strtolower($message), array_filter($variants), true)) {
                return $this->buildPendingSelectSuccess($field, $option);
            }
        }

        return null;
    }

    protected function buildPendingSelectSuccess(array $field, array $option)
    {
        $dependentFill = null;
        if (!empty($field['dependent_target'])) {
            $returnColumn = $field['dependent_target']['return_column'] ?? null;
            $returnValue = $returnColumn ? ($option['extras'][$returnColumn] ?? null) : null;

            if ($returnValue !== null && $returnValue !== '') {
                $dependentFill = [
                    'field_id' => $field['dependent_target']['field_id'],
                    'field_key' => $field['dependent_target']['field_key'],
                    'label' => $field['dependent_target']['label'],
                    'type' => $field['dependent_target']['type'],
                    'value' => (string) $returnValue,
                ];
            }
        }

        return [
            'ok' => true,
            'value' => $option['value'],
            'dependent_fill' => $dependentFill,
        ];
    }

    protected function buildFieldContexts($orderedCampos)
    {
        $contexts = [];
        $currentGroupLabel = null;
        $currentGroupOrder = 0;
        $hasExplicitGroups = false;

        foreach ($orderedCampos->values() as $index => $campo) {
            if (strtoupper((string) $campo->input_tipo) === 'TITLE') {
                $hasExplicitGroups = true;
                $currentGroupOrder++;
                $currentGroupLabel = trim((string) $campo->input_title) ?: ('Bloco ' . $currentGroupOrder);
                continue;
            }

            $contexts[$campo->id_input] = [
                'group_label' => $currentGroupLabel,
                'group_order' => $currentGroupLabel !== null ? $currentGroupOrder : 0,
                'field_order' => $index,
            ];
        }

        if ($hasExplicitGroups) {
            $fallbackOrder = $currentGroupOrder + 1;
            foreach ($contexts as $fieldId => $context) {
                if ((int) $context['group_order'] !== 0) {
                    continue;
                }

                $contexts[$fieldId]['group_order'] = $fallbackOrder;
            }
        }

        return $contexts;
    }

    protected function applyDeterministicFieldValues($modelo, $orderedCampos, array $fieldContexts, array $assistantAnswers, array $processData)
    {
        $updated = true;

        while ($updated) {
            $updated = false;

            foreach ($orderedCampos as $campo) {
                if (strtoupper((string) $campo->input_tipo) === 'TITLE') {
                    continue;
                }

                if ($this->campoHasResolvedValue($campo, $assistantAnswers, $processData)) {
                    continue;
                }

                $derived = $this->deriveDeterministicFieldAnswer($campo, $modelo, $fieldContexts);
                if (!$derived) {
                    continue;
                }

                $fieldKey = 'campo_' . $campo->id_input;
                $assistantAnswers[$fieldKey] = [
                    'field_id' => $campo->id_input,
                    'label' => $campo->input_title,
                    'type' => strtoupper((string) $campo->input_tipo),
                    'value' => $derived['value'],
                    'source' => 'derived',
                ];

                if (!empty($derived['dependent_fill'])) {
                    $dependent = $derived['dependent_fill'];
                    if (trim((string) ($assistantAnswers[$dependent['field_key']]['value'] ?? '')) === '') {
                        $assistantAnswers[$dependent['field_key']] = [
                            'field_id' => $dependent['field_id'],
                            'label' => $dependent['label'],
                            'type' => $dependent['type'],
                            'value' => $dependent['value'],
                            'source' => 'derived',
                        ];
                    }
                }

                $updated = true;
            }
        }

        return $assistantAnswers;
    }

    protected function deriveDeterministicFieldAnswer($campo, $modelo, array $fieldContexts)
    {
        $defaultValue = trim((string) $campo->texto_padrao);
        if ($defaultValue !== '') {
            return [
                'value' => $defaultValue,
                'dependent_fill' => null,
            ];
        }

        if (strtoupper((string) $campo->input_tipo) !== 'SELECT') {
            return null;
        }

        $descriptor = $this->buildPendingFieldDescriptor($campo, $modelo, $fieldContexts);
        if (count($descriptor['options']) !== 1) {
            return null;
        }

        return $this->buildPendingSelectSuccess($descriptor, $descriptor['options'][0]);
    }

    protected function campoHasResolvedValue($campo, array $assistantAnswers, array $processData)
    {
        $fieldKey = 'campo_' . $campo->id_input;
        $assistantValue = trim((string) ($assistantAnswers[$fieldKey]['value'] ?? ''));
        if ($assistantValue !== '') {
            return true;
        }

        return $this->campoHasProcessValue($campo, $processData);
    }

    protected function campoHasProcessValue($campo, array $processData)
    {
        $column = trim((string) $campo->origem_coluna);
        if ($column === '') {
            return false;
        }

        foreach ($processData as $key => $value) {
            if (strcasecmp((string) $key, $column) === 0 && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function setConversationStage(array $state, $stage)
    {
        $state['conversation_stage'] = $stage;
        $state['conversation_stage_label'] = $this->conversationStageLabel($stage);

        return $state;
    }

    protected function setConversationStageFromState(array $state)
    {
        if (empty($state['process_code'])) {
            return $this->setConversationStage($state, 'process_lookup');
        }

        if (empty($state['selected_model_id'])) {
            return $this->setConversationStage($state, 'model_selection');
        }

        if (!empty($state['missing_fields'])) {
            return $this->setConversationStage($state, 'data_completion');
        }

        return $this->setConversationStage($state, 'ready_for_handoff');
    }

    protected function conversationStageLabel($stage)
    {
        switch ($stage) {
            case 'model_selection':
                return 'Escolha do modelo';
            case 'data_completion':
                return 'Confirmacao dos dados faltantes';
            case 'ready_for_handoff':
                return 'Pronto para abrir a montagem';
            case 'process_lookup':
            default:
                return 'Consulta do processo';
        }
    }
}
