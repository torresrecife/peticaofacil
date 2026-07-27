<?php

namespace App\Console\Commands;

use App\Services\LegacyModeloSyncService;
use App\Tipo;
use Illuminate\Console\Command;

class SyncLegacyModelos extends Command
{
    protected $signature = 'peticao:sync-legado {modelo_id? : ID legado do modelo}';

    protected $description = 'Sincroniza modelos, paragrafos e campos do legado para as tabelas normalizadas.';

    public function handle(LegacyModeloSyncService $syncService)
    {
        $modeloId = $this->argument('modelo_id');

        if ($modeloId) {
            $tipo = Tipo::with(['paragrafos', 'campos.dados'])->findOrFail($modeloId);
            $modelo = $syncService->syncTipo($tipo);

            $this->info('Modelo sincronizado: #' . $modelo->legacy_tipo_id . ' -> #' . $modelo->id);

            return 0;
        }

        $count = $syncService->syncAll();
        $this->info('Modelos sincronizados: ' . $count);

        return 0;
    }
}
