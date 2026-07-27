<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeUsersEmailNullable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
    }

    public function down()
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
    }
}
