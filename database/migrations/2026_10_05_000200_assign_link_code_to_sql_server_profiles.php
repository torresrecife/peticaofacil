<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Os modelos apontam para o servidor SQL pelo codigo guardado em
 * sql_server_profiles.legacy_config_id. So os servidores vindos do sistema
 * antigo tinham esse codigo; os criados pela tela nova ficavam sem ele e nao
 * podiam ser escolhidos em nenhum modelo.
 *
 * Esta migration da um codigo aos servidores que ainda nao tem. Os novos ja
 * recebem o seu ao serem criados (App\SqlServerProfile).
 *
 * Pode ser executada mais de uma vez: quem ja tem codigo nao e alterado.
 */
class AssignLinkCodeToSqlServerProfiles extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sql_server_profiles')) {
            return;
        }

        // Acima tambem dos codigos que algum modelo cita sem servidor correspondente,
        // para um servidor novo nao herdar esses modelos por acaso.
        $last = (int) DB::table('sql_server_profiles')->max('legacy_config_id');
        if (Schema::hasTable('peticao_modelos')) {
            $last = max($last, (int) DB::table('peticao_modelos')->max('legacy_sql_config_id'));
        }

        $pending = DB::table('sql_server_profiles')->whereNull('legacy_config_id')->orderBy('id')->pluck('id');
        foreach ($pending as $id) {
            DB::table('sql_server_profiles')->where('id', $id)->update(['legacy_config_id' => ++$last]);
        }
    }

    public function down()
    {
        // Nada a desfazer: retirar o codigo soltaria os modelos ja vinculados.
    }
}
