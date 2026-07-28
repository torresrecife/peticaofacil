<?php

namespace App\Services;

use App\PeticaoModelo;

class PeticaoModeloResolverService
{
    public function findNormalizedByLegacyTipoId($legacyTipoId)
    {
        if (!$legacyTipoId) {
            return null;
        }

        return PeticaoModelo::where('legacy_tipo_id', $legacyTipoId)->first();
    }

    public function findLoadedNormalizedByLegacyTipoId($legacyTipoId, array $relations = [])
    {
        if (!$legacyTipoId) {
            return null;
        }

        $query = PeticaoModelo::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->where('legacy_tipo_id', $legacyTipoId)->first();
    }
}
