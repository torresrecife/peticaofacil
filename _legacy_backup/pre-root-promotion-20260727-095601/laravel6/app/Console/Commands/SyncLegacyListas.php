<?php

namespace App\Console\Commands;

use App\Services\ListaSyncService;
use Illuminate\Console\Command;

class SyncLegacyListas extends Command
{
    protected $signature = 'listas:sync-legado';
    protected $description = 'Sincroniza listas legadas para o esquema normalizado';

    public function handle(ListaSyncService $service)
    {
        $count = $service->syncAll();
        $this->info('Listas sincronizadas: ' . $count);

        return 0;
    }
}
