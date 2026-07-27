<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropUsersEmailUniqueIndex extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_unique'");

        if (!empty($indexes)) {
            DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
        }
    }

    public function down()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_unique'");

        if (empty($indexes)) {
            DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_email_unique (email)');
        }
    }
}
