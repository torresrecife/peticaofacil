<?php

namespace App\Services;

use App\PeticaoModelo;
use App\Tipo;

class PeticaoModeloResolverService
{
    public function findNormalizedForLegacyTipo(Tipo $tipo)
    {
        return $this->findNormalizedByLegacyTipoId($tipo->tipo_id);
    }

    public function findNormalizedByLegacyTipoId($legacyTipoId)
    {
        if (!$legacyTipoId) {
            return null;
        }

        return PeticaoModelo::where('legacy_tipo_id', $legacyTipoId)->first();
    }

    public function findLoadedNormalizedForLegacyTipo(Tipo $tipo, array $relations = [])
    {
        $query = PeticaoModelo::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->where('legacy_tipo_id', $tipo->tipo_id)->first();
    }
}
