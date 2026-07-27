<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserModelFavoritesTable extends Migration
{
    public function up()
    {
        Schema::create('user_model_favorites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_usuario_id');
            $table->string('source', 20);
            $table->unsignedBigInteger('modelo_id')->default(0);
            $table->unsignedInteger('legacy_tipo_id')->default(0);
            $table->timestamps();

            $table->unique(['legacy_usuario_id', 'source', 'modelo_id', 'legacy_tipo_id'], 'user_model_favorites_unique');
            $table->index(['legacy_usuario_id', 'source'], 'user_model_favorites_user_source_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_model_favorites');
    }
}
