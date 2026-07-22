<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeticaoModeloParagrafosTable extends Migration
{
    public function up()
    {
        Schema::create('peticao_modelo_paragrafos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('modelo_id');
            $table->unsignedInteger('legacy_fund_id')->nullable()->unique();
            $table->string('titulo', 255)->nullable();
            $table->longText('conteudo_html')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('visivel')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('modelo_id')
                ->references('id')
                ->on('peticao_modelos')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('peticao_modelo_paragrafos');
    }
}
