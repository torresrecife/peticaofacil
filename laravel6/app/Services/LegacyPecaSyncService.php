<?php

namespace App\Services;

use App\Peca;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Tipo;
use Illuminate\Support\Facades\DB;

class LegacyPecaSyncService
{
    public function syncPeca(Peca $peca, Tipo $tipo = null)
    {
        $tipo = $tipo ?: $peca->tipo ?: Tipo::find($peca->tipo_id);
        if (!$tipo) {
            return null;
        }

        $modelo = PeticaoModelo::where('legacy_tipo_id', $tipo->tipo_id)->first();
        if (!$modelo) {
            return null;
        }

        return PeticaoNormalizada::updateOrCreate(
            ['legacy_peca_id' => $peca->id_pecas],
            [
                'modelo_id' => $modelo->id,
                'legacy_usuario_id' => $peca->id_usu ?: null,
                'codigo_externo' => $peca->cod_sav ?: null,
                'nome_arquivo' => $peca->nome_pecas ?: $tipo->tipo_nome,
                'cliente_referencia' => $peca->nome_cli,
                'conteudo_html' => $peca->cod_pecas,
                'campos_resolvidos' => [
                    'legacy_tipo_id' => $tipo->tipo_id,
                    'legacy_cod_sav' => $peca->cod_sav,
                ],
                'gerado_em' => $peca->data_cad,
                'salvo_em' => now(),
            ]
        );
    }

    public function syncAll()
    {
        $synced = 0;

        Peca::with('tipo')
            ->orderBy('id_pecas')
            ->chunk(100, function ($pecas) use (&$synced) {
                DB::transaction(function () use ($pecas, &$synced) {
                    foreach ($pecas as $peca) {
                        if ($this->syncPeca($peca, $peca->tipo)) {
                            $synced++;
                        }
                    }
                });
            });

        return $synced;
    }
}
