<?php

namespace App\Services;

use App\Peca;
use App\Tipo;
use Illuminate\Support\Facades\Auth;

class PecaStorageService
{
    public function save(Tipo $modelo, array $payload, Peca $peca = null)
    {
        $peca = $peca ?: new Peca();

        $peca->tipo_id = $modelo->tipo_id;
        $peca->id_usu = Auth::id();
        $peca->nome_pecas = $modelo->tipo_nome;
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
