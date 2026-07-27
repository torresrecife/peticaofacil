<?php

namespace App\Services;

use App\Peca;
use App\PeticaoNormalizada;
use Illuminate\Support\Facades\Auth;

class LegacyPecaMirrorService
{
    public function syncFromNormalized(PeticaoNormalizada $peticao): ?Peca
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $peticao->loadMissing(['modelo', 'legacyPeca']);
        $modelo = $peticao->modelo;

        if (!$modelo || !$modelo->legacy_tipo_id) {
            return null;
        }

        $peca = $peticao->legacyPeca ?: new Peca();

        $peca->tipo_id = $modelo->legacy_tipo_id;
        $peca->id_usu = $peticao->legacy_usuario_id ?: optional(Auth::user())->id_usu;
        $peca->nome_pecas = $peticao->nome_arquivo ?: $modelo->nome;
        $peca->nome_cli = $peticao->cliente_referencia ?: 'Sem cliente';
        $peca->cod_pecas = $peticao->conteudo_html;
        $peca->data_cad = $peticao->gerado_em ?: $peca->data_cad ?: now();
        $peca->cod_sav = $peticao->codigo_externo ?: $peca->cod_sav ?: $this->generateCodSav();
        $peca->save();

        $peticao->legacy_peca_id = $peca->id_pecas;
        $peticao->legacy_usuario_id = $peca->id_usu ?: $peticao->legacy_usuario_id;
        $peticao->codigo_externo = $peca->cod_sav;
        $peticao->nome_arquivo = $peca->nome_pecas ?: $peticao->nome_arquivo;

        return $peca;
    }

    protected function isEnabled(): bool
    {
        return (bool) config('legacy.mirror_legacy_pecas', true);
    }

    protected function generateCodSav(): string
    {
        return (string) ((int) round(microtime(true) * 1000));
    }
}
