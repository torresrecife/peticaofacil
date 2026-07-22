<?php

namespace App\Services;

use App\Peca;
use App\PeticaoNormalizada;
use App\Tipo;
use Illuminate\Support\Facades\DB;

class PeticaoNormalizedStorageService
{
    public function save(PeticaoNormalizada $peticao, array $payload)
    {
        return DB::transaction(function () use ($peticao, $payload) {
            $peticao->loadMissing(['modelo', 'legacyPeca.tipo']);

            $legacyPeca = $peticao->legacyPeca;
            $tipo = $legacyPeca && $legacyPeca->tipo
                ? $legacyPeca->tipo
                : Tipo::find(optional($peticao->modelo)->legacy_tipo_id);

            if (!$legacyPeca && $tipo) {
                $legacyPeca = new Peca();
                $legacyPeca->tipo_id = $tipo->tipo_id;
                $legacyPeca->id_usu = $peticao->legacy_usuario_id;
                $legacyPeca->nome_pecas = $tipo->tipo_nome;
                $legacyPeca->cod_sav = $peticao->codigo_externo;
            }

            if ($legacyPeca) {
                $legacyPeca->nome_cli = $payload['nome_cli'];
                $legacyPeca->cod_pecas = $payload['cod_pecas'];
                $legacyPeca->data_cad = now();
                $legacyPeca->save();

                $peticao->legacy_peca_id = $legacyPeca->id_pecas;
                $peticao->legacy_usuario_id = $legacyPeca->id_usu;
                $peticao->codigo_externo = $legacyPeca->cod_sav;
                $peticao->nome_arquivo = $legacyPeca->nome_pecas ?: $peticao->nome_arquivo;
            }

            $peticao->cliente_referencia = $payload['nome_cli'];
            $peticao->conteudo_html = $payload['cod_pecas'];
            $peticao->salvo_em = now();
            $peticao->save();

            return $peticao->fresh(['modelo', 'legacyPeca.tipo', 'legacyUsuario']);
        });
    }
}
