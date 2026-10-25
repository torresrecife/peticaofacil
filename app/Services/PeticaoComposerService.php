<?php

namespace App\Services;

/**
 * Monta o texto da peticao a partir do modelo e dos valores do formulario,
 * seguindo as regras do sistema antigo (parag.php):
 *
 * - a forma do marcador decide maiusculas/minusculas do valor:
 *   @CAMPO1@ = MAIUSCULAS, @Campo1@ = Iniciais Maiusculas, @campo1@ = como digitado;
 * - @campo1_pre@ / @campo1_pos@ trazem o prefixo/sufixo do campo (ex.: as linhas
 *   da tabela de parcelas) e so aparecem quando o campo tem valor; sem esses
 *   marcadores, o prefixo/sufixo vai junto do valor, tambem so quando ha valor;
 * - em SELECT/RADIO vindo do legado, o marcador traz o texto da opcao escolhida
 *   (o "retorno" da opcao abastece outros campos pelos eventos da tela);
 * - virgulas soltas de campos vazios (", , ,") sao limpas nos paragrafos.
 */
class PeticaoComposerService
{
    const TOKEN_PATTERN = '/@(campo)(\d+)(_pre|_pos)?@/i';

    /** Palavras que ficam minusculas nas "Iniciais Maiusculas" (exceto no inicio). */
    const TITLE_LOWER_WORDS = ['a', 'à', 'ao', 'aos', 'as', 'às', 'com', 'da', 'das', 'de', 'do', 'dos', 'e', 'em', 'na', 'nas', 'no', 'nos', 'o', 'os', 'ou', 'para', 'pela', 'pelas', 'pelo', 'pelos', 'por', 'sob', 'nº', 'n°'];

    /** Siglas que ficam inteiras em maiusculas nas "Iniciais Maiusculas". */
    const TITLE_UPPER_WORDS = ['cpf', 'cnpj', 'cnpj/mf', 'cpf/mf', 's.a', 's.a.', 's/a', 'ltda', 'ltda.', 'me', 'epp', 'eireli', 'oab', 'cep', 'rg', 'ii', 'iii', 'iv', 'vi', 'vii', 'viii', 'ix', 'xi', 'xii'];

    const UF = ['ac', 'al', 'ap', 'am', 'ba', 'ce', 'df', 'es', 'go', 'ma', 'mt', 'ms', 'mg', 'pa', 'pb', 'pr', 'pe', 'pi', 'rj', 'rn', 'rs', 'ro', 'rr', 'sc', 'sp', 'se', 'to'];

    public function compose($modelo, array $input)
    {
        $resolved = [];
        $fields = [];
        $fileNameParts = [];

        foreach ($modelo->campos as $campo) {
            $fieldKey = 'campo_' . $campo->id_input;
            $rawValue = $input[$fieldKey] ?? '';
            $value = $this->resolveCampoValue($campo, $rawValue);
            $filled = $this->hasContent($value);
            $prefix = (string) ($campo->input_pre ?: '');
            $suffix = (string) ($campo->input_pos ?: '');

            $fields[(string) $campo->id_input] = [
                'value' => $value,
                'filled' => $filled,
                'prefix' => $prefix,
                'suffix' => $suffix,
            ];

            $resolved[$campo->id_input] = [
                'id' => $campo->id_input,
                'title' => $campo->input_title,
                'type' => $campo->input_tipo,
                'raw' => $rawValue,
                'value' => $filled ? $prefix . $value . $suffix : '',
                'placeholder' => $campo->placeholder,
            ];

            if ($campo->nomepet === 'Y' && trim(strip_tags($value)) !== '') {
                $fileNameParts[] = trim(strip_tags($value));
            }
        }

        $allText = (string) $modelo->cod_cabec . (string) $modelo->cod_rodap;
        foreach ($modelo->paragrafos as $paragrafo) {
            $allText .= (string) $paragrafo->fund_text;
        }
        $explicitAffixes = $this->explicitAffixFields($allText);

        $sections = [];
        if ($modelo->cod_cabec) {
            $sections[] = $this->replacePlaceholders($modelo->cod_cabec, $fields, $explicitAffixes);
        }
        foreach ($modelo->paragrafos as $paragrafo) {
            $sections[] = $this->cleanEmptyCommas($this->replacePlaceholders($paragrafo->fund_text, $fields, $explicitAffixes));
        }
        if ($modelo->cod_rodap) {
            $sections[] = $this->replacePlaceholders($modelo->cod_rodap, $fields, $explicitAffixes);
        }

        $composedHtml = implode("\n", array_filter($sections));
        // Resolve tokens that may exist inside a predefined TEXTAREA template.
        $composedHtml = $this->replacePlaceholders($composedHtml, $fields, $explicitAffixes);

        return [
            'html' => $composedHtml,
            'resolved_fields' => $resolved,
            'suggested_filename' => $fileNameParts ? implode('_', $fileNameParts) : 'peticao',
        ];
    }

    /**
     * Valor do campo ja pronto para o HTML, sem prefixo/sufixo.
     */
    protected function resolveCampoValue($campo, $rawValue)
    {
        if (in_array($campo->input_tipo, ['SELECT', 'RADIO2'], true)) {
            if ($campo->hasAssociatedListSource()) {
                return e((string) $rawValue);
            }

            foreach ($campo->select_options as $option) {
                $value = $option['value'] ?? $option['label'];
                if ((string) $value === (string) $rawValue) {
                    // No legado o value da opcao e o proprio texto (nome_dados).
                    // Modelos criados no sistema novo usam o retorno da opcao, que
                    // pode ser o HTML de um trecho opcional (importacao com IA).
                    if ($this->isLegacyField($campo)) {
                        return e($option['label']);
                    }
                    $return = (string) $option['return'];

                    return $return !== strip_tags($return) ? $this->sanitizeRichText($return) : e($return);
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

        return e((string) $rawValue);
    }

    protected function isLegacyField($campo)
    {
        return (int) ($campo->legacy_input_id ?? 0) > 0;
    }

    protected function hasContent($html)
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\xC2\xA0", ' ', $text)) !== '';
    }

    /**
     * Campos cujo prefixo/sufixo e citado por marcador proprio no texto.
     */
    protected function explicitAffixFields($html)
    {
        $ids = [];
        if (preg_match_all('/@campo(\d+)_(?:pre|pos)@/i', (string) $html, $matches)) {
            foreach ($matches[1] as $id) {
                $ids[(string) $id] = true;
            }
        }

        return $ids;
    }

    protected function replacePlaceholders($html, array $fields, array $explicitAffixes = [])
    {
        return preg_replace_callback(self::TOKEN_PATTERN, function ($matches) use ($fields, $explicitAffixes) {
            $id = $matches[2];
            if (!array_key_exists($id, $fields)) {
                return $matches[0];
            }

            $field = $fields[$id];
            $part = strtolower((string) ($matches[3] ?? ''));

            if (!$field['filled']) {
                $replacement = '';
            } elseif ($part === '_pre') {
                $replacement = $field['prefix'];
            } elseif ($part === '_pos') {
                $replacement = $field['suffix'];
            } elseif (isset($explicitAffixes[$id])) {
                $replacement = $field['value'];
            } else {
                $replacement = $field['prefix'] . $field['value'] . $field['suffix'];
            }

            return $this->applyTokenCase($replacement, $matches[1]);
        }, (string) $html);
    }

    /**
     * "CAMPO" = maiusculas, "Campo" = iniciais maiusculas, qualquer outra forma = como esta.
     */
    protected function applyTokenCase($html, $marker)
    {
        if ($marker === 'CAMPO') {
            return $this->transformText($html, function ($text) {
                return mb_strtoupper($text, 'UTF-8');
            });
        }

        if ($marker === 'Campo') {
            return $this->transformText($html, function ($text) {
                return $this->titleCase($text);
            });
        }

        return $html;
    }

    /**
     * Aplica a transformacao so no texto, preservando tags e entidades HTML.
     */
    protected function transformText($html, callable $transform)
    {
        if ($html === '') {
            return $html;
        }

        $parts = preg_split('/(<[^>]*>)/', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $index => $part) {
            if ($part === '' || $part[0] === '<') {
                continue;
            }

            $text = html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $parts[$index] = htmlspecialchars($transform($text), ENT_NOQUOTES, 'UTF-8', false);
        }

        return implode('', $parts);
    }

    /**
     * Iniciais maiusculas no estilo do legado (upwords), com os conectivos em
     * minusculas e siglas/UF preservadas.
     */
    public function titleCase($text)
    {
        $tokens = preg_split('/([\s\/\-]+)/u', mb_strtolower((string) $text, 'UTF-8'), -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = [];
        $isFirstWord = true;
        $previousSeparator = '';

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            if (preg_match('/^[\s\/\-]+$/u', $token)) {
                $out[] = $token;
                $previousSeparator = $token;
                continue;
            }

            $bare = trim($token, '.,;:()"\'');
            if (in_array($token, self::TITLE_UPPER_WORDS, true) || in_array($bare, self::TITLE_UPPER_WORDS, true)) {
                $word = mb_strtoupper($token, 'UTF-8');
            } elseif (in_array($bare, self::UF, true) && (strpos($previousSeparator, '-') !== false || strpos($previousSeparator, '/') !== false)) {
                $word = mb_strtoupper($token, 'UTF-8');
            } elseif (!$isFirstWord && in_array($bare, self::TITLE_LOWER_WORDS, true)) {
                $word = $token;
            } else {
                $word = preg_replace_callback('/^([^\pL]*)(\pL)/u', function ($m) {
                    return $m[1] . mb_strtoupper($m[2], 'UTF-8');
                }, $token);
            }

            $out[] = $word;
            $isFirstWord = false;
            $previousSeparator = '';
        }

        return implode('', $out);
    }

    /**
     * Como no legado: ", , ," vira ", ," e ", ," vira ",".
     */
    protected function cleanEmptyCommas($html)
    {
        return str_replace(', ,', ',', str_replace(', , ,', ', ,', (string) $html));
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
