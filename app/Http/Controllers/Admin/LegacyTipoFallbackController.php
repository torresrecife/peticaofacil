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
    protected function ensureCompatEnabled(): void
    {
        abort_unless((bool) config('legacy.compat_admin_model_routes', true), 410);
    }

    public function edit($modelo, LegacyModeloAdminAccessService $legacyAccess)
    {
        $this->ensureCompatEnabled();
        $legacyAccess->findTipoOrFail($modelo);
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar.');
    }

    public function update(Request $request, $modelo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $this->ensureCompatEnabled();
        $legacyAccess->findTipoOrFail($modelo);
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar.');
    }
}
