<?php

namespace App\Http\Controllers;

use App\Peca;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Services\LegacyPecaSyncService;
use App\Services\PecaStorageService;
use App\Services\PeticaoExportService;
use App\Tipo;
use Illuminate\Http\Request;

class PeticaoEditorController extends Controller
{
    public function create(Request $request, Tipo $modelo)
    {
        return $this->renderCreateEditor($request, $modelo);
    }

    public function createNormalized(Request $request, PeticaoModelo $modeloNormalizado)
    {
        abort_unless($modeloNormalizado->legacy_tipo_id, 404);

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

    public function edit(Peca $peca, LegacyPecaSyncService $legacyPecaSync)
    {
        $mirror = PeticaoNormalizada::where('legacy_peca_id', $peca->id_pecas)->first();
        if (!$mirror) {
            $peca->load('modeloNormalizado');
            $mirror = $legacyPecaSync->syncPeca($peca, $peca->modeloNormalizado);
        }

        if ($mirror) {
            return redirect()->route('peticoes.saved.edit', $mirror);
        }

        $peca->loadMissing('tipo');

        return view('peticao.editor', [
            'modelo' => $peca->tipo,
            'peca' => $peca,
            'content' => $peca->cod_pecas,
            'nomeCli' => $peca->nome_cli,
        ]);
    }

    public function save(Request $request, Tipo $modelo, PecaStorageService $storage)
    {
        return $this->handleSave($request, $modelo, $storage);
    }

    public function saveNormalized(Request $request, PeticaoModelo $modeloNormalizado, PecaStorageService $storage)
    {
        abort_unless($modeloNormalizado->legacy_tipo_id, 404);

        return $this->handleSave($request, $modeloNormalizado, $storage);
    }

    protected function handleSave(Request $request, $modelo, PecaStorageService $storage)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
            'peca_id' => 'nullable|integer',
        ]);

        $peca = null;
        if (!empty($data['peca_id'])) {
            $peca = Peca::findOrFail($data['peca_id']);
        }

        $peca = $storage->save($modelo, $data, $peca);

        return redirect()->route('peticoes.editor.edit', $peca)->with('status', 'Peca salva.');
    }

    public function exportWord(Request $request, Tipo $modelo, PeticaoExportService $exportService)
    {
        return $this->handleExportWord($request, $exportService);
    }

    public function exportNormalizedWord(Request $request, PeticaoModelo $modeloNormalizado, PeticaoExportService $exportService)
    {
        abort_unless($modeloNormalizado->legacy_tipo_id, 404);

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

    public function exportPdf(Request $request, Tipo $modelo, PeticaoExportService $exportService)
    {
        return $this->handleExportPdf($request, $exportService);
    }

    public function exportNormalizedPdf(Request $request, PeticaoModelo $modeloNormalizado, PeticaoExportService $exportService)
    {
        abort_unless($modeloNormalizado->legacy_tipo_id, 404);

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
