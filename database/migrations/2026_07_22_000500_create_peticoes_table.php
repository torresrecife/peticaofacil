<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeticoesTable extends Migration
{
    public function up()
    {
        Schema::create('peticoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_peca_id')->nullable()->unique();
            $table->unsignedBigInteger('modelo_id');
            $table->unsignedInteger('legacy_usuario_id')->nullable();
            $table->string('codigo_externo', 255)->nullable();
            $table->string('nome_arquivo', 500);
            $table->string('cliente_referencia', 500)->nullable();
            $table->longText('conteudo_html');
            $table->json('campos_resolvidos')->nullable();
            $table->timestamp('gerado_em')->nullable();
            $table->timestamp('salvo_em')->nullable();
            $table->timestamps();

            $table->foreign('modelo_id')
                ->references('id')
                ->on('peticao_modelos')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('peticoes');
    }
}
