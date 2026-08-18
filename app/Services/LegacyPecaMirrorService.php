<?php

namespace App\Services;

use App\PeticaoNormalizada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LegacyPecaMirrorService
{
    public function syncFromNormalized(PeticaoNormalizada $peticao)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $peticao->loadMissing(['modelo']);
        $modelo = $peticao->modelo;

        if (!$modelo || !$modelo->legacy_tipo_id) {
            return null;
        }

        $legacyId = $peticao->legacy_peca_id ? (int) $peticao->legacy_peca_id : null;
        $existing = $legacyId
            ? DB::table('tp_pecas_tb')->where('id_pecas', $legacyId)->first()
            : null;
        $legacyUserId = $peticao->legacy_usuario_id ?: optional(Auth::user())->id_usu;
        $nomeArquivo = $peticao->nome_arquivo ?: $modelo->nome;
        $codigoExterno = $peticao->codigo_externo ?: ($existing->cod_sav ?? null) ?: $this->generateCodSav();

        $payload = [
            'tipo_id' => $modelo->legacy_tipo_id,
            'id_usu' => $legacyUserId,
            'nome_pecas' => $nomeArquivo,
            'nome_cli' => $peticao->cliente_referencia ?: 'Sem cliente',
            'cod_pecas' => $peticao->conteudo_html,
            'data_cad' => $peticao->gerado_em ?: ($existing->data_cad ?? null) ?: now(),
            'cod_sav' => $codigoExterno,
        ];

        if ($legacyId && $existing) {
            DB::table('tp_pecas_tb')->where('id_pecas', $legacyId)->update($payload);
        } else {
            $legacyId = (int) DB::table('tp_pecas_tb')->insertGetId($payload);
        }

        $peticao->legacy_peca_id = $legacyId;
        $peticao->legacy_usuario_id = $legacyUserId ?: $peticao->legacy_usuario_id;
        $peticao->codigo_externo = $codigoExterno;
        $peticao->nome_arquivo = $nomeArquivo ?: $peticao->nome_arquivo;

        return DB::table('tp_pecas_tb')->where('id_pecas', $legacyId)->first();
    }

    protected function isEnabled(): bool
    {
        return (bool) config('legacy.mirror_legacy_pecas', false);
    }

    protected function generateCodSav(): string
    {
        return (string) ((int) round(microtime(true) * 1000));
    }
}
