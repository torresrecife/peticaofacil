<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeUserModelFavoritesLegacyUserNullable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_model_favorites') || !Schema::hasColumn('user_model_favorites', 'legacy_usuario_id')) {
            return;
        }

        DB::statement('ALTER TABLE user_model_favorites MODIFY legacy_usuario_id INT UNSIGNED NULL');
    }

    public function down()
    {
        if (!Schema::hasTable('user_model_favorites') || !Schema::hasColumn('user_model_favorites', 'legacy_usuario_id')) {
            return;
        }

        DB::statement('UPDATE user_model_favorites SET legacy_usuario_id = 0 WHERE legacy_usuario_id IS NULL');
        DB::statement('ALTER TABLE user_model_favorites MODIFY legacy_usuario_id INT UNSIGNED NOT NULL');
    }
}
