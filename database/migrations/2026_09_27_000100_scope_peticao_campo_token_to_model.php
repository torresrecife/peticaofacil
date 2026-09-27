<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScopePeticaoCampoTokenToModel extends Migration
{
    public function up()
    {
        Schema::table('peticao_modelo_campos', function (Blueprint $table) {
            $table->dropUnique('peticao_modelo_campos_token_unique');
            $table->unique(['modelo_id', 'token'], 'peticao_modelo_campos_modelo_token_unique');
        });
    }

    public function down()
    {
        Schema::table('peticao_modelo_campos', function (Blueprint $table) {
            $table->dropUnique('peticao_modelo_campos_modelo_token_unique');
            $table->unique('token', 'peticao_modelo_campos_token_unique');
        });
    }
}
