<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\Setor;
use App\SqlServerProfile;
use App\SqlServerConfig;
use App\Support\LegacyEditorContent;
use App\Services\LegacyModeloSyncService;
use App\Services\NormalizedModeloLegacySyncService;
use App\Tipo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class LegacyTipoFallbackController extends Controller
{
    public function edit(Tipo $modelo)
    {
        $mirror = app(LegacyModeloSyncService::class)->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos-normalizados.edit', $mirror)
            ->with('status', 'Modelo legado sincronizado para a trilha normalizada.');
    }

    public function update(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return app(NormalizedTipoController::class)->update($request, $mirror, $normalizedSyncService);
    }

    protected function validateData(Request $request)
    {
        $data = $request->validate([
            'tipo_nome' => 'required|string|max:300',
            'nome_pre' => 'nullable|string|max:300',
            'nome_pos' => 'nullable|string|max:300',
            'id_db' => 'nullable|integer',
            'id_cliente' => 'nullable|integer',
            'id_setor' => 'required|integer',
            'tipo_stt' => ['required', Rule::in(['Y', 'N'])],
            'tipo_arq' => ['required', Rule::in(['pdf', 'word', 'pdf,word'])],
            'cod_cabec' => 'nullable|string',
            'cod_rodap' => 'nullable|string',
        ]);

        $data['cod_cabec'] = LegacyEditorContent::denormalize($data['cod_cabec'] ?? null);
        $data['cod_rodap'] = LegacyEditorContent::denormalize($data['cod_rodap'] ?? null);

        return $data;
    }

    protected function prepareForEditor(Tipo $modelo)
    {
        $modelo->cod_cabec = LegacyEditorContent::normalize($modelo->cod_cabec);
        $modelo->cod_rodap = LegacyEditorContent::normalize($modelo->cod_rodap);

        if ($modelo->relationLoaded('paragrafos')) {
            $modelo->paragrafos->transform(function ($paragrafo) {
                $paragrafo->fund_text = LegacyEditorContent::normalize($paragrafo->fund_text);

                return $paragrafo;
            });
        }

        return $modelo;
    }

    protected function availableServidores(): Collection
    {
        $profiles = SqlServerProfile::active()->orderBy('nome')->get();

        if ($profiles->isEmpty()) {
            return SqlServerConfig::active()->orderBy('nome_db')->get();
        }

        $legacyIds = $profiles->pluck('legacy_config_id')->filter()->values()->all();
        $legacyFallback = SqlServerConfig::active()
            ->when(!empty($legacyIds), function ($query) use ($legacyIds) {
                $query->whereNotIn('id_db', $legacyIds);
            })
            ->orderBy('nome_db')
            ->get();

        return $profiles->concat($legacyFallback)->values();
    }
}
