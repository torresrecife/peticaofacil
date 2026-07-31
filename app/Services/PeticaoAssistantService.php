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
            $state = $this->handleProcessLookup($state, $message);

            return $this->applyAiAnalysis($state, $message);
        }

        if (!$state['selected_model_id']) {
            $state = $this->handleModelSelection($state, $message);

            return $this->applyAiAnalysis($state, $message);
        }

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
        $state['selected_model_id'] = $modelo->id;
        $state['selected_model_name'] = $modelo->nome;
        $state['model_suggestions'] = $this->serializeModelSuggestions(collect([$modelo]));

        $reply = 'Modelo selecionado: ' . $modelo->nome . '. ';
        $reply .= 'Agora voce pode abrir a montagem assistida para carregar o processo e continuar no formulario normal.';

        $state = $this->state->appendMessage($state, 'assistant', $reply);
        $state = $this->refreshSelectedModelAnalysis($state);

        return $this->applyAiAnalysis($state, $modelo->nome);
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
            $state['missing_fields'] = [];
            $state['consistency_checks'] = [];
            $state['model_rationale'] = null;
            $state['jurisprudencia_suggestions'] = [];
            $state['conflict_alerts'] = $this->conflicts->analyze($state);

            return $state;
        }

        $modelo = PeticaoModelo::with('campos')->find($state['selected_model_id']);
        if (!$modelo) {
            return $state;
        }

        $requiredCampos = $modelo->campos->where('obrigatorio', true);
        $missingFields = [];

        foreach ($requiredCampos as $campo) {
            $column = trim((string) $campo->origem_coluna);
            $label = $campo->rotulo;

            if ($column === '') {
                $missingFields[] = $label;
                continue;
            }

            $matched = false;
            foreach ($state['process_data'] as $key => $value) {
                if (strcasecmp((string) $key, $column) === 0 && trim((string) $value) !== '') {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $missingFields[] = $label;
            }
        }

        $checks = [];
        if (!empty($state['duplicate_petitions'])) {
            $checks[] = 'Encontradas peticoes anteriores potencialmente duplicadas para este processo.';
        }
        if (!empty($missingFields)) {
            $checks[] = 'Existem campos obrigatorios do modelo que nao vieram preenchidos pelo processo.';
        }

        $state['missing_fields'] = array_values(array_unique($missingFields));
        $state['consistency_checks'] = array_values(array_unique($checks));
        $state['duplicate_petitions'] = $this->detectDuplicates(
            $state['process_code'],
            $state['selected_model_id'],
            $state['process_data']
        );
        $state['model_rationale'] = 'Modelo selecionado para o processo atual. Revise os campos obrigatorios antes de abrir a montagem.';
        $state['jurisprudencia_suggestions'] = $this->jurisprudencia->suggest($state);
        $state['conflict_alerts'] = $this->conflicts->analyze($state);

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
}
