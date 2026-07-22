<?php

namespace App\Http\Controllers;

use App\Peca;
use App\Services\PecaStorageService;
use App\Services\PeticaoExportService;
use App\Tipo;
use Illuminate\Http\Request;

class PeticaoEditorController extends Controller
{
    public function create(Request $request, Tipo $modelo)
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

    public function edit(Peca $peca)
    {
        $peca->load('tipo');

        return view('peticao.editor', [
            'modelo' => $peca->tipo,
            'peca' => $peca,
            'content' => $peca->cod_pecas,
            'nomeCli' => $peca->nome_cli,
        ]);
    }

    public function save(Request $request, Tipo $modelo, PecaStorageService $storage)
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
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportWord($data['nome_cli'], $data['cod_pecas']);
    }

    public function exportPdf(Request $request, Tipo $modelo, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportPdf($request, $data['nome_cli'], $data['cod_pecas']);
    }
}
