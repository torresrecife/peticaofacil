<?php

namespace App\Http\Controllers;

use App\Services\PeticaoModeloResolverService;
use Illuminate\Http\Request;

class LegacyPeticaoModeloCompatController extends Controller
{
    public function compose(
        Request $request,
        $modelo,
        PeticaoModeloResolverService $modeloResolver,
        PeticaoAssemblyController $assemblyController
    ) {
        $mirror = $modeloResolver->findLoadedNormalizedByLegacyTipoId(
            (int) $modelo,
            ['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor', 'servidorLegacy']
        );

        if (!$mirror) {
            return $this->redirectLegacyModelWithoutMirror();
        }

        return $assemblyController->composeNormalized($request, $mirror, app(\App\Services\PeticaoComposerService::class), app(\App\Services\SqlServerLookupService::class));
    }

    public function createEditor(
        Request $request,
        $modelo,
        PeticaoModeloResolverService $modeloResolver,
        PeticaoEditorController $editorController
    ) {
        $mirror = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if (!$mirror) {
            return $this->redirectLegacyModelWithoutMirror();
        }

        return $editorController->createNormalized($request, $mirror);
    }

    public function storePreview(
        Request $request,
        $modelo,
        PeticaoModeloResolverService $modeloResolver,
        PeticaoSavedController $savedController
    ) {
        $mirror = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        abort_unless($mirror, 404);

        return $savedController->storeFromNormalizedPreview($request, $mirror, app(\App\Services\PeticaoNormalizedDraftService::class));
    }

    public function save(
        Request $request,
        $modelo,
        PeticaoModeloResolverService $modeloResolver,
        PeticaoEditorController $editorController
    ) {
        $mirror = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if (!$mirror) {
            return $this->redirectLegacyModelWithoutMirror();
        }

        return $editorController->saveNormalized($request, $mirror, app(\App\Services\PeticaoNormalizedStorageService::class));
    }

    public function exportPdf(
        Request $request,
        $modelo,
        PeticaoModeloResolverService $modeloResolver,
        PeticaoEditorController $editorController
    ) {
        $mirror = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if (!$mirror) {
            return $this->redirectLegacyModelWithoutMirror();
        }

        return $editorController->exportNormalizedPdf($request, $mirror, app(\App\Services\PeticaoExportService::class));
    }

    public function exportWord(
        Request $request,
        $modelo,
        PeticaoModeloResolverService $modeloResolver,
        PeticaoEditorController $editorController
    ) {
        $mirror = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if (!$mirror) {
            return $this->redirectLegacyModelWithoutMirror();
        }

        return $editorController->exportNormalizedWord($request, $mirror, app(\App\Services\PeticaoExportService::class));
    }

    protected function redirectLegacyModelWithoutMirror()
    {
        return redirect()
            ->route('peticoes.index')
            ->with('status', 'Modelo legado sem mirror normalizado. Use a sincronizacao administrativa antes da montagem.');
    }
}
