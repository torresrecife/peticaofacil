<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LegacyModeloAdminAccessService;
use App\Services\LegacyModeloMirrorService;
use App\Services\PeticaoModeloResolverService;
use Illuminate\Http\Request;

class ParagrafoController extends Controller
{
    public function store(Request $request, $modelo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $modelo = $legacyAccess->findTipoOrFail($modelo);
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            return app(NormalizedParagrafoController::class)->store($request, $mirror, $mirrorService);
        }

        return app(LegacyParagrafoFallbackController::class)->store(
            $request,
            $modelo->tipo_id,
            app(LegacyModeloAdminAccessService::class),
            $mirrorService
        );
    }

    public function update(Request $request, $modelo, $paragrafo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $modelo = $legacyAccess->findTipoOrFail($modelo);
        $paragrafo = $legacyAccess->findParagrafoForTipoOrFail($modelo->tipo_id, $paragrafo);
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            $normalizedParagrafo = $mirror->paragrafos()->where('legacy_fund_id', $paragrafo->fund_id)->first();
            abort_unless($normalizedParagrafo, 404);

            return app(NormalizedParagrafoController::class)->update($request, $mirror, $normalizedParagrafo, $mirrorService);
        }

        return app(LegacyParagrafoFallbackController::class)->update(
            $request,
            $modelo->tipo_id,
            $paragrafo->fund_id,
            app(LegacyModeloAdminAccessService::class),
            $mirrorService
        );
    }
}
