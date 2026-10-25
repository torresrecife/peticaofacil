<?php

namespace Tests\Unit;

use App\Support\CampoMascaraPorRotulo;
use PHPUnit\Framework\TestCase;

class CampoMascaraPorRotuloTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function test_suggests_document_mask_from_label($tipo, $rotulo, $atual, $esperado)
    {
        $this->assertSame($esperado, CampoMascaraPorRotulo::suggest($tipo, $rotulo, $atual));
    }

    public function cases()
    {
        return [
            'cpf/cnpj sem mascara' => ['TEXT', 'CPF/CNPJ', '', 'cpf_cnpj'],
            'cpf/cnpj com mascara so de cpf (cortava o CNPJ)' => ['TEXT', 'CPF / CNPJ RÉU', 'cpf', 'cpf_cnpj'],
            'cnpj ou cpf com mascara de numero' => ['TEXT', 'CNPJ/MF OU CPF/MF', 'integer', 'cpf_cnpj'],
            'cpf com mascara de valor' => ['TEXT', 'CPF', 'decimal', 'cpf'],
            'cpf sem mascara' => ['TEXT', 'CPF DO RÉU', '', 'cpf'],
            'cnpj sem mascara' => ['TEXT', 'CNPJ DO RÉU', null, 'cnpj'],
            'cep sem mascara' => ['TEXT', 'CEP - BANCO', '', 'cep'],
            'mascara certa fica' => ['TEXT', 'CNPJ AUTOR', 'cnpj', null],
            'cpf/cnpj ja combinado fica' => ['TEXT', 'CPF/CNPJ DO EXECUTADO', 'cpf_cnpj', null],
            'campo oculto nao muda' => ['HIDDEN', 'CPF/CNPJ_NOME', '', null],
            'select nao muda' => ['SELECT', 'CPF/CNPJ', '', null],
            'palavra que contem as letras nao conta' => ['TEXT', 'RECEPCAO', '', null],
        ];
    }
}
