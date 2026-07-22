<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Paragrafo;
use App\PeticaoModelo;
use App\Services\NormalizedModeloLegacySyncService;
use App\Tipo;
use Illuminate\Http\Request;

class ParagrafoController extends Controller
{
    public function store(Request $request, Tipo $modelo, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->first();
        if ($mirror) {
            return app(NormalizedParagrafoController::class)->store($request, $mirror, $normalizedSyncService);
        }

        return app(LegacyParagrafoFallbackController::class)->store($request, $modelo, app(\App\Services\LegacyModeloSyncService::class));
    }

    public function update(Request $request, Tipo $modelo, Paragrafo $paragrafo, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->first();
        if ($mirror) {
            $normalizedParagrafo = $mirror->paragrafos()->where('legacy_fund_id', $paragrafo->fund_id)->first();
            abort_unless($normalizedParagrafo, 404);

            return app(NormalizedParagrafoController::class)->update($request, $mirror, $normalizedParagrafo, $normalizedSyncService);
        }

        return app(LegacyParagrafoFallbackController::class)->update($request, $modelo, $paragrafo, app(\App\Services\LegacyModeloSyncService::class));
    }
}
