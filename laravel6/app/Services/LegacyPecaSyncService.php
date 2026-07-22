<?php

namespace App\Services;

use App\Peca;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Tipo;
use Illuminate\Support\Facades\DB;

class LegacyPecaSyncService
{
    public function syncPeca(Peca $peca, $source = null)
    {
        $modelo = $this->resolveModelo($peca, $source);

        if (!$modelo) {
            return null;
        }

        $legacyTipoId = $modelo->legacy_tipo_id ?: ($source instanceof Tipo ? $source->tipo_id : $peca->tipo_id);

        return PeticaoNormalizada::updateOrCreate(
            ['legacy_peca_id' => $peca->id_pecas],
            [
                'modelo_id' => $modelo->id,
                'legacy_usuario_id' => $peca->id_usu ?: null,
                'codigo_externo' => $peca->cod_sav ?: null,
                'nome_arquivo' => $peca->nome_pecas ?: $modelo->nome,
                'cliente_referencia' => $peca->nome_cli,
                'conteudo_html' => $peca->cod_pecas,
                'campos_resolvidos' => [
                    'legacy_tipo_id' => $legacyTipoId,
                    'legacy_cod_sav' => $peca->cod_sav,
                ],
                'gerado_em' => $peca->data_cad,
                'salvo_em' => now(),
            ]
        );
    }

    public function syncAll($year = null)
    {
        $synced = 0;

        $query = Peca::with('modeloNormalizado');

        if ($year !== null) {
            $query->whereYear('data_cad', $year);
        }

        $query
            ->orderBy('id_pecas')
            ->chunk(100, function ($pecas) use (&$synced) {
                DB::transaction(function () use ($pecas, &$synced) {
                    foreach ($pecas as $peca) {
                        if ($this->syncPeca($peca, $peca->modeloNormalizado)) {
                            $synced++;
                        }
                    }
                });
            });

        return $synced;
    }

    protected function resolveModelo(Peca $peca, $source = null)
    {
        if ($source instanceof PeticaoModelo) {
            return $source;
        }

        if ($source instanceof Tipo) {
            return PeticaoModelo::where('legacy_tipo_id', $source->tipo_id)->first();
        }

        if ($peca->relationLoaded('modeloNormalizado') && $peca->modeloNormalizado) {
            return $peca->modeloNormalizado;
        }

        return $peca->modeloNormalizado()->first();
    }
}
