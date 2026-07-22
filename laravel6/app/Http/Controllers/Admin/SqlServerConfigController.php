<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\SqlServerProfile;
use App\SqlServerConfig;

class SqlServerConfigController extends Controller
{
    public function index()
    {
        $configs = SqlServerProfile::orderBy('id')->paginate(20);
        $legacyIds = $configs->pluck('legacy_config_id')->filter()->values()->all();

        $legacyFallback = SqlServerConfig::orderBy('id_db');
        if (!empty($legacyIds)) {
            $legacyFallback->whereNotIn('id_db', $legacyIds);
        }

        return view('admin.sqlserver.index', [
            'configs' => $configs,
            'legacyFallback' => $legacyFallback->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.servidores-normalizados.create');
    }

    public function store(\Illuminate\Http\Request $request, NormalizedSqlServerConfigController $controller)
    {
        return $controller->store($request, app(\App\Services\NormalizedSqlServerConfigSyncService::class));
    }

    public function edit(SqlServerConfig $servidore)
    {
        $mirror = SqlServerProfile::where('legacy_config_id', $servidore->id_db)->first();

        if ($mirror) {
            return redirect()->route('admin.servidores-normalizados.edit', $mirror);
        }

        return app(LegacySqlServerConfigFallbackController::class)->edit($servidore);
    }

    public function update(\Illuminate\Http\Request $request, SqlServerConfig $servidore)
    {
        $mirror = SqlServerProfile::where('legacy_config_id', $servidore->id_db)->first();

        if ($mirror) {
            return app(NormalizedSqlServerConfigController::class)->update(
                $request,
                $mirror,
                app(\App\Services\NormalizedSqlServerConfigSyncService::class)
            );
        }

        return app(LegacySqlServerConfigFallbackController::class)->update(
            $request,
            $servidore,
            app(\App\Services\NormalizedSqlServerConfigSyncService::class)
        );
    }
}
