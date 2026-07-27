<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveLegacyOperationalTables extends Command
{
    protected $signature = 'legacy:archive-operational {--targets=listas,sql : listas|sql|all} {--execute : Executa o arquivamento fisico}';
    protected $description = 'Arquiva tabelas legadas operacionais por rename controlado';

    public function handle()
    {
        $targets = strtolower((string) $this->option('targets'));
        $execute = (bool) $this->option('execute');

        if (!in_array($targets, ['listas', 'sql', 'all'], true)) {
            $this->error('Targets invalidos. Use listas, sql ou all.');

            return 1;
        }

        $tables = [];

        if ($targets === 'listas' || $targets === 'all') {
            $tables = array_merge($tables, ['tp_grupo_tb', 'tp_lista_tb']);
        }

        if ($targets === 'sql' || $targets === 'all') {
            $tables[] = 'tp_config_db';
        }

        $suffix = 'archive_20260727';
        $plan = [];

        foreach ($tables as $table) {
            $archiveTable = $table . '_' . $suffix;
            $plan[] = [
                'origem' => $table,
                'destino' => $archiveTable,
                'status' => $this->determineStatus($table, $archiveTable),
            ];
        }

        $this->table(['Origem', 'Destino', 'Status'], $plan);

        if (!$execute) {
            $this->warn('Dry-run. Use --execute para arquivar fisicamente.');

            return 0;
        }

        foreach ($plan as $item) {
            if ($item['status'] !== 'PRONTA') {
                continue;
            }

            DB::statement(sprintf(
                'RENAME TABLE `%s` TO `%s`',
                $item['origem'],
                $item['destino']
            ));
        }

        $this->info('Arquivamento concluido.');

        return 0;
    }

    protected function determineStatus(string $source, string $target): string
    {
        if (!Schema::hasTable($source) && Schema::hasTable($target)) {
            return 'JA_ARQUIVADA';
        }

        if (!Schema::hasTable($source)) {
            return 'AUSENTE';
        }

        if (Schema::hasTable($target)) {
            return 'CONFLITO_DESTINO';
        }

        return 'PRONTA';
    }
}
