<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NormalizedInputCampoController;
use App\InputCampo;
use App\Services\LegacyModeloSyncService;
use App\Services\LegacyModeloMirrorService;
use App\Tipo;
use Illuminate\Http\Request;

class LegacyInputCampoFallbackController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return app(NormalizedInputCampoController::class)->store($request, $mirror, $mirrorService);
    }

    public function update(Request $request, Tipo $modelo, InputCampo $campo, LegacyModeloSyncService $syncService, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));
        $normalizedCampo = $mirror->campos()->where('legacy_input_id', $campo->id_input)->first();
        abort_unless($normalizedCampo, 404);

        return app(NormalizedInputCampoController::class)->update($request, $mirror, $normalizedCampo, $mirrorService);
    }
}
