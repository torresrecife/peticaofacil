<?php

namespace App\Services;

use App\Tipo;

class LegacyModeloRouteAccessService
{
    public function findTipoOrFail($tipoId): Tipo
    {
        return Tipo::findOrFail($tipoId);
    }
}
