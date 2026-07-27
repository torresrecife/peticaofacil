<?php

namespace App\Services;

use App\SqlServerProfile;

class LegacySqlServerProfileMirrorService
{
    public function enabled(): bool
    {
        return (bool) config('legacy.mirror_legacy_sql_configs', false);
    }

    public function syncIfEnabled(SqlServerProfile $profile): void
    {
        if (!$this->enabled()) {
            return;
        }

        app(NormalizedSqlServerConfigSyncService::class)->sync($profile);
    }
}
