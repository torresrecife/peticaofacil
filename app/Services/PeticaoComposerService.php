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

        $composedHtml = implode("\n", array_filter($sections));
        // Resolve tokens that may exist inside a predefined TEXTAREA template.
        $composedHtml = $this->replacePlaceholders($composedHtml, $replacements);

        return [
            'html' => $composedHtml,
            'resolved_fields' => $resolved,
            'suggested_filename' => $fileNameParts ? implode('_', $fileNameParts) : 'peticao',
        ];
    }

    protected function resolveCampoValue($campo, $rawValue)
    {
        if (in_array($campo->input_tipo, ['SELECT', 'RADIO2'], true)) {
            if ($campo->hasAssociatedListSource()) {
                return e((string) $rawValue);
            }

            foreach ($campo->select_options as $option) {
                $value = $option['value'] ?? $option['label'];
                if ((string) $value === (string) $rawValue) {
                    return e($option['return']);
                }
            }
        }

        if ($campo->input_tipo === 'TEXTAREA') {
            $rawValue = (string) $rawValue;
            if ($rawValue !== strip_tags($rawValue)) {
                return $this->sanitizeRichText($rawValue);
            }

            return nl2br(e($rawValue));
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

    protected function sanitizeRichText($html)
    {
        $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6><table><thead><tbody><tfoot><tr><td><th><a><span><div><hr><sup><sub>';
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $html);
        $html = strip_tags($html, $allowedTags);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*"[^"]*"/iu', '', $html);
        $html = preg_replace("/\\s+on[a-z]+\\s*=\\s*'[^']*'/iu", '', $html);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*[^\s>]+/iu', '', $html);
        $html = preg_replace('/\s+(?:src|href)\s*=\s*"\s*(?:javascript|data):[^"]*"/iu', '', $html);
        $html = preg_replace("/\\s+(?:src|href)\\s*=\\s*'\\s*(?:javascript|data):[^']*'/iu", '', $html);
        $html = preg_replace('/(?:expression\s*\(|javascript\s*:|behavior\s*:|@import)/iu', '', $html);

        return $html;
    }
}
