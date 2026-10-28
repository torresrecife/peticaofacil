<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configuracoes gerais do sistema (chave => valor), editadas na administracao.
 * Comeca com o cabecalho padrao dos modelos novos, que antes ficava fixo no
 * codigo (NormalizedTipoController::defaultHeaderHtml).
 */
class CreateConfiguracoesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('configuracoes')) {
            Schema::create('configuracoes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('chave', 120)->unique();
                $table->longText('valor')->nullable();
                $table->timestamps();
            });
        }

        if (!DB::table('configuracoes')->where('chave', 'modelo.cabecalho_padrao')->exists()) {
            DB::table('configuracoes')->insert([
                'chave' => 'modelo.cabecalho_padrao',
                'valor' => \App\Configuracao::CABECALHO_PADRAO_INICIAL,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('configuracoes');
    }
}
