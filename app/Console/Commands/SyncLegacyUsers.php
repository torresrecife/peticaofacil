<?php

namespace App\Console\Commands;

use App\Services\UserSyncService;
use Illuminate\Console\Command;

class SyncLegacyUsers extends Command
{
    protected $signature = 'usuarios:sync-legado';

    protected $description = 'Sincroniza os usuarios legados para a tabela users do Laravel';

    public function handle(UserSyncService $service)
    {
        $total = $service->syncAll();

        $this->info('Usuarios sincronizados: ' . $total);

        return 0;
    }
}
