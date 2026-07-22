<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NormalizedSqlServerConfigSyncService;
use App\SqlServerConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegacySqlServerConfigFallbackController extends Controller
{
    public function edit(SqlServerConfig $servidore)
    {
        return view('admin.sqlserver.form', [
            'config' => $servidore,
            'mirror' => null,
        ]);
    }

    public function update(Request $request, SqlServerConfig $servidore, NormalizedSqlServerConfigSyncService $syncService)
    {
        $servidore->fill($this->validateData($request))->save();
        $syncService->syncLegacy($servidore);

        return redirect()->route('admin.servidores.edit', $servidore)->with('status', 'Servidor SQL atualizado.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'nome_db' => 'required|string|max:50',
            'ip_db' => 'required|string|max:50',
            'data_db' => 'required|string|max:50',
            'usu_db' => 'required|string|max:50',
            'senha_db' => 'nullable|string|max:50',
            'table_db' => 'nullable|string|max:50',
            'chave_db' => 'nullable|string|max:50',
            'query_db' => 'nullable|string',
            'where_db' => 'nullable|string|max:15000',
            'stt' => ['required', Rule::in(['Y', 'N'])],
        ]);
    }
}
