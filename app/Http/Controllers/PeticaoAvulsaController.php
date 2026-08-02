<?php

namespace App\Http\Controllers;

use App\PeticaoNormalizada;
use App\Services\PeticaoAvulsaTemplateService;
use App\Services\PeticaoNormalizedStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeticaoAvulsaController extends Controller
{
    public function create()
    {
        return view('peticao.avulsa-create');
    }

    public function store(Request $request, PeticaoNormalizedStorageService $storage, PeticaoAvulsaTemplateService $templateService)
    {
        $data = $request->validate([
            'tipo_peticao' => 'required|string|max:255',
            'parte_contraria' => 'required|string|max:500',
            'codigo_processo' => 'nullable|string|max:255',
        ]);

        $modeloAvulso = $templateService->resolveSystemModel();

        $peticao = PeticaoNormalizada::create([
            'modelo_id' => $modeloAvulso->id,
            'user_id' => Auth::id(),
            'codigo_externo' => $data['codigo_processo'] ?: null,
            'nome_arquivo' => $data['tipo_peticao'],
            'cliente_referencia' => $data['parte_contraria'],
            'conteudo_html' => $templateService->composeInitialHtml($data),
            'campos_resolvidos' => [
                'origem' => 'avulsa',
                'tipo_peticao' => $data['tipo_peticao'],
                'parte_contraria' => $data['parte_contraria'],
                'codigo_processo' => $data['codigo_processo'] ?: null,
            ],
            'gerado_em' => now(),
            'salvo_em' => now(),
        ]);

        $storage->createVersionSnapshot($peticao, 'draft');

        return redirect()
            ->route('peticoes.saved.edit', $peticao)
            ->with('status', 'Peticao avulsa criada. Complete o texto no editor.');
    }
}
