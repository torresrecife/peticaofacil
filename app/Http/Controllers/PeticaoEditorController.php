<?php

namespace App\Http\Controllers;

use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Services\PeticaoExportService;
use App\Services\PeticaoModeloResolverService;
use App\Services\PeticaoNormalizedStorageService;
use Illuminate\Http\Request;

class PeticaoEditorController extends Controller
{
    public function create(Request $request, $modelo, PeticaoModeloResolverService $modeloResolver)
    {
        $modeloNormalizado = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if ($modeloNormalizado) {
            return $this->createNormalized($request, $modeloNormalizado);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function createNormalized(Request $request, PeticaoModelo $modeloNormalizado)
    {
        return $this->renderCreateEditor($request, $modeloNormalizado);
    }

    protected function renderCreateEditor(Request $request, $modelo)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'nome_cli' => 'required|string|max:500',
        ]);

        return view('peticao.editor', [
            'modelo' => $modelo,
            'peca' => null,
            'content' => $data['content'],
            'nomeCli' => $data['nome_cli'],
        ]);
    }

    public function edit($peca)
    {
        $mirror = PeticaoNormalizada::where('legacy_peca_id', (int) $peca)->first();

        if ($mirror) {
            return redirect()->route('peticoes.saved.edit', $mirror);
        }

        return redirect()
            ->route('pecas.index')
            ->with('status', 'Peca legada sem espelho normalizado. Use a sincronizacao historica antes de editar.');
    }

    public function save(Request $request, $modelo, PeticaoNormalizedStorageService $normalizedStorage, PeticaoModeloResolverService $modeloResolver)
    {
        $modeloNormalizado = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if ($modeloNormalizado) {
            return $this->saveNormalized($request, $modeloNormalizado, $normalizedStorage);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function saveNormalized(Request $request, PeticaoModelo $modeloNormalizado, PeticaoNormalizedStorageService $storage)
    {
        return $this->handleSaveNormalized($request, $modeloNormalizado, $storage);
    }

    protected function handleSaveNormalized(Request $request, PeticaoModelo $modelo, PeticaoNormalizedStorageService $storage)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $peticao = new PeticaoNormalizada([
            'modelo_id' => $modelo->id,
            'user_id' => auth()->id(),
            'codigo_externo' => null,
            'nome_arquivo' => $modelo->nome,
            'cliente_referencia' => $data['nome_cli'],
            'conteudo_html' => $data['cod_pecas'],
            'campos_resolvidos' => [
                'legacy_tipo_id' => $modelo->legacy_tipo_id,
            ],
            'gerado_em' => now(),
            'salvo_em' => now(),
        ]);

        $peticao = $storage->save($peticao, $data);

        return redirect()->route('peticoes.saved.edit', $peticao)->with('status', 'Peca salva.');
    }

    public function exportWord(Request $request, $modelo, PeticaoModeloResolverService $modeloResolver, PeticaoExportService $exportService)
    {
        $modeloNormalizado = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if ($modeloNormalizado) {
            return $this->exportNormalizedWord($request, $modeloNormalizado, $exportService);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function exportNormalizedWord(Request $request, PeticaoModelo $modeloNormalizado, PeticaoExportService $exportService)
    {
        return $this->handleExportWord($request, $exportService);
    }

    protected function handleExportWord(Request $request, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportWord($data['nome_cli'], $data['cod_pecas']);
    }

    public function exportPdf(Request $request, $modelo, PeticaoModeloResolverService $modeloResolver, PeticaoExportService $exportService)
    {
        $modeloNormalizado = $modeloResolver->findNormalizedByLegacyTipoId((int) $modelo);
        if ($modeloNormalizado) {
            return $this->exportNormalizedPdf($request, $modeloNormalizado, $exportService);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function exportNormalizedPdf(Request $request, PeticaoModelo $modeloNormalizado, PeticaoExportService $exportService)
    {
        return $this->handleExportPdf($request, $exportService);
    }

    protected function handleExportPdf(Request $request, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportPdf($request, $data['nome_cli'], $data['cod_pecas']);
    }

    protected function redirectLegacyModelWithoutMirror()
    {
        return redirect()
            ->route('peticoes.index')
            ->with('status', 'Modelo legado sem mirror normalizado. Use a sincronizacao administrativa antes da montagem.');
    }
}
