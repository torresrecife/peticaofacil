<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LegacyModeloAdminAccessService;
use App\Services\LegacyModeloMirrorService;
use App\Services\PeticaoModeloResolverService;
use Illuminate\Http\Request;

class InputCampoController extends Controller
{
    protected function ensureCompatEnabled(): void
    {
        abort_unless((bool) config('legacy.compat_admin_model_routes', true), 410);
    }

    public function store(Request $request, $modelo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $this->ensureCompatEnabled();

        $modelo = $legacyAccess->findTipoOrFail($modelo);
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            return app(NormalizedInputCampoController::class)->store($request, $mirror, $mirrorService);
        }

        return app(LegacyInputCampoFallbackController::class)->store(
            $request,
            $modelo->tipo_id,
            app(LegacyModeloAdminAccessService::class),
            $mirrorService
        );
    }

    public function update(Request $request, $modelo, $campo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $this->ensureCompatEnabled();

        $modelo = $legacyAccess->findTipoOrFail($modelo);
        $campo = $legacyAccess->findCampoForTipoOrFail($modelo->tipo_id, $campo);
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            $normalizedCampo = $mirror->campos()->where('legacy_input_id', $campo->id_input)->first();
            abort_unless($normalizedCampo, 404);

            return app(NormalizedInputCampoController::class)->update($request, $mirror, $normalizedCampo, $mirrorService);
        }

        return app(LegacyInputCampoFallbackController::class)->update(
            $request,
            $modelo->tipo_id,
            $campo->id_input,
            app(LegacyModeloAdminAccessService::class),
            $mirrorService
        );
    }
}
