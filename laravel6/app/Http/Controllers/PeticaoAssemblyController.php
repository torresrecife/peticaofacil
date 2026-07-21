<?php

namespace App\Http\Controllers;

use App\Services\PeticaoComposerService;
use App\Tipo;
use Illuminate\Http\Request;

class PeticaoAssemblyController extends Controller
{
    public function index()
    {
        $modelos = Tipo::with(['setor', 'cliente'])
            ->where('tipo_stt', 'Y')
            ->orderBy('tipo_nome')
            ->paginate(20);

        return view('peticao.index', compact('modelos'));
    }

    public function show(Tipo $modelo)
    {
        $modelo->load(['campos.dados', 'paragrafos', 'setor', 'cliente']);

        return view('peticao.assemble', [
            'modelo' => $modelo,
            'preview' => null,
            'values' => [],
        ]);
    }

    public function compose(Request $request, Tipo $modelo, PeticaoComposerService $composer)
    {
        $modelo->load(['campos.dados', 'paragrafos', 'setor', 'cliente']);

        $values = [];
        foreach ($modelo->campos as $campo) {
            $values['campo_' . $campo->id_input] = $request->input('campo_' . $campo->id_input);
        }

        return view('peticao.assemble', [
            'modelo' => $modelo,
            'preview' => $composer->compose($modelo, $values),
            'values' => $values,
        ]);
    }
}
