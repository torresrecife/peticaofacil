<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Paragrafo;
use App\Services\LegacyModeloSyncService;
use App\Support\LegacyEditorContent;
use App\Tipo;
use Illuminate\Http\Request;

class ParagrafoController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService)
    {
        $data = $request->validate([
            'fund_titulo' => 'required|string|max:200',
            'fund_text' => 'nullable|string',
        ]);

        $titulo = mb_strtoupper($data['fund_titulo'], 'UTF-8');
        $texto = $data['fund_text'] ?: '<div class="titulos">' . e($titulo) . '</div><p>&nbsp;</p><p align="left"></p>';
        $texto = LegacyEditorContent::denormalize($texto);

        Paragrafo::create([
            'tipo_id' => $modelo->tipo_id,
            'fund_titulo' => $titulo,
            'fund_text' => $texto,
            'fund_order' => ((int) Paragrafo::where('tipo_id', $modelo->tipo_id)->max('fund_order')) + 1,
            'fund_data' => now()->format('Y-m-d'),
            'fund_visi' => 'Y',
            'fund_stt' => 'Y',
        ]);

        $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos.edit', $modelo)->with('status', 'Paragrafo criado.');
    }

    public function update(Request $request, Tipo $modelo, Paragrafo $paragrafo, LegacyModeloSyncService $syncService)
    {
        $data = $request->validate([
            'fund_titulo' => 'required|string|max:200',
            'fund_text' => 'nullable|string',
            'fund_order' => 'nullable|integer|min:1',
        ]);

        $data['fund_text'] = LegacyEditorContent::denormalize($data['fund_text'] ?? null);

        $paragrafo->fill($data)->save();
        $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos.edit', $modelo)->with('status', 'Paragrafo atualizado.');
    }
}
