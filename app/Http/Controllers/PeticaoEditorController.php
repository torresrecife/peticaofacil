<?php

namespace App\Http\Controllers;

use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Services\PeticaoExportService;
use App\Services\PeticaoNormalizedStorageService;
use Illuminate\Http\Request;

class PeticaoEditorController extends Controller
{
    public function createNormalized(Request $request, PeticaoModelo $modeloNormalizado)
    {
        return $this->renderCreateEditor($request, $modeloNormalizado);
    }

    protected function renderCreateEditor(Request $request, $modelo)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'nome_cli' => 'required|string|max:500',
            'codigo_processo' => 'nullable|string|max:255',
        ]);

        return view('peticao.editor', [
            'modelo' => $modelo,
            'peca' => null,
            'content' => $data['content'],
            'nomeCli' => $data['nome_cli'],
            'codigoProcesso' => $data['codigo_processo'] ?? null,
        ]);
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
            'codigo_processo' => 'nullable|string|max:255',
        ]);

        $peticao = new PeticaoNormalizada([
            'modelo_id' => $modelo->id,
            'user_id' => auth()->id(),
            'codigo_externo' => $data['codigo_processo'] ?? null,
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
}
