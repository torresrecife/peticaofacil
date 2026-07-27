<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSqlServerProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('sql_server_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_config_id')->nullable()->unique();
            $table->string('nome', 255);
            $table->string('host', 255);
            $table->string('database_name', 255);
            $table->string('username', 255);
            $table->string('password', 255)->nullable();
            $table->string('table_name', 255)->nullable();
            $table->string('lookup_key', 255)->nullable();
            $table->longText('base_query')->nullable();
            $table->longText('where_clause')->nullable();
            $table->string('status', 20)->default('ativo');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sql_server_profiles');
    }
}
