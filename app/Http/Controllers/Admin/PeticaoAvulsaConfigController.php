<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PeticaoAvulsaTemplateService;
use Illuminate\Http\Request;

class PeticaoAvulsaConfigController extends Controller
{
    public function edit(PeticaoAvulsaTemplateService $templateService)
    {
        $modelo = $templateService->resolveSystemModel();
        return view('admin.peticao-avulsa.form', [
            'modelo' => $modelo,
        ]);
    }

    public function update(Request $request, PeticaoAvulsaTemplateService $templateService)
    {
        $data = $request->validate([
            'cod_cabec' => 'nullable|string',
            'cod_rodap' => 'nullable|string',
        ]);

        $modelo = $templateService->resolveSystemModel();
        $modelo->cabecalho_html = $data['cod_cabec'] ?? null;
        $modelo->rodape_html = $data['cod_rodap'] ?? null;
        $metadata = $modelo->metadata ?: [];
        $metadata['system'] = 'avulsa';
        $modelo->metadata = $metadata;
        $modelo->save();

        return redirect()
            ->route('admin.peticoes-avulsas.config.edit')
            ->with('status', 'Cabecalho e rodape da peticao avulsa atualizados.');
    }
}
