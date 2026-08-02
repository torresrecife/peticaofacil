<?php

namespace App\Services;

use App\PeticaoModelo;

class PeticaoAvulsaTemplateService
{
    public function resolveSystemModel()
    {
        return PeticaoModelo::firstOrCreate(
            ['slug' => '__peticao-avulsa__'],
            [
                'nome' => 'Peticao avulsa',
                'status' => 'ativo',
                'arquivo_padrao' => 'doc',
                'cabecalho_html' => null,
                'rodape_html' => null,
                'metadata' => [
                    'system' => 'avulsa',
                ],
            ]
        );
    }

    public function composeInitialHtml(array $data)
    {
        $modelo = $this->resolveSystemModel();
        $header = $this->replacePlaceholders((string) $modelo->cabecalho_html, $data);
        $footer = $this->replacePlaceholders((string) $modelo->rodape_html, $data);

        $sections = array_values(array_filter([
            trim($header) !== '' ? $header : null,
            '<p></p><p></p><p></p>',
            trim($footer) !== '' ? $footer : null,
        ]));

        return implode("\n", $sections);
    }

    protected function replacePlaceholders($html, array $data)
    {
        $replacements = [
            '@TIPO_PETICAO@' => $data['tipo_peticao'] ?? '',
            '@PARTE_CONTRARIA@' => $data['parte_contraria'] ?? '',
            '@CODIGO_PROCESSO@' => $data['codigo_processo'] ?? '',
            '@DATA_ATUAL@' => now()->format('d/m/Y'),
        ];

        return str_ireplace(array_keys($replacements), array_values($replacements), (string) $html);
    }
}
