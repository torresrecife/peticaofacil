<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveEmptyLegacyEmailPublicationTables extends Migration
{
    protected $tables = [
        'tp_emails_tb',
        'tp_pub_tb',
    ];

    public function up()
    {
        foreach ($this->tables as $source) {
            $archive = $source . '_archive_20260818';

            if (!Schema::hasTable($source)) {
                continue;
            }

            if (Schema::hasTable($archive)) {
                throw new \RuntimeException('Destino de arquivamento ja existe: ' . $archive);
            }

            if (DB::table($source)->count() !== 0) {
                throw new \RuntimeException('Arquivamento bloqueado: a tabela ' . $source . ' nao esta vazia.');
            }

            Schema::rename($source, $archive);
        }
    }

    public function down()
    {
        foreach (array_reverse($this->tables) as $source) {
            $archive = $source . '_archive_20260818';

            if (!Schema::hasTable($source) && Schema::hasTable($archive)) {
                Schema::rename($archive, $source);
            }
        }
    }
}
