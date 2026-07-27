<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\InputCampo;
use App\Services\PeticaoModeloResolverService;
use App\Services\NormalizedModeloLegacySyncService;
use App\Tipo;
use Illuminate\Http\Request;

class InputCampoController extends Controller
{
    public function store(Request $request, Tipo $modelo, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findMirrorForTipo($modelo);
        if ($mirror) {
            return app(NormalizedInputCampoController::class)->store($request, $mirror, $normalizedSyncService);
        }

        return app(LegacyInputCampoFallbackController::class)->store(
            $request,
            $modelo,
            app(\App\Services\LegacyModeloSyncService::class),
            $normalizedSyncService
        );
    }

    public function update(Request $request, Tipo $modelo, InputCampo $campo, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findMirrorForTipo($modelo);
        if ($mirror) {
            $normalizedCampo = $mirror->campos()->where('legacy_input_id', $campo->id_input)->first();
            abort_unless($normalizedCampo, 404);

            return app(NormalizedInputCampoController::class)->update($request, $mirror, $normalizedCampo, $normalizedSyncService);
        }

        return app(LegacyInputCampoFallbackController::class)->update(
            $request,
            $modelo,
            $campo,
            app(\App\Services\LegacyModeloSyncService::class),
            $normalizedSyncService
        );
    }
}
