<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\InputCampo;
use App\Services\LegacyModeloMirrorService;
use App\Tipo;
use Illuminate\Http\Request;

class LegacyInputCampoFallbackController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloMirrorService $mirrorService)
    {
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar campos.');
    }

    public function update(Request $request, Tipo $modelo, InputCampo $campo, LegacyModeloMirrorService $mirrorService)
    {
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar campos.');
    }
}
