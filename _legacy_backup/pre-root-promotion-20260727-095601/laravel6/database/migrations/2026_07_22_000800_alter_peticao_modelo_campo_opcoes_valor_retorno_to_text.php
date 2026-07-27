<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterPeticaoModeloCampoOpcoesValorRetornoToText extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE peticao_modelo_campo_opcoes MODIFY valor_retorno TEXT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE peticao_modelo_campo_opcoes MODIFY valor_retorno VARCHAR(255) NULL');
    }
}
