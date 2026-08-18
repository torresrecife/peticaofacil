<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLanguagetoolPreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('user_languagetool_preferences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('entry_type', 20);
            $table->string('token', 255);
            $table->string('rule_id', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'entry_type']);
            $table->index(['user_id', 'token']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_languagetool_preferences');
    }
}
