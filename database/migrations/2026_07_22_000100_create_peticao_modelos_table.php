<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeticaoModelosTable extends Migration
{
    public function up()
    {
        Schema::create('peticao_modelos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_tipo_id')->nullable()->unique();
            $table->unsignedInteger('legacy_cliente_id')->nullable();
            $table->unsignedInteger('legacy_setor_id')->nullable();
            $table->unsignedInteger('legacy_sql_config_id')->nullable();
            $table->string('nome', 255);
            $table->string('slug', 255)->nullable()->unique();
            $table->string('status', 20)->default('ativo');
            $table->string('arquivo_padrao', 50)->nullable();
            $table->longText('cabecalho_html')->nullable();
            $table->longText('rodape_html')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('peticao_modelos');
    }
}
