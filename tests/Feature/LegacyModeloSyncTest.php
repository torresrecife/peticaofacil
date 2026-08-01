<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\LegacyModeloSyncService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyModeloSyncTest extends TestCase
{
    public function test_service_syncs_modelo_paragrafos_campos_and_options()
    {
        $this->seedLegacyModelo();

        $service = $this->app->make(LegacyModeloSyncService::class);
        $modelo = $service->syncTipo(\App\Tipo::with(['paragrafos', 'campos.dados'])->findOrFail(10));

        $this->assertSame(10, (int) $modelo->legacy_tipo_id);
        $this->assertSame('Modelo Espelho', $modelo->nome);
        $this->assertCount(2, $modelo->paragrafos);
        $this->assertCount(2, $modelo->campos);
        $this->assertSame('@campo502@', $modelo->campos->firstWhere('legacy_input_id', 502)->token);
        $this->assertSame('cpf', $modelo->campos->firstWhere('legacy_input_id', 501)->comportamento);
        $this->assertSame('retorno-1', $modelo->campos->firstWhere('legacy_input_id', 502)->opcoes->first()->valor_retorno);
    }

    public function test_command_syncs_all_legacy_modelos()
    {
        $this->seedLegacyModelo();

        $this->artisan('peticao:sync-legado')
            ->expectsOutput('Modelos sincronizados: 1')
            ->assertExitCode(0);

        $this->assertSame(1, PeticaoModelo::count());
    }

    protected function seedLegacyModelo()
    {
        DB::table('tp_setor_tb')->insert([
            'id_setor' => 2,
            'nome_setor' => 'Consumidor',
            'cod_setor' => 'CON',
            'data_cad' => now(),
        ]);

        DB::table('tp_clientes_db')->insert([
            'cliente_id' => 2,
            'cliente_name' => 'Cliente Espelho',
            'cliente_cod' => 'CE002',
            'cliente_area' => 1,
            'cliente_status' => 'Y',
            'cliente_creator' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 10,
            'tipo_nome' => 'Modelo Espelho',
            'id_cliente' => 2,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'id_setor' => 2,
            'cod_cabec' => '<p>Cabecalho</p>',
            'cod_rodap' => '<p>Rodape</p>',
            'tipo_arq' => 'pdf,word',
            'nome_pre' => 'PRE',
            'nome_pos' => 'POS',
        ]);

        DB::table('tp_funda_tb')->insert([
            [
                'fund_id' => 300,
                'tipo_id' => 10,
                'fund_titulo' => 'A',
                'fund_text' => '<p>Texto A</p>',
                'fund_order' => 1,
                'fund_data' => now(),
                'fund_visi' => 'Y',
                'fund_stt' => 'Y',
            ],
            [
                'fund_id' => 301,
                'tipo_id' => 10,
                'fund_titulo' => 'B',
                'fund_text' => '<p>Texto B</p>',
                'fund_order' => 2,
                'fund_data' => now(),
                'fund_visi' => 'Y',
                'fund_stt' => 'Y',
            ],
        ]);

        DB::table('tp_inputs_tb')->insert([
            [
                'id_input' => 501,
                'tipo_id' => 10,
                'input_title' => 'Nome',
                'input_tipo' => 'TEXT',
                'input_order' => 1,
                'input_val' => 'NOME',
                'input_db' => 'autor',
                'input_alt' => 'cpf',
                'input_focu' => null,
                'input_blur' => null,
                'input_load' => null,
                'input_req' => '1',
                'nomepet' => 'Y',
                'hide' => 'true',
                'listsel' => 'N',
            ],
            [
                'id_input' => 502,
                'tipo_id' => 10,
                'input_title' => 'Resultado',
                'input_tipo' => 'SELECT',
                'input_order' => 2,
                'input_val' => 'RESULTADO',
                'input_db' => 'processo',
                'input_alt' => null,
                'input_focu' => 'focusFn()',
                'input_blur' => 'blurFn()',
                'input_load' => 'loadFn()',
                'input_req' => '0',
                'nomepet' => 'N',
                'hide' => 'true',
                'listsel' => 'N',
            ],
        ]);

        DB::table('tp_dados_tb')->insert([
            [
                'id_dados' => 801,
                'id_input' => 502,
                'nome_dados' => 'Opcao 1',
                'return_1' => 'retorno-1',
                'return_2' => 'extra-2',
                'data_cad' => now(),
                'dados_order' => 1,
                'listsel' => 'N',
            ],
            [
                'id_dados' => 802,
                'id_input' => 502,
                'nome_dados' => 'Opcao 2',
                'return_1' => 'retorno-2',
                'return_3' => 'extra-3',
                'data_cad' => now(),
                'dados_order' => 2,
                'listsel' => 'N',
            ],
        ]);
    }
}
