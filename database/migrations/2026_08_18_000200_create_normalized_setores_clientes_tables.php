<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNormalizedSetoresClientesTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('setores')) {
            Schema::create('setores', function (Blueprint $table) {
                $table->increments('id_setor');
                $table->string('nome_setor', 500);
                $table->string('cod_setor', 50)->nullable();
                $table->dateTime('data_cad')->nullable();
            });
        }

        if (!Schema::hasColumn('setores', 'cod_img')) {
            DB::statement('ALTER TABLE `setores` ADD `cod_img` LONGBLOB NULL AFTER `data_cad`');
        }

        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->increments('cliente_id');
                $table->string('cliente_name', 500)->nullable();
                $table->string('cliente_cod', 500)->nullable();
                $table->unsignedInteger('cliente_area')->nullable();
                $table->string('cliente_status', 1)->default('Y');
                $table->dateTime('cliente_creator')->nullable();
            });
        }

        $this->copyMissingRows('tp_setor_tb', 'setores', 'id_setor', [
            'id_setor', 'nome_setor', 'data_cad', 'cod_img', 'cod_setor',
        ]);
        $this->copyMissingRows('tp_clientes_db', 'clientes', 'cliente_id', [
            'cliente_id', 'cliente_name', 'cliente_cod', 'cliente_creator', 'cliente_area', 'cliente_status',
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('setores');
    }

    protected function copyMissingRows(string $source, string $target, string $key, array $columns): void
    {
        if (!Schema::hasTable($source)) {
            return;
        }

        DB::table($source)->orderBy($key)->chunk(200, function ($rows) use ($target, $key, $columns) {
            foreach ($rows as $row) {
                $data = array_intersect_key((array) $row, array_flip($columns));

                if (!DB::table($target)->where($key, $data[$key])->exists()) {
                    DB::table($target)->insert($data);
                }
            }
        });
    }
}
