<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\Services\LegacyModeloMirrorService;
use App\Tipo;
use Illuminate\Http\Request;

class LegacyTipoFallbackController extends Controller
{
    public function edit(Tipo $modelo)
    {
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar.');
    }

    public function update(Request $request, Tipo $modelo, LegacyModeloMirrorService $mirrorService)
    {
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar.');
    }
}
