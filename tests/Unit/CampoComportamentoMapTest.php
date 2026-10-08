<?php

namespace Tests\Unit;

use App\PeticaoModeloCampo;
use PHPUnit\Framework\TestCase;

class CampoComportamentoMapTest extends TestCase
{
    /**
     * @dataProvider legacyValues
     */
    public function test_maps_legacy_input_alt_to_supported_behavior($input, $expected)
    {
        $this->assertSame($expected, PeticaoModeloCampo::mapLegacyBehavior($input));
    }

    public function legacyValues()
    {
        return [
            ['date', 'date'],
            ['DATE', 'date'],
            [' data ', 'date'],
            ['decimal', 'decimal'],
            ['valor', 'decimal'],
            ['integer', 'integer'],
            ['numero', 'integer'],
            ['cpf', 'cpf'],
            ['cnpj', 'cnpj'],
            ['cpfcnpj', 'cpf_cnpj'],
            ['cpf_cnpj', 'cpf_cnpj'],
            ['phone', 'fone'],
            ['fone', 'fone'],
            ['cep', 'cep'],
            ['processo', 'processo'],
            ['', ''],
            ['desconhecido', ''],
            [null, ''],
        ];
    }
}
