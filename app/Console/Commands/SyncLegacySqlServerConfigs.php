<?php

namespace App\Console\Commands;

use App\SqlServerConfig;
use App\Services\NormalizedSqlServerConfigSyncService;
use Illuminate\Console\Command;

class SyncLegacySqlServerConfigs extends Command
{
    protected $signature = 'servidores:sync-legado {config_id? : ID legado do servidor SQL}';
    protected $description = 'Sincroniza servidores SQL legados para o esquema normalizado';

    public function handle(NormalizedSqlServerConfigSyncService $syncService)
    {
        $configId = $this->argument('config_id');

        if ($configId) {
            $profile = $syncService->syncLegacy(SqlServerConfig::findOrFail($configId));
            $this->info('Servidor sincronizado: legado #' . $configId . ' -> perfil #' . $profile->id);

            return 0;
        }

        $count = 0;

        SqlServerConfig::orderBy('id_db')->chunk(100, function ($configs) use ($syncService, &$count) {
            foreach ($configs as $config) {
                $syncService->syncLegacy($config);
                $count++;
            }
        });

        $this->info('Servidores sincronizados: ' . $count);

        return 0;
    }
}
