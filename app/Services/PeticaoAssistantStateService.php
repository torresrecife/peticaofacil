<?php

namespace App\Services;

class PeticaoAssistantStateService
{
    const SESSION_KEY = 'peticao_assistant';

    public function current()
    {
        return session(self::SESSION_KEY, $this->fresh());
    }

    public function reset()
    {
        session()->forget(self::SESSION_KEY);

        return $this->fresh();
    }

    public function store(array $state)
    {
        session([self::SESSION_KEY => $state]);

        return $state;
    }

    public function appendMessage(array $state, $role, $content)
    {
        $state['messages'][] = [
            'role' => $role,
            'content' => trim((string) $content),
            'at' => now()->format('d/m/Y H:i'),
        ];

        return $state;
    }

    public function fresh()
    {
        return [
            'messages' => [[
                'role' => 'assistant',
                'content' => 'Informe o codigo do processo para eu consultar os dados e sugerir a peticao adequada.',
                'at' => now()->format('d/m/Y H:i'),
            ]],
            'conversation_stage' => 'process_lookup',
            'conversation_stage_label' => 'Consulta do processo',
            'process_code' => null,
            'sql_profile_id' => null,
            'process_data' => [],
            'selected_model_id' => null,
            'selected_model_name' => null,
            'model_suggestions' => [],
            'duplicate_petitions' => [],
            'assistant_field_answers' => [],
            'pending_fields' => [],
            'current_pending_field' => null,
            'missing_fields' => [],
            'consistency_checks' => [],
            'model_rationale' => null,
            'assistant_mode' => 'local',
            'assistant_warnings' => [],
            'assistant_questions' => [],
            'assistant_stage_guidance' => null,
            'jurisprudencia_suggestions' => [],
            'conflict_alerts' => [],
        ];
    }
}
