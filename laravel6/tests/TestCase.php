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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('tp_pecas_tb');
        Schema::dropIfExists('tp_dados_tb');
        Schema::dropIfExists('tp_inputs_tb');
        Schema::dropIfExists('tp_funda_tb');
        Schema::dropIfExists('tp_tipo_tb');
        Schema::dropIfExists('tp_config_db');
        Schema::dropIfExists('tp_clientes_db');
        Schema::dropIfExists('tp_setor_tb');
        Schema::dropIfExists('tp_usu_tb');

        Schema::enableForeignKeyConstraints();

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

        Schema::create('tp_config_db', function (Blueprint $table) {
            $table->increments('id_db');
            $table->string('nome_db', 255);
            $table->string('ip_db', 255)->nullable();
            $table->string('data_db', 255)->nullable();
            $table->string('usu_db', 255)->nullable();
            $table->string('senha_db', 255)->nullable();
            $table->string('table_db', 255)->nullable();
            $table->string('chave_db', 255)->nullable();
            $table->text('query_db')->nullable();
            $table->text('where_db')->nullable();
            $table->string('stt', 1)->default('Y');
        });

        Schema::create('tp_tipo_tb', function (Blueprint $table) {
            $table->increments('tipo_id');
            $table->unsignedInteger('id_db')->nullable();
            $table->text('nome_pre')->nullable();
            $table->text('nome_pos')->nullable();
            $table->string('tipo_nome', 255);
            $table->unsignedInteger('id_cliente')->nullable();
            $table->dateTime('tipo_data')->nullable();
            $table->string('tipo_stt', 1)->default('Y');
            $table->unsignedInteger('id_setor')->nullable();
            $table->longText('cod_cabec')->nullable();
            $table->longText('cod_rodap')->nullable();
            $table->string('tipo_arq', 255)->nullable();
        });

        Schema::create('tp_funda_tb', function (Blueprint $table) {
            $table->increments('fund_id');
            $table->unsignedInteger('tipo_id');
            $table->string('fund_titulo', 255)->nullable();
            $table->longText('fund_text')->nullable();
            $table->integer('fund_order')->default(0);
            $table->dateTime('fund_data')->nullable();
            $table->string('fund_visi', 1)->default('Y');
            $table->string('fund_stt', 1)->default('Y');
        });

        Schema::create('tp_inputs_tb', function (Blueprint $table) {
            $table->increments('id_input');
            $table->unsignedInteger('tipo_id');
            $table->text('input_pre')->nullable();
            $table->text('input_pos')->nullable();
            $table->string('input_title', 255)->nullable();
            $table->string('input_tipo', 50)->nullable();
            $table->string('input_db', 255)->nullable();
            $table->string('input_val', 255)->nullable();
            $table->string('input_alt', 255)->nullable();
            $table->integer('input_ext')->nullable();
            $table->integer('input_cols')->nullable();
            $table->integer('input_rols')->nullable();
            $table->text('input_focu')->nullable();
            $table->text('input_load')->nullable();
            $table->text('input_blur')->nullable();
            $table->integer('input_width')->nullable();
            $table->string('input_req', 1)->default('N');
            $table->integer('input_order')->default(0);
            $table->string('listsel', 1)->default('N');
            $table->string('nomepet', 1)->default('N');
            $table->string('hide', 1)->default('N');
            $table->text('texto_padrao')->nullable();
            $table->string('add_class', 255)->nullable();
        });

        Schema::create('tp_dados_tb', function (Blueprint $table) {
            $table->increments('id_dados');
            $table->unsignedInteger('id_input');
            $table->string('nome_dados', 255);
            $table->string('return_1', 255)->nullable();
            $table->string('return_2', 255)->nullable();
            $table->string('return_3', 255)->nullable();
            $table->string('return_4', 255)->nullable();
            $table->string('return_5', 255)->nullable();
            $table->dateTime('data_cad')->nullable();
            $table->integer('dados_order')->default(0);
            $table->unsignedInteger('id_setor')->nullable();
            $table->string('listsel', 1)->default('N');
        });

        Schema::create('tp_pecas_tb', function (Blueprint $table) {
            $table->increments('id_pecas');
            $table->unsignedInteger('tipo_id');
            $table->unsignedInteger('id_usu')->nullable();
            $table->string('nome_pecas', 255)->nullable();
            $table->string('nome_cli', 500);
            $table->longText('cod_pecas');
            $table->dateTime('data_cad')->nullable();
            $table->string('cod_sav', 255)->nullable();
        });
    }
}
