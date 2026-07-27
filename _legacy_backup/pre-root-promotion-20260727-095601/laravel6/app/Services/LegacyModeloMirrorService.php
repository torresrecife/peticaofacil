<?php

namespace App\Services;

use App\PeticaoModelo;

class LegacyModeloMirrorService
{
    public function syncIfEnabled(PeticaoModelo $modelo)
    {
        if (!config('legacy.mirror_legacy_modelos')) {
            return null;
        }

        return app(NormalizedModeloLegacySyncService::class)->sync($modelo);
    }
}
