<?php

namespace App\Console\Commands;

use App\Peca;
use App\Services\LegacyPecaSyncService;
use Illuminate\Console\Command;

class SyncLegacyPecas extends Command
{
    protected $signature = 'peticao:sync-pecas {peca_id? : ID legado da peca}';

    protected $description = 'Sincroniza pecas legadas para a tabela normalizada peticoes.';

    public function handle(LegacyPecaSyncService $syncService)
    {
        $pecaId = $this->argument('peca_id');

        if ($pecaId) {
            $peca = Peca::with('tipo')->findOrFail($pecaId);
            $peticao = $syncService->syncPeca($peca, $peca->tipo);

            if (!$peticao) {
                $this->warn('Peca sem modelo normalizado correspondente.');

                return 1;
            }

            $this->info('Peca sincronizada: #' . $peca->id_pecas . ' -> #' . $peticao->id);

            return 0;
        }

        $count = $syncService->syncAll();
        $this->info('Pecas sincronizadas: ' . $count);

        return 0;
    }
}
