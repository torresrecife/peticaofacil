<?php

namespace App\Services;

class PeticaoComposerService
{
    public function compose($modelo, array $input)
    {
        $replacements = [];
        $resolved = [];
        $fileNameParts = [];

        foreach ($modelo->campos as $campo) {
            $fieldKey = 'campo_' . $campo->id_input;
            $rawValue = $input[$fieldKey] ?? '';
            $resolvedValue = $this->resolveCampoValue($campo, $rawValue);

            $resolved[$campo->id_input] = [
                'id' => $campo->id_input,
                'title' => $campo->input_title,
                'type' => $campo->input_tipo,
                'raw' => $rawValue,
                'value' => $resolvedValue,
                'placeholder' => $campo->placeholder,
            ];

            $replacements[(string) $campo->id_input] = $resolvedValue;

            if ($campo->nomepet === 'Y' && trim(strip_tags($resolvedValue)) !== '') {
                $fileNameParts[] = trim(strip_tags($resolvedValue));
            }
        }

        $sections = [];
        if ($modelo->cod_cabec) {
            $sections[] = $this->replacePlaceholders($modelo->cod_cabec, $replacements);
        }
        foreach ($modelo->paragrafos as $paragrafo) {
            $sections[] = $this->replacePlaceholders($paragrafo->fund_text, $replacements);
        }
        if ($modelo->cod_rodap) {
            $sections[] = $this->replacePlaceholders($modelo->cod_rodap, $replacements);
        }

        return [
            'html' => implode("\n", array_filter($sections)),
            'resolved_fields' => $resolved,
            'suggested_filename' => $fileNameParts ? implode('_', $fileNameParts) : 'peticao',
        ];
    }

    protected function resolveCampoValue($campo, $rawValue)
    {
        if ($campo->input_tipo === 'SELECT') {
            foreach ($campo->select_options as $option) {
                if ((string) $option['label'] === (string) $rawValue) {
                    return e($option['return']);
                }
            }
        }

        if ($campo->input_tipo === 'TEXTAREA') {
            return nl2br(e((string) $rawValue));
        }

        $prefix = $campo->input_pre ?: '';
        $suffix = $campo->input_pos ?: '';

        return e($prefix . (string) $rawValue . $suffix);
    }

    protected function replacePlaceholders($html, array $replacements)
    {
        return preg_replace_callback('/@campo(\d+)@/i', function ($matches) use ($replacements) {
            $id = $matches[1];

            return array_key_exists($id, $replacements) ? $replacements[$id] : $matches[0];
        }, (string) $html);
    }
}
