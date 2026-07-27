<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropUsersLoginUniqueIndex extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_login_usu_unique'");

        if (!empty($indexes)) {
            DB::statement('ALTER TABLE users DROP INDEX users_login_usu_unique');
        }
    }

    public function down()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_login_usu_unique'");

        if (empty($indexes)) {
            DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_login_usu_unique (login_usu)');
        }
    }
}
