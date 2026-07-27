<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
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
        $tables = [
            'peticao_versoes',
            'peticoes',
            'peticao_modelo_campo_opcoes',
            'peticao_modelo_campos',
            'peticao_modelo_paragrafos',
            'peticao_modelos',
            'lista_itens',
            'lista_grupos',
            'user_model_favorites',
            'sql_server_profiles',
            'users',
            'tp_pecas_tb',
            'tp_dados_tb',
            'tp_inputs_tb',
            'tp_funda_tb',
            'tp_tipo_tb',
            'tp_lista_tb',
            'tp_grupo_tb',
            'tp_config_db',
            'tp_clientes_db',
            'tp_setor_tb',
            'tp_usu_tb',
        ];

        Schema::disableForeignKeyConstraints();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        Schema::enableForeignKeyConstraints();

        $this->createOrResetTable('tp_setor_tb', function (Blueprint $table) {
            $table->increments('id_setor');
            $table->string('nome_setor', 500);
            $table->string('cod_setor', 50)->nullable();
            $table->dateTime('data_cad')->nullable();
        });

        $this->createOrResetTable('tp_clientes_db', function (Blueprint $table) {
            $table->increments('cliente_id');
            $table->string('cliente_name', 500);
            $table->string('cliente_cod', 500)->nullable();
            $table->integer('cliente_area')->nullable();
            $table->string('cliente_status', 1)->default('Y');
            $table->dateTime('cliente_creator')->nullable();
        });

        $this->createOrResetTable('tp_usu_tb', function (Blueprint $table) {
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

        $this->createOrResetTable('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_usuario_id')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('nome_usu', 50)->nullable();
            $table->string('login_usu', 50)->nullable();
            $table->string('senha_usu', 255)->nullable();
            $table->string('email_usu', 50)->nullable();
            $table->string('nivel_usu', 3)->default('USU');
            $table->dateTime('acesso_usu')->nullable();
            $table->dateTime('data_cad')->nullable();
            $table->integer('id_setor')->nullable();
            $table->string('id_cliente', 255)->nullable()->default('0');
            $table->string('status_usu', 3)->default('ATI');
            $table->string('estados_usu', 255)->nullable();
            $table->string('comarca_usu', 255)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $this->createOrResetTable('tp_config_db', function (Blueprint $table) {
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

        $this->createOrResetTable('sql_server_profiles', function (Blueprint $table) {
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

        $this->createOrResetTable('tp_tipo_tb', function (Blueprint $table) {
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

        $this->createOrResetTable('tp_grupo_tb', function (Blueprint $table) {
            $table->increments('id_grupo');
            $table->string('nome_grupo', 500);
            $table->string('titulo_1', 500)->nullable();
            $table->string('titulo_2', 500)->nullable();
            $table->string('titulo_3', 500)->nullable();
            $table->string('titulo_4', 500)->nullable();
            $table->dateTime('data_cad')->nullable();
        });

        $this->createOrResetTable('tp_lista_tb', function (Blueprint $table) {
            $table->increments('id_lista');
            $table->unsignedInteger('id_grupo');
            $table->string('nome_lista', 500)->nullable();
            $table->string('return_1', 500)->nullable();
            $table->string('return_2', 500)->nullable();
            $table->string('return_3', 500)->nullable();
            $table->string('return_4', 500)->nullable();
            $table->string('return_5', 500)->nullable();
            $table->string('return_6', 500)->nullable();
            $table->dateTime('data_cad')->nullable();
            $table->unsignedInteger('id_setor')->nullable();
        });

        $this->createOrResetTable('lista_grupos', function (Blueprint $table) {
            $table->unsignedInteger('id_grupo')->primary();
            $table->unsignedInteger('legacy_grupo_id')->nullable()->unique();
            $table->string('nome_grupo', 500);
            $table->dateTime('data_cad')->nullable();
            $table->timestamps();
        });

        $this->createOrResetTable('lista_itens', function (Blueprint $table) {
            $table->unsignedInteger('id_lista')->primary();
            $table->unsignedInteger('legacy_lista_id')->nullable()->unique();
            $table->unsignedInteger('id_grupo');
            $table->string('nome_lista', 500)->nullable();
            $table->string('return_1', 500)->nullable();
            $table->string('return_2', 500)->nullable();
            $table->string('return_3', 500)->nullable();
            $table->string('return_4', 500)->nullable();
            $table->string('return_5', 500)->nullable();
            $table->string('return_6', 500)->nullable();
            $table->dateTime('data_cad')->nullable();
            $table->unsignedInteger('id_setor')->nullable();
            $table->timestamps();
        });

        $this->createOrResetTable('tp_funda_tb', function (Blueprint $table) {
            $table->increments('fund_id');
            $table->unsignedInteger('tipo_id');
            $table->string('fund_titulo', 255)->nullable();
            $table->longText('fund_text')->nullable();
            $table->integer('fund_order')->default(0);
            $table->dateTime('fund_data')->nullable();
            $table->string('fund_visi', 1)->default('Y');
            $table->string('fund_stt', 1)->default('Y');
        });

        $this->createOrResetTable('tp_inputs_tb', function (Blueprint $table) {
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
            $table->string('hide', 20)->default('true');
            $table->text('texto_padrao')->nullable();
            $table->string('add_class', 255)->nullable();
        });

        $this->createOrResetTable('tp_dados_tb', function (Blueprint $table) {
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

        $this->createOrResetTable('tp_pecas_tb', function (Blueprint $table) {
            $table->increments('id_pecas');
            $table->unsignedInteger('tipo_id');
            $table->unsignedInteger('id_usu')->nullable();
            $table->string('nome_pecas', 255)->nullable();
            $table->string('nome_cli', 500);
            $table->longText('cod_pecas');
            $table->dateTime('data_cad')->nullable();
            $table->string('cod_sav', 255)->nullable();
        });

        $this->createOrResetTable('peticao_modelos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_tipo_id')->nullable()->unique();
            $table->unsignedInteger('legacy_cliente_id')->nullable();
            $table->unsignedInteger('legacy_setor_id')->nullable();
            $table->unsignedInteger('legacy_sql_config_id')->nullable();
            $table->string('nome', 255);
            $table->string('slug', 255)->nullable();
            $table->string('status', 20)->default('ativo');
            $table->string('arquivo_padrao', 50)->nullable();
            $table->longText('cabecalho_html')->nullable();
            $table->longText('rodape_html')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();
        });

        $this->createOrResetTable('user_model_favorites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('legacy_usuario_id');
            $table->string('source', 20);
            $table->unsignedBigInteger('modelo_id')->default(0);
            $table->unsignedInteger('legacy_tipo_id')->default(0);
            $table->timestamps();
        });

        $this->createOrResetTable('peticao_modelo_paragrafos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('modelo_id');
            $table->unsignedInteger('legacy_fund_id')->nullable()->unique();
            $table->string('titulo', 255)->nullable();
            $table->longText('conteudo_html')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('visivel')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        $this->createOrResetTable('peticao_modelo_campos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('modelo_id');
            $table->unsignedInteger('legacy_input_id')->nullable()->unique();
            $table->string('rotulo', 255)->nullable();
            $table->string('token', 255)->unique();
            $table->string('tipo', 50);
            $table->string('origem_coluna', 255)->nullable();
            $table->string('origem_alias', 255)->nullable();
            $table->text('prefixo')->nullable();
            $table->text('sufixo')->nullable();
            $table->text('valor_padrao')->nullable();
            $table->string('classe_css', 255)->nullable();
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('colunas_layout')->nullable();
            $table->unsignedInteger('linhas_layout')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('obrigatorio')->default(false);
            $table->boolean('visivel')->default(true);
            $table->boolean('gera_nome_arquivo')->default(false);
            $table->text('eventos_frontend')->nullable();
            $table->timestamps();
        });

        $this->createOrResetTable('peticao_modelo_campo_opcoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('campo_id');
            $table->unsignedInteger('legacy_dado_id')->nullable()->unique();
            $table->string('rotulo', 255);
            $table->text('valor_retorno')->nullable();
            $table->text('valores_extras')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
        });

        $this->createOrResetTable('peticoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('legacy_peca_id')->nullable()->unique();
            $table->unsignedBigInteger('modelo_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('legacy_usuario_id')->nullable();
            $table->string('codigo_externo', 255)->nullable();
            $table->string('nome_arquivo', 500);
            $table->string('cliente_referencia', 500)->nullable();
            $table->longText('conteudo_html');
            $table->text('campos_resolvidos')->nullable();
            $table->timestamp('gerado_em')->nullable();
            $table->timestamp('salvo_em')->nullable();
            $table->timestamps();
        });

        $this->createOrResetTable('peticao_versoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('peticao_id');
            $table->unsignedInteger('versao_numero');
            $table->unsignedInteger('legacy_peca_id_snapshot')->nullable();
            $table->unsignedInteger('legacy_usuario_id_snapshot')->nullable();
            $table->unsignedBigInteger('user_id_snapshot')->nullable();
            $table->string('codigo_externo_snapshot', 255)->nullable();
            $table->string('cliente_referencia_snapshot', 500)->nullable();
            $table->longText('conteudo_html_snapshot');
            $table->text('campos_resolvidos_snapshot')->nullable();
            $table->string('origem_snapshot', 50)->default('save');
            $table->timestamp('criado_em')->nullable();
            $table->timestamps();
        });
    }

    protected function createOrResetTable(string $table, \Closure $callback): void
    {
        if (!Schema::hasTable($table)) {
            try {
                Schema::create($table, $callback);
            } catch (\Throwable $e) {
                if (!Schema::hasTable($table)) {
                    throw $e;
                }

                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table($table)->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return;
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table($table)->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Schema::dropIfExists($table);
            Schema::create($table, $callback);
        }
    }
}
