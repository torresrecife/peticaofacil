<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * As senhas dos servidores externos (NEO) ficavam em texto puro.
 * Esta migration amplia a coluna, porque o valor criptografado nao cabe
 * em 255 caracteres para senhas longas, e criptografa o que ja existe
 * com a chave da aplicacao (APP_KEY).
 *
 * Pode ser executada mais de uma vez: valores ja criptografados sao
 * mantidos como estao.
 */
class EncryptSqlServerProfilePasswords extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sql_server_profiles')) {
            return;
        }

        DB::statement('ALTER TABLE `sql_server_profiles` MODIFY `password` TEXT NULL');

        foreach (DB::table('sql_server_profiles')->select('id', 'password')->get() as $row) {
            if ($row->password === null || $row->password === '' || $this->decrypt($row->password) !== null) {
                continue;
            }

            DB::table('sql_server_profiles')
                ->where('id', $row->id)
                ->update(['password' => Crypt::encryptString($row->password)]);
        }
    }

    public function down()
    {
        if (!Schema::hasTable('sql_server_profiles')) {
            return;
        }

        // Volta os valores para texto puro. A coluna continua TEXT: reduzir
        // para 255 poderia truncar dados.
        foreach (DB::table('sql_server_profiles')->select('id', 'password')->get() as $row) {
            if ($row->password === null || $row->password === '') {
                continue;
            }

            $plain = $this->decrypt($row->password);
            if ($plain === null) {
                continue;
            }

            DB::table('sql_server_profiles')
                ->where('id', $row->id)
                ->update(['password' => $plain]);
        }
    }

    protected function decrypt($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $exception) {
            return null;
        }
    }
}
