<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLegacySchema();
    }

    protected function setUpLegacySchema(): void
    {
        Schema::dropIfExists('tp_clientes_db');
        Schema::dropIfExists('tp_setor_tb');
        Schema::dropIfExists('tp_usu_tb');

        Schema::create('tp_setor_tb', function (Blueprint $table) {
            $table->increments('id_setor');
            $table->string('nome_setor', 500);
            $table->string('cod_setor', 50)->nullable();
            $table->dateTime('data_cad')->nullable();
        });

        Schema::create('tp_clientes_db', function (Blueprint $table) {
            $table->increments('cliente_id');
            $table->string('cliente_name', 500);
            $table->string('cliente_cod', 500)->nullable();
            $table->integer('cliente_area')->nullable();
            $table->string('cliente_status', 1)->default('Y');
            $table->dateTime('cliente_creator')->nullable();
        });

        Schema::create('tp_usu_tb', function (Blueprint $table) {
            $table->increments('id_usu');
            $table->string('nome_usu', 50);
            $table->string('login_usu', 50)->unique();
            $table->string('senha_usu', 255);
            $table->string('email_usu', 50)->nullable();
            $table->string('nivel_usu', 3)->default('USU');
            $table->dateTime('acesso_usu')->nullable();
            $table->dateTime('data_cad')->nullable();
            $table->integer('id_setor')->nullable();
            $table->string('id_cliente', 255)->nullable()->default('0');
            $table->string('status_usu', 3)->default('ATI');
            $table->string('estados_usu', 255)->nullable();
            $table->string('comarca_usu', 255)->nullable();
        });
    }
}
