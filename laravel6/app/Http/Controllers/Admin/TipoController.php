<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\LegacyTipoFallbackController;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\PeticaoModelo;
use App\Services\NormalizedModeloLegacySyncService;
use App\Tipo;
use Illuminate\Http\Request;

class TipoController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.modelos-normalizados.index');
    }

    public function store(Request $request, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        return app(NormalizedTipoController::class)->store($request, $normalizedSyncService);
    }

    public function edit(Tipo $modelo)
    {
        $mirror = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->first();
        if ($mirror) {
            return redirect()->route('admin.modelos-normalizados.edit', $mirror);
        }

        return app(LegacyTipoFallbackController::class)->edit($modelo);
    }

    public function update(Request $request, Tipo $modelo, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->first();
        if ($mirror) {
            return app(NormalizedTipoController::class)->update($request, $mirror, $normalizedSyncService);
        }

        return app(LegacyTipoFallbackController::class)->update($request, $modelo, app(\App\Services\LegacyModeloSyncService::class));
    }
}
