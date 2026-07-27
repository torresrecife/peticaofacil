<?php

namespace App\Services;

use App\InputCampo;
use App\Paragrafo;
use App\Tipo;

class LegacyModeloAdminAccessService
{
    public function findTipoOrFail($tipoId): Tipo
    {
        return Tipo::findOrFail($tipoId);
    }

    public function findParagrafoForTipoOrFail($tipoId, $paragrafoId): Paragrafo
    {
        return Paragrafo::where('tipo_id', $tipoId)
            ->where('fund_id', $paragrafoId)
            ->firstOrFail();
    }

    public function findCampoForTipoOrFail($tipoId, $campoId): InputCampo
    {
        return InputCampo::where('tipo_id', $tipoId)
            ->where('id_input', $campoId)
            ->firstOrFail();
    }
}
