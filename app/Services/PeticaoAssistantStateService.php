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
            'process_code' => null,
            'sql_profile_id' => null,
            'process_data' => [],
            'selected_model_id' => null,
            'selected_model_name' => null,
            'model_suggestions' => [],
            'duplicate_petitions' => [],
        ];
    }
}
