<?php

namespace App\Services;

use App\Peca;
use App\PeticaoNormalizada;
use App\PeticaoVersao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeticaoNormalizedStorageService
{
    public function save(PeticaoNormalizada $peticao, array $payload, $origin = 'save')
    {
        return DB::transaction(function () use ($peticao, $payload, $origin) {
            $peticao->loadMissing(['modelo', 'legacyPeca.tipo']);

            $legacyPeca = $peticao->legacyPeca;
            $tipo = $legacyPeca && $legacyPeca->tipo ? $legacyPeca->tipo : null;

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

            if (!$peticao->user_id && Auth::check()) {
                $peticao->user_id = Auth::id();
            }

            if (!$legacyPeca && !$peticao->nome_arquivo) {
                $peticao->nome_arquivo = optional($peticao->modelo)->nome ?: 'Peticao normalizada';
            }

            $peticao->cliente_referencia = $payload['nome_cli'];
            $peticao->conteudo_html = $payload['cod_pecas'];
            $peticao->salvo_em = now();
            $peticao->save();

            $this->createVersionSnapshot($peticao, $origin);

            return $peticao->fresh(['modelo', 'legacyPeca.tipo', 'user']);
        });
    }

    public function restoreVersion(PeticaoNormalizada $peticao, PeticaoVersao $versao)
    {
        return $this->save($peticao, [
            'nome_cli' => $versao->cliente_referencia_snapshot,
            'cod_pecas' => $versao->conteudo_html_snapshot,
        ], 'restore');
    }

    public function createVersionSnapshot(PeticaoNormalizada $peticao, $origem)
    {
        $nextVersion = ((int) PeticaoVersao::where('peticao_id', $peticao->id)->max('versao_numero')) + 1;

        return PeticaoVersao::create([
            'peticao_id' => $peticao->id,
            'versao_numero' => $nextVersion,
            'legacy_peca_id_snapshot' => $peticao->legacy_peca_id,
            'legacy_usuario_id_snapshot' => $peticao->legacy_usuario_id,
            'user_id_snapshot' => $peticao->user_id,
            'codigo_externo_snapshot' => $peticao->codigo_externo,
            'cliente_referencia_snapshot' => $peticao->cliente_referencia,
            'conteudo_html_snapshot' => $peticao->conteudo_html,
            'campos_resolvidos_snapshot' => $peticao->campos_resolvidos,
            'origem_snapshot' => $origem,
            'criado_em' => now(),
        ]);
    }
}
