<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\Services\LegacyModeloAdminAccessService;
use App\Services\LegacyModeloMirrorService;
use Illuminate\Http\Request;

class LegacyTipoFallbackController extends Controller
{
    public function edit($modelo, LegacyModeloAdminAccessService $legacyAccess)
    {
        $legacyAccess->findTipoOrFail($modelo);
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar.');
    }

    public function update(Request $request, $modelo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $legacyAccess->findTipoOrFail($modelo);
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar.');
    }
}
