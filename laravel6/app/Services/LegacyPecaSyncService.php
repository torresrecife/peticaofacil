<?php

namespace App\Services;

use App\Peca;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Tipo;
use Illuminate\Support\Facades\DB;

class LegacyPecaSyncService
{
    public function countForYear($year = null)
    {
        $query = Peca::query();

        if ($year !== null) {
            $query->whereYear('data_cad', $year);
        }

        return $query->count();
    }

    public function countSyncableForYear($year = null)
    {
        $query = Peca::query()
            ->join('peticao_modelos', 'peticao_modelos.legacy_tipo_id', '=', 'tp_pecas_tb.tipo_id');

        if ($year !== null) {
            $query->whereYear('tp_pecas_tb.data_cad', $year);
        }

        return $query->count();
    }

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

    public function syncAll($year = null, $chunkSize = 100, $limit = null, $fromId = null, $progressCallback = null)
    {
        $synced = 0;
        $processed = 0;

        $query = Peca::with('modeloNormalizado');

        if ($year !== null) {
            $query->whereYear('data_cad', $year);
        }

        if ($fromId !== null) {
            $query->where('id_pecas', '>=', $fromId);
        }

        $query
            ->orderBy('id_pecas')
            ->chunkById($chunkSize, function ($pecas) use (&$synced, &$processed, $limit, $progressCallback) {
                foreach ($pecas as $peca) {
                    if ($limit !== null && $processed >= $limit) {
                        return false;
                    }

                    if ($this->syncPeca($peca, $peca->modeloNormalizado)) {
                        $synced++;
                    }

                    $processed++;
                }

                if ($progressCallback) {
                    call_user_func($progressCallback, $processed, $synced, $pecas->last()->id_pecas);
                }
            }, 'id_pecas', 'id_pecas');

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
