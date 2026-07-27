<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareUsersTableForLegacyAuth extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('legacy_usuario_id')->nullable()->unique();
                $table->string('name')->nullable();
                $table->string('email')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('nome_usu', 50)->nullable();
                $table->string('login_usu', 50)->nullable()->unique();
                $table->string('senha_usu', 255)->nullable();
                $table->string('email_usu', 50)->nullable();
                $table->string('nivel_usu', 3)->default('USU');
                $table->dateTime('acesso_usu')->nullable();
                $table->dateTime('data_cad')->nullable();
                $table->unsignedInteger('id_setor')->nullable();
                $table->string('id_cliente', 255)->nullable()->default('0');
                $table->string('status_usu', 3)->default('ATI');
                $table->string('estados_usu', 255)->nullable();
                $table->string('comarca_usu', 255)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'legacy_usuario_id')) {
                $table->unsignedInteger('legacy_usuario_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'nome_usu')) {
                $table->string('nome_usu', 50)->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'login_usu')) {
                $table->string('login_usu', 50)->nullable()->unique()->after('nome_usu');
            }
            if (!Schema::hasColumn('users', 'senha_usu')) {
                $table->string('senha_usu', 255)->nullable()->after('login_usu');
            }
            if (!Schema::hasColumn('users', 'email_usu')) {
                $table->string('email_usu', 50)->nullable()->after('senha_usu');
            }
            if (!Schema::hasColumn('users', 'nivel_usu')) {
                $table->string('nivel_usu', 3)->default('USU')->after('email_usu');
            }
            if (!Schema::hasColumn('users', 'acesso_usu')) {
                $table->dateTime('acesso_usu')->nullable()->after('nivel_usu');
            }
            if (!Schema::hasColumn('users', 'data_cad')) {
                $table->dateTime('data_cad')->nullable()->after('acesso_usu');
            }
            if (!Schema::hasColumn('users', 'id_setor')) {
                $table->unsignedInteger('id_setor')->nullable()->after('data_cad');
            }
            if (!Schema::hasColumn('users', 'id_cliente')) {
                $table->string('id_cliente', 255)->nullable()->default('0')->after('id_setor');
            }
            if (!Schema::hasColumn('users', 'status_usu')) {
                $table->string('status_usu', 3)->default('ATI')->after('id_cliente');
            }
            if (!Schema::hasColumn('users', 'estados_usu')) {
                $table->string('estados_usu', 255)->nullable()->after('status_usu');
            }
            if (!Schema::hasColumn('users', 'comarca_usu')) {
                $table->string('comarca_usu', 255)->nullable()->after('estados_usu');
            }
        });
    }

    public function down()
    {
        // Mantemos a tabela users; rollback destrutivo nao e apropriado aqui.
    }
}
