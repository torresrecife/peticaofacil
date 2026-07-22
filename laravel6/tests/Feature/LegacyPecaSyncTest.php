<?php

namespace Tests\Feature;

use App\Services\LegacyPecaSyncService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyPecaSyncTest extends TestCase
{
    public function test_command_syncs_existing_legacy_pieces()
    {
        $this->seedLegacyPeca();

        $this->artisan('peticao:sync-pecas')
            ->expectsOutput('Pecas sincronizadas de 2026: 1')
            ->assertExitCode(0);

        $espelho = DB::table('peticoes')->where('legacy_peca_id', 901)->first();
        $this->assertNotNull($espelho);
        $this->assertSame('Cliente Retroativo', $espelho->cliente_referencia);
    }

    public function test_command_syncs_only_requested_year_by_default()
    {
        $this->seedLegacyPeca();

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 902,
            'tipo_id' => 33,
            'id_usu' => null,
            'nome_pecas' => 'Modelo para Peca',
            'nome_cli' => 'Cliente Antigo',
            'cod_pecas' => '<p>Legado antigo</p>',
            'data_cad' => '2025-12-20 10:00:00',
            'cod_sav' => 'ANTIGO',
        ]);

        $count = $this->app->make(LegacyPecaSyncService::class)->syncAll(2026);

        $this->assertSame(1, $count);
        $this->assertNotNull(DB::table('peticoes')->where('legacy_peca_id', 901)->first());
        $this->assertNull(DB::table('peticoes')->where('legacy_peca_id', 902)->first());
    }

    public function test_command_can_sync_full_legacy_history_when_requested()
    {
        $this->seedLegacyPeca();

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 902,
            'tipo_id' => 33,
            'id_usu' => null,
            'nome_pecas' => 'Modelo para Peca',
            'nome_cli' => 'Cliente Antigo',
            'cod_pecas' => '<p>Legado antigo</p>',
            'data_cad' => '2025-12-20 10:00:00',
            'cod_sav' => 'ANTIGO',
        ]);

        $count = $this->app->make(LegacyPecaSyncService::class)->syncAll(null);

        $this->assertSame(2, $count);
        $this->assertNotNull(DB::table('peticoes')->where('legacy_peca_id', 901)->first());
        $this->assertNotNull(DB::table('peticoes')->where('legacy_peca_id', 902)->first());
    }

    public function test_service_syncs_one_legacy_piece()
    {
        $this->seedLegacyPeca();

        $peca = \App\Peca::with('tipo')->findOrFail(901);
        $espelho = $this->app->make(LegacyPecaSyncService::class)->syncPeca($peca, $peca->tipo);

        $this->assertNotNull($espelho);
        $this->assertSame(901, (int) $espelho->legacy_peca_id);
        $this->assertSame('Retroativo', $espelho->codigo_externo);
    }

    protected function seedLegacyPeca()
    {
        DB::table('tp_setor_tb')->insert([
            'id_setor' => 3,
            'nome_setor' => 'Previdenciario',
            'cod_setor' => 'PRE',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 33,
            'tipo_nome' => 'Modelo para Peca',
            'id_setor' => 3,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 33,
            'legacy_tipo_id' => 33,
            'legacy_setor_id' => 3,
            'nome' => 'Modelo para Peca',
            'slug' => 'modelo-para-peca-33',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 901,
            'tipo_id' => 33,
            'id_usu' => null,
            'nome_pecas' => 'Modelo para Peca',
            'nome_cli' => 'Cliente Retroativo',
            'cod_pecas' => '<p>Legado existente</p>',
            'data_cad' => now(),
            'cod_sav' => 'Retroativo',
        ]);
    }
}
