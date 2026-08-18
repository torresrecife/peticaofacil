<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\PeticaoModeloParagrafo;
use App\Support\LegacyEditorContent;
use Illuminate\Http\Request;

class NormalizedParagrafoController extends Controller
{
    public function store(Request $request, PeticaoModelo $modeloNormalizado)
    {
        $data = $request->validate([
            'fund_titulo' => 'required|string|max:200',
            'fund_text' => 'nullable|string',
        ]);

        $titulo = mb_strtoupper($data['fund_titulo'], 'UTF-8');
        $texto = $data['fund_text'] ?: '<div class="titulos">' . e($titulo) . '</div><p>&nbsp;</p><p align="left"></p>';

        PeticaoModeloParagrafo::create([
            'modelo_id' => $modeloNormalizado->id,
            'legacy_fund_id' => null,
            'titulo' => $titulo,
            'conteudo_html' => LegacyEditorContent::denormalize($texto),
            'ordem' => ((int) PeticaoModeloParagrafo::where('modelo_id', $modeloNormalizado->id)->max('ordem')) + 1,
            'visivel' => true,
            'ativo' => true,
        ]);

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Paragrafo criado.');
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado, PeticaoModeloParagrafo $paragrafo)
    {
        abort_unless((int) $paragrafo->modelo_id === (int) $modeloNormalizado->id, 404);

        $data = $request->validate([
            'fund_titulo' => 'required|string|max:200',
            'fund_text' => 'nullable|string',
            'fund_order' => 'nullable|integer|min:1',
        ]);

        $paragrafo->fill([
            'titulo' => mb_strtoupper($data['fund_titulo'], 'UTF-8'),
            'conteudo_html' => LegacyEditorContent::denormalize($data['fund_text'] ?? null),
            'ordem' => $data['fund_order'] ?: $paragrafo->ordem,
        ])->save();

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Paragrafo atualizado.');
    }
}
