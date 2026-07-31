<?php

namespace App\Services;

class JurisprudenciaSuggestionService
{
    public function suggest(array $state)
    {
        $terms = $this->buildTerms($state);
        if (empty($terms)) {
            return [];
        }

        $queryLabel = implode(' | ', $terms);

        return [
            [
                'fonte' => 'STF - Pesquisa de Jurisprudencia',
                'url' => 'https://portal.stf.jus.br/jurisprudencia/',
                'termos' => $queryLabel,
                'observacao' => 'Use estes termos na pesquisa oficial do STF.',
            ],
            [
                'fonte' => 'STJ - Pesquisa de Jurisprudencia',
                'url' => 'https://www.stj.jus.br/sites/portalp/paginas/Sob-medida/Advogado/Jurisprudencia/Pesquisa-de-Jurisprudencia.aspx',
                'termos' => $queryLabel,
                'observacao' => 'Use estes termos na pesquisa oficial do STJ.',
            ],
        ];
    }

    protected function buildTerms(array $state)
    {
        $terms = [];

        if (!empty($state['selected_model_name'])) {
            $terms[] = $state['selected_model_name'];
        }

        foreach ($state['process_data'] ?? [] as $key => $value) {
            $key = (string) $key;
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if (stripos($key, 'COMARCA') !== false || stripos($key, 'VARA') !== false) {
                $terms[] = $value;
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }
}
