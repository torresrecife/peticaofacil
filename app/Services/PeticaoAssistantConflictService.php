<?php

namespace App\Services;

use App\PeticaoNormalizada;

class PeticaoAssistantConflictService
{
    public function analyze(array $state)
    {
        if (empty($state['process_code'])) {
            return [];
        }

        $alerts = [];
        $processCode = $state['process_code'];
        $selectedModelId = $state['selected_model_id'] ?? null;
        $mainParty = $this->extractMainPartyName($state['process_data'] ?? []);

        $sameProcessCount = PeticaoNormalizada::where('codigo_externo', $processCode)->count();
        if ($sameProcessCount > 0) {
            $alerts[] = 'Ja existem ' . $sameProcessCount . ' peticao(oes) com o mesmo codigo de processo.';
        }

        if ($selectedModelId) {
            $sameProcessSameModel = PeticaoNormalizada::where('codigo_externo', $processCode)
                ->where('modelo_id', $selectedModelId)
                ->count();

            if ($sameProcessSameModel > 0) {
                $alerts[] = 'Ja existe peticao com o mesmo processo e o mesmo modelo.';
            }
        }

        if ($mainParty !== '' && $selectedModelId) {
            $sameClientSameModelRecent = PeticaoNormalizada::where('cliente_referencia', 'like', '%' . $mainParty . '%')
                ->where('modelo_id', $selectedModelId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            if ($sameClientSameModelRecent > 0) {
                $alerts[] = 'O mesmo cliente ja recebeu este modelo nos ultimos 30 dias.';
            }
        }

        return array_values(array_unique($alerts));
    }

    protected function extractMainPartyName(array $processData)
    {
        foreach ($processData as $key => $value) {
            $key = (string) $key;
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            if (stripos($key, 'AUTOR') !== false || stripos($key, 'CLIENTE') !== false || stripos($key, 'NOME') !== false) {
                return $value;
            }
        }

        return '';
    }
}
