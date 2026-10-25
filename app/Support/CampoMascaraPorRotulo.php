<?php

namespace App\Support;

/**
 * Mascara de documento/CEP deduzida do rotulo do campo de texto.
 *
 * No legado varios campos de CPF, CNPJ e CEP ficaram sem mascara, e campos
 * "CPF/CNPJ" ficaram com mascara so de CPF, que corta o CNPJ em 11 digitos.
 * A regra so vale para campos TEXT e so troca mascara ausente ou incompativel:
 *
 * - rotulo com CPF e CNPJ  -> cpf_cnpj (CPF ou CNPJ pelo tamanho / campo PESSOA)
 * - rotulo so com CPF       -> cpf
 * - rotulo so com CNPJ      -> cnpj
 * - rotulo com CEP          -> cep
 */
class CampoMascaraPorRotulo
{
    /** Mascaras que a regra pode substituir (vazia ou que nao serve para documento). */
    const REPLACEABLE = ['', 'integer', 'decimal'];

    /**
     * Mascara sugerida para o campo, ou null quando nao ha nada a mudar.
     */
    public static function suggest($tipo, $rotulo, $atual)
    {
        if (strtoupper((string) $tipo) !== 'TEXT') {
            return null;
        }

        $label = mb_strtoupper((string) $rotulo, 'UTF-8');
        $atual = strtolower(trim((string) $atual));
        $hasCpf = (bool) preg_match('/(?<![A-Z])CPF(?![A-Z])/u', $label);
        $hasCnpj = (bool) preg_match('/(?<![A-Z])CNPJ(?![A-Z])/u', $label);
        $hasCep = (bool) preg_match('/(?<![A-Z])CEP(?![A-Z])/u', $label);

        if ($hasCpf && $hasCnpj) {
            // CPF ou CNPJ sozinho corta o outro documento: troca tambem esses.
            return in_array($atual, array_merge(self::REPLACEABLE, ['cpf', 'cnpj']), true) ? 'cpf_cnpj' : null;
        }
        if ($hasCpf) {
            return in_array($atual, self::REPLACEABLE, true) ? 'cpf' : null;
        }
        if ($hasCnpj) {
            return in_array($atual, self::REPLACEABLE, true) ? 'cnpj' : null;
        }
        if ($hasCep) {
            return $atual === '' ? 'cep' : null;
        }

        return null;
    }
}
