<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeticaoVersoesTable extends Migration
{
    public function up()
    {
        Schema::create('peticao_versoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('peticao_id');
            $table->unsignedInteger('versao_numero');
            $table->unsignedInteger('legacy_peca_id_snapshot')->nullable();
            $table->unsignedInteger('legacy_usuario_id_snapshot')->nullable();
            $table->string('codigo_externo_snapshot', 255)->nullable();
            $table->string('cliente_referencia_snapshot', 500)->nullable();
            $table->longText('conteudo_html_snapshot');
            $table->text('campos_resolvidos_snapshot')->nullable();
            $table->string('origem_snapshot', 50)->default('save');
            $table->timestamp('criado_em')->nullable();
            $table->timestamps();

            $table->foreign('peticao_id')
                ->references('id')
                ->on('peticoes')
                ->onDelete('cascade');

            $table->unique(['peticao_id', 'versao_numero']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('peticao_versoes');
    }
}
