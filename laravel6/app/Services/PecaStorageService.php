<?php

namespace App\Services;

use App\Peca;
use Illuminate\Support\Facades\Auth;

class PecaStorageService
{
    public function saveLegacy(int $legacyTipoId, string $legacyNome, array $payload, Peca $peca = null)
    {
        if ($legacyTipoId <= 0) {
            throw new \InvalidArgumentException('Nao foi possivel resolver o modelo legado para salvar a peca.');
        }

        $peca = $peca ?: new Peca();

        $peca->tipo_id = $legacyTipoId;
        $peca->id_usu = Auth::user()->id_usu;
        $peca->nome_pecas = $legacyNome;
        $peca->nome_cli = $payload['nome_cli'];
        $peca->cod_pecas = $payload['cod_pecas'];
        $peca->data_cad = now();
        $peca->cod_sav = $peca->cod_sav ?: $this->generateCodSav();
        $peca->save();

        return $peca;
    }

    protected function generateCodSav()
    {
        return (string) ((int) round(microtime(true) * 1000));
    }
}
