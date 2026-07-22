<?php

namespace App\Services;

use App\Peca;
use App\PeticaoNormalizada;
use App\PeticaoVersao;
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

            if (!$legacyPeca && $tipo && !$peticao->nome_arquivo) {
                $peticao->nome_arquivo = $tipo->tipo_nome;
            }

            $peticao->cliente_referencia = $payload['nome_cli'];
            $peticao->conteudo_html = $payload['cod_pecas'];
            $peticao->salvo_em = now();
            $peticao->save();

            $this->createVersionSnapshot($peticao, 'save');

            return $peticao->fresh(['modelo', 'legacyPeca.tipo', 'legacyUsuario']);
        });
    }

    public function createVersionSnapshot(PeticaoNormalizada $peticao, $origem)
    {
        $nextVersion = ((int) PeticaoVersao::where('peticao_id', $peticao->id)->max('versao_numero')) + 1;

        return PeticaoVersao::create([
            'peticao_id' => $peticao->id,
            'versao_numero' => $nextVersion,
            'legacy_peca_id_snapshot' => $peticao->legacy_peca_id,
            'legacy_usuario_id_snapshot' => $peticao->legacy_usuario_id,
            'codigo_externo_snapshot' => $peticao->codigo_externo,
            'cliente_referencia_snapshot' => $peticao->cliente_referencia,
            'conteudo_html_snapshot' => $peticao->conteudo_html,
            'campos_resolvidos_snapshot' => $peticao->campos_resolvidos,
            'origem_snapshot' => $origem,
            'criado_em' => now(),
        ]);
    }
}
