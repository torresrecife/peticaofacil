<?php

use App\Services\LegacyCampoRestoreService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * A migracao do sistema antigo gravou o "obrigatorio" dos campos invertido
 * (no legado input_req = 2 e Sim e 1 e Nao) e deixou algumas mascaras vazias.
 *
 * Esta migration corrige os dois a partir de database/data/legacy-campos.json,
 * uma foto de tp_inputs_tb gerada por peticao:legacy-campos-snapshot. Assim a
 * producao nao precisa enxergar o banco antigo.
 *
 * - Casa por legacy_input_id: campos criados no sistema novo nao sao tocados.
 * - Campos editados na tela depois da migracao (updated_at > created_at) mantem
 *   o obrigatorio escolhido.
 * - Mascara so e preenchida quando esta vazia.
 * - Pode rodar mais de uma vez: o que ja esta certo nao muda.
 */
class RestoreLegacyCampoObrigatorioEMascaras extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('peticao_modelo_campos')) {
            return;
        }

        $service = app(LegacyCampoRestoreService::class);
        $fields = $service->loadSnapshot();

        $service->apply($service->planRequired($fields)['updates'], 'obrigatorio');
        $service->apply($service->planBehavior($fields)['updates'], 'comportamento');
    }

    public function down()
    {
        // Nada a desfazer: voltar seria inverter de novo o obrigatorio.
    }
}
