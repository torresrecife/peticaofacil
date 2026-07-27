<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureUsersLegacyColumns extends Migration
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

        $missing = [];

        foreach ([
            'legacy_usuario_id',
            'nome_usu',
            'login_usu',
            'senha_usu',
            'email_usu',
            'nivel_usu',
            'acesso_usu',
            'data_cad',
            'id_setor',
            'id_cliente',
            'status_usu',
            'estados_usu',
            'comarca_usu',
        ] as $column) {
            if (!Schema::hasColumn('users', $column)) {
                $missing[] = $column;
            }
        }

        if (empty($missing)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($missing) {
            foreach ($missing as $column) {
                switch ($column) {
                    case 'legacy_usuario_id':
                        $table->unsignedInteger('legacy_usuario_id')->nullable()->unique()->after('id');
                        break;
                    case 'nome_usu':
                        $table->string('nome_usu', 50)->nullable();
                        break;
                    case 'login_usu':
                        $table->string('login_usu', 50)->nullable()->unique();
                        break;
                    case 'senha_usu':
                        $table->string('senha_usu', 255)->nullable();
                        break;
                    case 'email_usu':
                        $table->string('email_usu', 50)->nullable();
                        break;
                    case 'nivel_usu':
                        $table->string('nivel_usu', 3)->default('USU');
                        break;
                    case 'acesso_usu':
                        $table->dateTime('acesso_usu')->nullable();
                        break;
                    case 'data_cad':
                        $table->dateTime('data_cad')->nullable();
                        break;
                    case 'id_setor':
                        $table->unsignedInteger('id_setor')->nullable();
                        break;
                    case 'id_cliente':
                        $table->string('id_cliente', 255)->nullable()->default('0');
                        break;
                    case 'status_usu':
                        $table->string('status_usu', 3)->default('ATI');
                        break;
                    case 'estados_usu':
                        $table->string('estados_usu', 255)->nullable();
                        break;
                    case 'comarca_usu':
                        $table->string('comarca_usu', 255)->nullable();
                        break;
                }
            }
        });
    }

    public function down()
    {
        // rollback destrutivo nao e apropriado para esta migracao de transicao.
    }
}
