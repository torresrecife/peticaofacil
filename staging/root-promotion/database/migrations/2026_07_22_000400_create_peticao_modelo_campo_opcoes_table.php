<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeticaoModeloCampoOpcoesTable extends Migration
{
    public function up()
    {
        Schema::create('peticao_modelo_campo_opcoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campo_id');
            $table->unsignedInteger('legacy_dado_id')->nullable()->unique();
            $table->string('rotulo', 255);
            $table->text('valor_retorno')->nullable();
            $table->json('valores_extras')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->foreign('campo_id')
                ->references('id')
                ->on('peticao_modelo_campos')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('peticao_modelo_campo_opcoes');
    }
}
