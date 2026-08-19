<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveLegacySetoresClientesTables extends Migration
{
    protected $tables = [
        'tp_clientes_db' => ['target' => 'clientes', 'key' => 'cliente_id'],
        'tp_setor_tb' => ['target' => 'setores', 'key' => 'id_setor'],
    ];

    public function up()
    {
        foreach ($this->tables as $source => $config) {
            $archive = $source . '_archive_20260818';

            if (!Schema::hasTable($source)) {
                continue;
            }

            if (Schema::hasTable($archive)) {
                throw new \RuntimeException('Destino de arquivamento ja existe: ' . $archive);
            }

            $sourceCount = DB::table($source)->count();
            $missingCount = DB::table($source . ' as legacy')
                ->leftJoin($config['target'] . ' as normalized', 'normalized.' . $config['key'], '=', 'legacy.' . $config['key'])
                ->whereNull('normalized.' . $config['key'])
                ->count();

            if ($sourceCount !== DB::table($config['target'])->count() || $missingCount !== 0) {
                throw new \RuntimeException('Arquivamento bloqueado por divergencia entre ' . $source . ' e ' . $config['target'] . '.');
            }

            Schema::rename($source, $archive);
        }
    }

    public function down()
    {
        foreach (array_reverse(array_keys($this->tables)) as $source) {
            $archive = $source . '_archive_20260818';

            if (!Schema::hasTable($source) && Schema::hasTable($archive)) {
                Schema::rename($archive, $source);
            }
        }
    }
}
