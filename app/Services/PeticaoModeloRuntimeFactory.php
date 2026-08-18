<?php

namespace App\Services;

use App\PeticaoModelo;
use App\Support\PeticaoModeloRuntime;

class PeticaoModeloRuntimeFactory
{
    public function fromNormalized(PeticaoModelo $modelo)
    {
        $modelo->loadMissing(['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor']);

        return new PeticaoModeloRuntime([
            'id' => $modelo->id,
            'legacy_tipo_id' => $modelo->legacy_tipo_id,
            'tipo_id' => $modelo->tipo_id,
            'tipo_nome' => $modelo->tipo_nome,
            'nome_pre' => $modelo->nome_pre,
            'nome_pos' => $modelo->nome_pos,
            'cod_cabec' => $modelo->cod_cabec,
            'cod_rodap' => $modelo->cod_rodap,
            'tipo_stt' => $modelo->tipo_stt,
            'tipo_arq' => $modelo->tipo_arq,
            'id_db' => $modelo->id_db,
            'id_cliente' => $modelo->id_cliente,
            'id_setor' => $modelo->id_setor,
            'campos' => $modelo->campos,
            'paragrafos' => $modelo->paragrafos,
            'setor' => $modelo->setor,
            'cliente' => $modelo->cliente,
            'servidor' => $modelo->servidor,
            'is_normalized' => true,
            'source' => $modelo,
        ]);
    }
}
