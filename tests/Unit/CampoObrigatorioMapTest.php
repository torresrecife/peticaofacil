<?php

namespace Tests\Unit;

use App\PeticaoModeloCampo;
use PHPUnit\Framework\TestCase;

class CampoObrigatorioMapTest extends TestCase
{
    /**
     * @dataProvider legacyValues
     */
    public function test_maps_legacy_input_req_to_required_flag($input, $expected)
    {
        $this->assertSame($expected, PeticaoModeloCampo::mapLegacyRequired($input));
    }

    public function legacyValues()
    {
        return [
            'Sim no legado' => [2, true],
            'Sim como texto' => ['2', true],
            'Nao no legado' => [1, false],
            'Nao como texto' => ['1', false],
            'vazio' => ['', false],
            'nulo' => [null, false],
        ];
    }

    public function test_reads_every_fc_ajax_comp_of_a_field()
    {
        $campo = new PeticaoModeloCampo([
            'eventos_frontend' => [
                'focus' => 'fc_ajax_comp("tp_dados_tb","return_1","campo2363","unir","id_dados",this,1); mcampo("campo2362_|_campo2363");'
                    . 'fc_ajax_comp("tp_dados_tb","return_2","campo2364","unir","id_dados",this,1); mcampo("campo2362_|_campo2364");',
                'blur' => 'fc_ajax_comp("tp_dados_tb","return_1","campo2363","unir","id_dados",this,1);',
            ],
        ]);

        $fills = array_map(function ($fill) {
            return [$fill['target_field_id'], $fill['return_column']];
        }, $campo->dependent_fill_configs);

        $this->assertSame([[2363, 'return_1'], [2364, 'return_2']], $fills);
        $this->assertSame(2363, $campo->dependent_fill_config['target_field_id']);
    }
}
