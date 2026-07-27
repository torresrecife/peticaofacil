<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Paragrafo;
use App\Services\LegacyModeloMirrorService;
use App\Services\PeticaoModeloResolverService;
use App\Tipo;
use Illuminate\Http\Request;

class ParagrafoController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findMirrorForTipo($modelo);
        if ($mirror) {
            return app(NormalizedParagrafoController::class)->store($request, $mirror, $mirrorService);
        }

        return app(LegacyParagrafoFallbackController::class)->store(
            $request,
            $modelo,
            app(\App\Services\LegacyModeloSyncService::class),
            $mirrorService
        );
    }

    public function update(Request $request, Tipo $modelo, Paragrafo $paragrafo, LegacyModeloMirrorService $mirrorService)
    {
        $mirror = app(PeticaoModeloResolverService::class)->findMirrorForTipo($modelo);
        if ($mirror) {
            $normalizedParagrafo = $mirror->paragrafos()->where('legacy_fund_id', $paragrafo->fund_id)->first();
            abort_unless($normalizedParagrafo, 404);

            return app(NormalizedParagrafoController::class)->update($request, $mirror, $normalizedParagrafo, $mirrorService);
        }

        return app(LegacyParagrafoFallbackController::class)->update(
            $request,
            $modelo,
            $paragrafo,
            app(\App\Services\LegacyModeloSyncService::class),
            $mirrorService
        );
    }
}
