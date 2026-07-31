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

    public function __construct(SqlServerLookupService $lookup, PeticaoAssistantStateService $state)
    {
        $this->lookup = $lookup;
        $this->state = $state;
    }

    public function processMessage(array $state, $message)
    {
        $message = trim((string) $message);
        $state = $this->state->appendMessage($state, 'user', $message);

        if (!$state['process_code']) {
            return $this->handleProcessLookup($state, $message);
        }

        if (!$state['selected_model_id']) {
            return $this->handleModelSelection($state, $message);
        }

        $state = $this->state->appendMessage(
            $state,
            'assistant',
            'Processo e modelo ja estao definidos. Use o botao de abertura da montagem assistida para continuar no formulario.'
        );

        return $state;
    }

    public function selectModel(array $state, PeticaoModelo $modelo)
    {
        $state['selected_model_id'] = $modelo->id;
        $state['selected_model_name'] = $modelo->nome;
        $state['model_suggestions'] = $this->serializeModelSuggestions(collect([$modelo]));

        $reply = 'Modelo selecionado: ' . $modelo->nome . '. ';
        $reply .= 'Agora voce pode abrir a montagem assistida para carregar o processo e continuar no formulario normal.';

        return $this->state->appendMessage($state, 'assistant', $reply);
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

    protected function detectDuplicates($code)
    {
        return PeticaoNormalizada::with('modelo')
            ->where('codigo_externo', $code)
            ->orderByDesc('salvo_em')
            ->limit(5)
            ->get()
            ->map(function ($peticao) {
                return [
                    'id' => $peticao->id,
                    'cliente' => $peticao->cliente_referencia,
                    'modelo' => optional($peticao->modelo)->nome,
                    'salvo_em' => optional($peticao->salvo_em)->format('d/m/Y H:i'),
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
}
