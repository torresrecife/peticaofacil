<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\LegacyTipoFallbackController;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\Services\PeticaoModeloResolverService;
use App\Services\NormalizedModeloLegacySyncService;
use App\Tipo;
use Illuminate\Http\Request;

class TipoController extends Controller
{
    public function edit(Tipo $modelo)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findMirrorForTipo($modelo);
        if ($mirror) {
            return redirect()->route('admin.modelos-normalizados.edit', $mirror);
        }

        return app(LegacyTipoFallbackController::class)->edit($modelo);
    }

    public function update(Request $request, Tipo $modelo, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findMirrorForTipo($modelo);
        if ($mirror) {
            return app(NormalizedTipoController::class)->update($request, $mirror, $normalizedSyncService);
        }

        return app(LegacyTipoFallbackController::class)->update(
            $request,
            $modelo,
            app(\App\Services\LegacyModeloSyncService::class),
            $normalizedSyncService
        );
    }
}
