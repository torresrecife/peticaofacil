<?php

namespace App\Services;

use App\Peca;
use App\PeticaoModelo;
use App\Tipo;
use LogicException;
use Illuminate\Support\Facades\Auth;

class PecaStorageService
{
    public function save($modelo, array $payload, Peca $peca = null)
    {
        if ($this->mustUseNormalizedStorage($modelo)) {
            throw new LogicException('Modelos normalizados ou espelhados devem usar a persistencia principal em peticoes.');
        }

        $legacyTipoId = $this->resolveLegacyTipoId($modelo);
        if (!$legacyTipoId) {
            throw new \InvalidArgumentException('Nao foi possivel resolver o modelo legado para salvar a peca.');
        }

        $peca = $peca ?: new Peca();

        $peca->tipo_id = $legacyTipoId;
        $peca->id_usu = Auth::user()->id_usu;
        $peca->nome_pecas = $this->resolveLegacyNome($modelo);
        $peca->nome_cli = $payload['nome_cli'];
        $peca->cod_pecas = $payload['cod_pecas'];
        $peca->data_cad = now();
        $peca->cod_sav = $peca->cod_sav ?: $this->generateCodSav();
        $peca->save();

        app(LegacyPecaSyncService::class)->syncPeca($peca, $modelo);

        return $peca;
    }

    protected function mustUseNormalizedStorage($modelo): bool
    {
        if ($modelo instanceof PeticaoModelo) {
            return true;
        }

        if ($modelo instanceof Tipo) {
            return PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->exists();
        }

        return false;
    }

    protected function resolveLegacyTipoId($modelo)
    {
        if ($modelo instanceof Tipo) {
            return $modelo->tipo_id;
        }

        if ($modelo instanceof PeticaoModelo && $modelo->legacy_tipo_id) {
            return $modelo->legacy_tipo_id;
        }

        return 0;
    }

    protected function resolveLegacyNome($modelo)
    {
        if ($modelo instanceof PeticaoModelo) {
            return $modelo->nome;
        }

        if ($modelo instanceof Tipo) {
            return $modelo->tipo_nome;
        }

        return 'Peticao';
    }

    protected function generateCodSav()
    {
        return (string) ((int) round(microtime(true) * 1000));
    }
}
