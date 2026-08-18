<?php

namespace App\Services;

use App\SqlServerProfile;
use Illuminate\Support\Facades\DB;

class NormalizedSqlServerConfigSyncService
{
    public function sync(SqlServerProfile $profile)
    {
        return DB::transaction(function () use ($profile) {
            $payload = [
                'nome_db' => $profile->nome,
                'ip_db' => $profile->host,
                'data_db' => $profile->database_name,
                'usu_db' => $profile->username,
                'senha_db' => $profile->password,
                'table_db' => $profile->table_name,
                'chave_db' => $profile->lookup_key,
                'query_db' => $profile->base_query,
                'where_db' => $profile->where_clause,
                'stt' => $profile->status === 'ativo' ? 'Y' : 'N',
            ];

            $legacyId = $profile->legacy_config_id ? (int) $profile->legacy_config_id : null;

            if ($legacyId) {
                DB::table('tp_config_db')->where('id_db', $legacyId)->update($payload);
            } else {
                $legacyId = (int) DB::table('tp_config_db')->insertGetId($payload);
            }

            if ((int) $profile->legacy_config_id !== $legacyId) {
                $profile->legacy_config_id = $legacyId;
                $profile->save();
            }

            return DB::table('tp_config_db')->where('id_db', $legacyId)->first();
        });
    }
}
