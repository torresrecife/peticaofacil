<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddComportamentoToPeticaoModeloCamposTable extends Migration
{
    public function up()
    {
        Schema::table('peticao_modelo_campos', function (Blueprint $table) {
            $table->string('comportamento', 50)->nullable()->after('tipo');
        });
    }

    public function down()
    {
        Schema::table('peticao_modelo_campos', function (Blueprint $table) {
            $table->dropColumn('comportamento');
        });
    }
}
