<?php

namespace Tests\Feature;

use App\Services\LegacyCampoRestoreService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyCampoRestoreServiceTest extends TestCase
{
    protected function seedCampos()
    {
        DB::table('peticao_modelos')->insert([
            'id' => 95,
            'nome' => 'MODELO MIGRADO',
            'slug' => 'modelo-migrado-95',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migrado = '2026-08-02 22:18:40';
        $rows = [
            // [id, legacy_input_id, obrigatorio, comportamento, updated_at]
            [9501, 501, 0, null, $migrado],        // legado Sim, veio invertido
            [9502, 502, 1, 'date', $migrado],       // legado Nao, veio invertido; mascara ja certa
            [9503, 503, 0, '', '2026-09-01 10:00:00'], // editado na tela depois: mantido
            [9504, null, 0, '', $migrado],          // criado no sistema novo: nao e tocado
            [9505, 505, 1, 'cpf', $migrado],        // ja certo; mascara diferente do legado
        ];

        foreach ($rows as $row) {
            DB::table('peticao_modelo_campos')->insert([
                'id' => $row[0],
                'modelo_id' => 95,
                'legacy_input_id' => $row[1],
                'rotulo' => 'CAMPO ' . $row[0],
                'token' => '@campo' . $row[0] . '@',
                'tipo' => 'TEXT',
                'comportamento' => $row[3],
                'ordem' => 1,
                'visivel' => 1,
                'obrigatorio' => $row[2],
                'gera_nome_arquivo' => 0,
                'created_at' => $migrado,
                'updated_at' => $row[4],
            ]);
        }

        return [
            '501' => ['req' => 2, 'alt' => 'cpf'],
            '502' => ['req' => 1, 'alt' => 'date'],
            '503' => ['req' => 2],
            '505' => ['req' => 2, 'alt' => 'cnpj'],
            '999' => ['req' => 2, 'alt' => 'cep'],
        ];
    }

    protected function campo($id)
    {
        return DB::table('peticao_modelo_campos')->where('id', $id)->first();
    }

    public function test_fixes_inverted_required_flag_but_keeps_fields_edited_on_screen()
    {
        $fields = $this->seedCampos();
        $service = app(LegacyCampoRestoreService::class);

        $plan = $service->planRequired($fields);
        $this->assertSame([9501 => 1, 9502 => 0], $plan['updates']);
        $this->assertSame(1, $plan['ja_ok']);
        $this->assertSame(1, $plan['editados_mantidos']);
        $this->assertSame(1, $plan['sem_campo_novo']);

        $this->assertSame(2, $service->apply($plan['updates'], 'obrigatorio'));
        $this->assertSame(1, (int) $this->campo(9501)->obrigatorio);
        $this->assertSame(0, (int) $this->campo(9502)->obrigatorio);
        $this->assertSame(0, (int) $this->campo(9503)->obrigatorio);
        $this->assertSame(0, (int) $this->campo(9504)->obrigatorio);
        $this->assertSame('2026-08-02 22:18:40', (string) $this->campo(9501)->updated_at);

        // Rodar de novo nao muda nada.
        $this->assertSame([], $service->planRequired($fields)['updates']);

        $this->assertSame([9503 => 1], $service->planRequired($fields, true)['updates']);
    }

    public function test_fills_only_empty_masks()
    {
        $fields = $this->seedCampos();
        $service = app(LegacyCampoRestoreService::class);

        $plan = $service->planBehavior($fields);
        $this->assertSame([9501 => 'cpf'], $plan['updates']);
        $this->assertSame(1, $plan['divergentes']);

        $service->apply($plan['updates'], 'comportamento');
        $this->assertSame('cpf', $this->campo(9501)->comportamento);
        $this->assertSame('cpf', $this->campo(9505)->comportamento);
    }

    public function test_sql_export_matches_by_legacy_id_and_keeps_the_same_guards()
    {
        $service = app(LegacyCampoRestoreService::class);
        $fields = ['501' => ['req' => 2, 'alt' => 'cpf'], '502' => ['req' => 1]];

        $sql = $service->toSql($fields, 'obrigatorio');
        $this->assertStringContainsString('SET obrigatorio = 1 WHERE legacy_input_id = 501 AND obrigatorio <> 1 AND (updated_at IS NULL OR created_at IS NULL OR updated_at <= created_at);', $sql);
        $this->assertStringContainsString('SET obrigatorio = 0 WHERE legacy_input_id = 502', $sql);
        $this->assertStringNotContainsString('WHERE id =', $sql);

        $sql = $service->toSql($fields, 'comportamento');
        $this->assertSame("UPDATE peticao_modelo_campos SET comportamento = 'cpf' WHERE legacy_input_id = 501 AND (comportamento IS NULL OR comportamento = '');\n", $sql);
    }

    public function test_versioned_snapshot_is_readable()
    {
        $fields = app(LegacyCampoRestoreService::class)->loadSnapshot();

        $this->assertGreaterThan(3000, count($fields));
        $this->assertContains(reset($fields)['req'], [1, 2]);
    }
}
