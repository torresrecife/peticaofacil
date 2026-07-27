<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNormalizedListasTables extends Migration
{
    public function up()
    {
        Schema::create('lista_grupos', function (Blueprint $table) {
            $table->unsignedInteger('id_grupo')->primary();
            $table->unsignedInteger('legacy_grupo_id')->nullable()->unique();
            $table->string('nome_grupo', 500);
            $table->dateTime('data_cad')->nullable();
            $table->timestamps();
        });

        Schema::create('lista_itens', function (Blueprint $table) {
            $table->unsignedInteger('id_lista')->primary();
            $table->unsignedInteger('legacy_lista_id')->nullable()->unique();
            $table->unsignedInteger('id_grupo');
            $table->string('nome_lista', 500)->nullable();
            $table->string('return_1', 500)->nullable();
            $table->string('return_2', 500)->nullable();
            $table->string('return_3', 500)->nullable();
            $table->string('return_4', 500)->nullable();
            $table->string('return_5', 500)->nullable();
            $table->string('return_6', 500)->nullable();
            $table->dateTime('data_cad')->nullable();
            $table->unsignedInteger('id_setor')->nullable();
            $table->timestamps();

            $table->index('id_grupo', 'lista_itens_grupo_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lista_itens');
        Schema::dropIfExists('lista_grupos');
    }
}
