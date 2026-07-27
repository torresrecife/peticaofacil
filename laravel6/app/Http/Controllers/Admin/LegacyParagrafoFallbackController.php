<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NormalizedParagrafoController;
use App\Paragrafo;
use App\Services\LegacyModeloSyncService;
use App\Services\NormalizedModeloLegacySyncService;
use App\Support\LegacyEditorContent;
use App\Tipo;
use Illuminate\Http\Request;

class LegacyParagrafoFallbackController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return app(NormalizedParagrafoController::class)->store($request, $mirror, $normalizedSyncService);
    }

    public function update(Request $request, Tipo $modelo, Paragrafo $paragrafo, LegacyModeloSyncService $syncService, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));
        $normalizedParagrafo = $mirror->paragrafos()->where('legacy_fund_id', $paragrafo->fund_id)->first();
        abort_unless($normalizedParagrafo, 404);

        return app(NormalizedParagrafoController::class)->update($request, $mirror, $normalizedParagrafo, $normalizedSyncService);
    }
}
