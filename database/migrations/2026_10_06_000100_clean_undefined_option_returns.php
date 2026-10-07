<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * As opcoes de SELECT trazidas do sistema legado guardaram a string literal
 * "undefined" (e, em alguns casos, "null") no retorno, quando o paragrafo
 * predefinido nunca foi preenchido. Isso fazia a peca exibir "undefined".
 * Esta migration zera esses valores; o codigo ja trata ambos como vazio,
 * entao ela so deixa o banco coerente. Pode rodar mais de uma vez.
 */
class CleanUndefinedOptionReturns extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('peticao_modelo_campo_opcoes')) {
            return;
        }

        DB::table('peticao_modelo_campo_opcoes')
            ->whereRaw("LOWER(TRIM(valor_retorno)) IN ('undefined', 'null')")
            ->update(['valor_retorno' => null]);
    }

    public function down()
    {
        // Nada a desfazer: "undefined" era lixo, nao um valor a restaurar.
    }
}
