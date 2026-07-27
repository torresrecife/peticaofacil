<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\LegacyTipoFallbackController;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\Services\LegacyModeloMirrorService;
use App\Services\LegacyModeloSyncService;
use App\Services\PeticaoModeloResolverService;
use App\Tipo;
use Illuminate\Http\Request;

class TipoController extends Controller
{
    public function edit(Tipo $modelo)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            return redirect()->route('admin.modelos-normalizados.edit', $mirror);
        }

        return app(LegacyTipoFallbackController::class)->edit($modelo);
    }

    public function syncLegacy(Tipo $modelo, LegacyModeloSyncService $syncService)
    {
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()
            ->route('admin.modelos-normalizados.edit', $mirror)
            ->with('status', 'Modelo legado sincronizado para a trilha normalizada.');
    }

    public function update(Request $request, Tipo $modelo, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            return app(NormalizedTipoController::class)->update($request, $mirror, $mirrorService);
        }

        return app(LegacyTipoFallbackController::class)->update(
            $request,
            $modelo,
            $mirrorService
        );
    }
}
