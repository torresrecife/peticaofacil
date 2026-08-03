<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeticaoModeloCamposTable extends Migration
{
    public function up()
    {
        Schema::create('peticao_modelo_campos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('modelo_id');
            $table->unsignedInteger('legacy_input_id')->nullable()->unique();
            $table->string('rotulo', 255)->nullable();
            $table->string('token', 255)->unique();
            $table->string('tipo', 50);
            $table->string('origem_coluna', 255)->nullable();
            $table->string('origem_alias', 255)->nullable();
            $table->text('prefixo')->nullable();
            $table->text('sufixo')->nullable();
            $table->text('valor_padrao')->nullable();
            $table->string('classe_css', 255)->nullable();
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('colunas_layout')->nullable();
            $table->unsignedInteger('linhas_layout')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('obrigatorio')->default(false);
            $table->boolean('visivel')->default(true);
            $table->boolean('gera_nome_arquivo')->default(false);
            $table->longText('eventos_frontend')->nullable();
            $table->timestamps();

            $table->foreign('modelo_id')
                ->references('id')
                ->on('peticao_modelos')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('peticao_modelo_campos');
    }
}
