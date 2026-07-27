<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserLinksToNormalizedEntities extends Migration
{
    public function up()
    {
        Schema::table('user_model_favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('user_model_favorites', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index(['user_id', 'source'], 'user_model_favorites_user_id_source_index');
            }
        });

        Schema::table('peticoes', function (Blueprint $table) {
            if (!Schema::hasColumn('peticoes', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('modelo_id');
                $table->index('user_id', 'peticoes_user_id_index');
            }
        });

        Schema::table('peticao_versoes', function (Blueprint $table) {
            if (!Schema::hasColumn('peticao_versoes', 'user_id_snapshot')) {
                $table->unsignedBigInteger('user_id_snapshot')->nullable()->after('legacy_usuario_id_snapshot');
                $table->index('user_id_snapshot', 'peticao_versoes_user_id_snapshot_index');
            }
        });

        $this->backfillUserLinks();
    }

    public function down()
    {
        Schema::table('peticao_versoes', function (Blueprint $table) {
            if (Schema::hasColumn('peticao_versoes', 'user_id_snapshot')) {
                $table->dropIndex('peticao_versoes_user_id_snapshot_index');
                $table->dropColumn('user_id_snapshot');
            }
        });

        Schema::table('peticoes', function (Blueprint $table) {
            if (Schema::hasColumn('peticoes', 'user_id')) {
                $table->dropIndex('peticoes_user_id_index');
                $table->dropColumn('user_id');
            }
        });

        Schema::table('user_model_favorites', function (Blueprint $table) {
            if (Schema::hasColumn('user_model_favorites', 'user_id')) {
                $table->dropIndex('user_model_favorites_user_id_source_index');
                $table->dropColumn('user_id');
            }
        });
    }

    protected function backfillUserLinks(): void
    {
        $map = DB::table('users')
            ->whereNotNull('legacy_usuario_id')
            ->pluck('id', 'legacy_usuario_id');

        foreach ($map as $legacyId => $userId) {
            DB::table('user_model_favorites')
                ->where('legacy_usuario_id', $legacyId)
                ->update(['user_id' => $userId]);

            DB::table('peticoes')
                ->where('legacy_usuario_id', $legacyId)
                ->update(['user_id' => $userId]);

            DB::table('peticao_versoes')
                ->where('legacy_usuario_id_snapshot', $legacyId)
                ->update(['user_id_snapshot' => $userId]);
        }
    }
}
