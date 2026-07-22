<?php

namespace App\Http\Controllers;

use App\PeticaoNormalizada;
use App\PeticaoModelo;
use App\Services\PeticaoExportService;
use App\Services\PeticaoNormalizedDraftService;
use App\Services\PeticaoNormalizedStorageService;
use Illuminate\Http\Request;

class PeticaoSavedController extends Controller
{
    public function storeFromPreview(Request $request, \App\Tipo $modelo, PeticaoNormalizedDraftService $draftService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'content' => 'required|string',
            'resolved_fields' => 'nullable|string',
        ]);

        $mirrorModelo = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->firstOrFail();

        $peticao = $draftService->createFromPreview($mirrorModelo, [
            'nome_cli' => $data['nome_cli'],
            'content' => $data['content'],
            'resolved_fields' => $data['resolved_fields'] ? json_decode($data['resolved_fields'], true) : null,
        ]);

        return redirect()->route('peticoes.saved.edit', $peticao);
    }

    public function edit(PeticaoNormalizada $peticao)
    {
        $peticao->load(['modelo', 'legacyPeca.tipo', 'legacyUsuario', 'versoes']);

        return view('peticao.saved-editor', [
            'peticao' => $peticao,
        ]);
    }

    public function update(Request $request, PeticaoNormalizada $peticao, PeticaoNormalizedStorageService $storage)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $peticao = $storage->save($peticao, $data);

        return redirect()->route('peticoes.saved.edit', $peticao)->with('status', 'Peticao salva.');
    }

    public function exportWord(Request $request, PeticaoNormalizada $peticao, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportWord($data['nome_cli'], $data['cod_pecas']);
    }

    public function exportPdf(Request $request, PeticaoNormalizada $peticao, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportPdf($request, $data['nome_cli'], $data['cod_pecas']);
    }
}
