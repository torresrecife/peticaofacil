<?php

namespace App\Services;

use App\SqlServerConfig;
use App\SqlServerProfile;
use Illuminate\Support\Facades\DB;

class NormalizedSqlServerConfigSyncService
{
    public function sync(SqlServerProfile $profile)
    {
        return DB::transaction(function () use ($profile) {
            $legacy = $profile->legacy_config_id ? SqlServerConfig::find($profile->legacy_config_id) : new SqlServerConfig();
            if (!$legacy) {
                $legacy = new SqlServerConfig();
            }

            $legacy->nome_db = $profile->nome;
            $legacy->ip_db = $profile->host;
            $legacy->data_db = $profile->database_name;
            $legacy->usu_db = $profile->username;
            $legacy->senha_db = $profile->password;
            $legacy->table_db = $profile->table_name;
            $legacy->chave_db = $profile->lookup_key;
            $legacy->query_db = $profile->base_query;
            $legacy->where_db = $profile->where_clause;
            $legacy->stt = $profile->status === 'ativo' ? 'Y' : 'N';
            $legacy->save();

            if ((int) $profile->legacy_config_id !== (int) $legacy->id_db) {
                $profile->legacy_config_id = $legacy->id_db;
                $profile->save();
            }

            return $legacy;
        });
    }

    public function syncLegacy(SqlServerConfig $config)
    {
        return DB::transaction(function () use ($config) {
            return SqlServerProfile::updateOrCreate(
                ['legacy_config_id' => $config->id_db],
                [
                    'nome' => $config->nome_db,
                    'host' => $config->ip_db,
                    'database_name' => $config->data_db,
                    'username' => $config->usu_db,
                    'password' => $config->senha_db,
                    'table_name' => $config->table_db,
                    'lookup_key' => $config->chave_db,
                    'base_query' => $config->query_db,
                    'where_clause' => $config->where_db,
                    'status' => $config->stt === 'Y' ? 'ativo' : 'inativo',
                ]
            );
        });
    }
}
