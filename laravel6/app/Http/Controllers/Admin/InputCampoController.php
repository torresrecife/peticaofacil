<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\InputCampo;
use App\Services\LegacyModeloMirrorService;
use App\Services\PeticaoModeloResolverService;
use App\Tipo;
use Illuminate\Http\Request;

class InputCampoController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            return app(NormalizedInputCampoController::class)->store($request, $mirror, $mirrorService);
        }

        return app(LegacyInputCampoFallbackController::class)->store(
            $request,
            $modelo,
            $mirrorService
        );
    }

    public function update(Request $request, Tipo $modelo, InputCampo $campo, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($mirror) {
            $normalizedCampo = $mirror->campos()->where('legacy_input_id', $campo->id_input)->first();
            abort_unless($normalizedCampo, 404);

            return app(NormalizedInputCampoController::class)->update($request, $mirror, $normalizedCampo, $mirrorService);
        }

        return app(LegacyInputCampoFallbackController::class)->update(
            $request,
            $modelo,
            $campo,
            $mirrorService
        );
    }
}
