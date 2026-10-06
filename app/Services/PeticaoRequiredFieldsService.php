<?php

namespace App\Services;

/**
 * Campos marcados como "Obrigatorio" no modelo e que ficaram em branco na
 * montagem. O resultado e um aviso: a previa e gerada do mesmo jeito.
 */
class PeticaoRequiredFieldsService
{
    /**
     * Tipos que nao recebem digitacao; a marcacao neles nao significa nada.
     */
    const NON_INPUT_TYPES = ['TITLE', 'HIDDEN'];

    public function isRequired($campo)
    {
        return (string) $campo->input_req === '1'
            && !in_array(strtoupper((string) $campo->input_tipo), self::NON_INPUT_TYPES, true);
    }

    /**
     * @param iterable   $campos
     * @param array      $values    Valores do formulario, por "campo_<id>".
     * @param array|null $hiddenIds Campos que a tela informou estarem escondidos
     *                              (regras de bens, parcelas e condicionais). Sem
     *                              essa informacao, vale a visibilidade inicial.
     *
     * @return array Lista de ['id' => int, 'label' => string], na ordem do modelo.
     */
    public function missing($campos, array $values, array $hiddenIds = null)
    {
        $missing = [];

        foreach ($campos as $campo) {
            if (!$this->isRequired($campo)) {
                continue;
            }

            $id = (int) $campo->id_input;
            $hidden = $hiddenIds === null ? !$campo->visivel : in_array($id, $hiddenIds, true);
            if ($hidden || !$this->isBlank($values['campo_' . $id] ?? '')) {
                continue;
            }

            $missing[] = ['id' => $id, 'label' => trim((string) $campo->input_title) ?: ('Campo ' . $id)];
        }

        return $missing;
    }

    /**
     * Texto rico vazio costuma chegar como "<p>&nbsp;</p>"; imagem ou tabela
     * sem texto ja conta como conteudo.
     */
    public function isBlank($value)
    {
        if (is_array($value)) {
            return count(array_filter($value, function ($item) {
                return !$this->isBlank($item);
            })) === 0;
        }

        $value = (string) $value;
        if (preg_match('/<(img|table|hr)\b/i', $value)) {
            return false;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\xC2\xA0", ' ', $text)) === '';
    }

    /**
     * Lista enviada pela tela, sempre iniciada por 0: "0" quando nao ha campo
     * escondido, "0,12,15" quando ha. O zero existe porque o Laravel troca
     * campo vazio por null, e null aqui significa outra coisa: a tela nao
     * informou nada (requisicao sem JavaScript).
     */
    public function parseHiddenIds($raw)
    {
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        $ids = [];
        foreach (explode(',', (string) $raw) as $part) {
            $part = trim($part);
            if (ctype_digit($part) && (int) $part > 0) {
                $ids[] = (int) $part;
            }
        }

        return $ids;
    }
}
