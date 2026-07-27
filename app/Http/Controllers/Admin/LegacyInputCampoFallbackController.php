<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LegacyModeloAdminAccessService;
use App\Services\LegacyModeloMirrorService;
use Illuminate\Http\Request;

class LegacyInputCampoFallbackController extends Controller
{
    protected function ensureCompatEnabled(): void
    {
        abort_unless((bool) config('legacy.compat_admin_model_routes', true), 410);
    }

    public function store(Request $request, $modelo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $this->ensureCompatEnabled();
        $legacyAccess->findTipoOrFail($modelo);
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar campos.');
    }

    public function update(Request $request, $modelo, $campo, LegacyModeloAdminAccessService $legacyAccess, LegacyModeloMirrorService $mirrorService)
    {
        $this->ensureCompatEnabled();
        $tipo = $legacyAccess->findTipoOrFail($modelo);
        $legacyAccess->findCampoForTipoOrFail($tipo->tipo_id, $campo);
        return redirect()
            ->route('admin.modelos-normalizados.index')
            ->with('status', 'Modelo legado sem mirror. Use "Sincronizar legado" antes de editar campos.');
    }
}
