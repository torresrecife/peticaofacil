<?php

namespace App\Services;

use App\Peca;
use App\PeticaoModelo;
use App\Tipo;
use Illuminate\Support\Facades\Auth;

class PecaStorageService
{
    public function save($modelo, array $payload, Peca $peca = null)
    {
        $legacyTipo = $this->resolveLegacyTipo($modelo);
        if (!$legacyTipo) {
            throw new \InvalidArgumentException('Nao foi possivel resolver o modelo legado para salvar a peca.');
        }

        $peca = $peca ?: new Peca();

        $peca->tipo_id = $legacyTipo->tipo_id;
        $peca->id_usu = Auth::id();
        $peca->nome_pecas = $legacyTipo->tipo_nome;
        $peca->nome_cli = $payload['nome_cli'];
        $peca->cod_pecas = $payload['cod_pecas'];
        $peca->data_cad = now();
        $peca->cod_sav = $peca->cod_sav ?: $this->generateCodSav();
        $peca->save();

        app(LegacyPecaSyncService::class)->syncPeca($peca, $legacyTipo);

        return $peca;
    }

    protected function resolveLegacyTipo($modelo)
    {
        if ($modelo instanceof Tipo) {
            return $modelo;
        }

        if ($modelo instanceof PeticaoModelo && $modelo->legacy_tipo_id) {
            return Tipo::find($modelo->legacy_tipo_id);
        }

        return null;
    }

    protected function generateCodSav()
    {
        return (string) ((int) round(microtime(true) * 1000));
    }
}
