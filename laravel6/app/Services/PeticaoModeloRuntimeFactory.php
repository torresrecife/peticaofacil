<?php

namespace App\Services;

use App\PeticaoModelo;
use App\Support\PeticaoModeloRuntime;
use App\Tipo;

class PeticaoModeloRuntimeFactory
{
    public function fromPreferred(Tipo $tipo)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findLoadedMirrorForTipo(
            $tipo,
            ['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor', 'servidorLegacy']
        );

        if ($mirror) {
            return $this->fromNormalized($mirror);
        }

        $tipo->loadMissing(['campos.dados', 'paragrafos', 'setor', 'cliente', 'servidor']);

        return $this->fromLegacy($tipo);
    }

    public function fromNormalized(PeticaoModelo $modelo)
    {
        $modelo->loadMissing(['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor', 'servidorLegacy']);

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
            'servidorLegacy' => $modelo->servidorLegacy,
            'is_normalized' => true,
            'source' => $modelo,
        ]);
    }

    public function fromLegacy(Tipo $tipo)
    {
        return new PeticaoModeloRuntime([
            'id' => $tipo->tipo_id,
            'legacy_tipo_id' => $tipo->tipo_id,
            'tipo_id' => $tipo->tipo_id,
            'tipo_nome' => $tipo->tipo_nome,
            'nome_pre' => $tipo->nome_pre,
            'nome_pos' => $tipo->nome_pos,
            'cod_cabec' => $tipo->cod_cabec,
            'cod_rodap' => $tipo->cod_rodap,
            'tipo_stt' => $tipo->tipo_stt,
            'tipo_arq' => $tipo->tipo_arq,
            'id_db' => $tipo->id_db,
            'id_cliente' => $tipo->id_cliente,
            'id_setor' => $tipo->id_setor,
            'campos' => $tipo->campos,
            'paragrafos' => $tipo->paragrafos,
            'setor' => $tipo->setor,
            'cliente' => $tipo->cliente,
            'servidor' => $tipo->servidor,
            'servidorLegacy' => null,
            'is_normalized' => false,
            'source' => $tipo,
        ]);
    }
}
