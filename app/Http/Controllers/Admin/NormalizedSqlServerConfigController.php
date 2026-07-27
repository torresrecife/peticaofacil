<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LegacySqlServerProfileMirrorService;
use App\SqlServerProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NormalizedSqlServerConfigController extends Controller
{
    public function index()
    {
        $configs = SqlServerProfile::orderBy('id')->paginate(20);

        return view('admin.sqlserver.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.sqlserver.form', [
            'config' => new SqlServerProfile([
                'status' => 'ativo',
                'where_clause' => 'where 1=1',
            ]),
            'mirror' => null,
        ]);
    }

    public function store(Request $request, LegacySqlServerProfileMirrorService $mirrorService)
    {
        $profile = SqlServerProfile::create($this->validateData($request));
        $mirrorService->syncIfEnabled($profile);

        return redirect()->route('admin.servidores-normalizados.edit', $profile)->with('status', 'Servidor SQL criado.');
    }

    public function edit(SqlServerProfile $servidorNormalizado)
    {
        return view('admin.sqlserver.form', [
            'config' => $servidorNormalizado,
            'mirror' => $servidorNormalizado,
        ]);
    }

    public function update(Request $request, SqlServerProfile $servidorNormalizado, LegacySqlServerProfileMirrorService $mirrorService)
    {
        $servidorNormalizado->fill($this->validateData($request))->save();
        $mirrorService->syncIfEnabled($servidorNormalizado);

        return redirect()->route('admin.servidores-normalizados.edit', $servidorNormalizado)->with('status', 'Servidor SQL atualizado.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'nome_db' => 'required|string|max:255',
            'ip_db' => 'required|string|max:255',
            'data_db' => 'required|string|max:255',
            'usu_db' => 'required|string|max:255',
            'senha_db' => 'nullable|string|max:255',
            'table_db' => 'nullable|string|max:255',
            'chave_db' => 'nullable|string|max:255',
            'query_db' => 'nullable|string',
            'where_db' => 'nullable|string|max:15000',
            'stt' => ['required', Rule::in(['Y', 'N'])],
        ]) + [
            'nome' => $request->input('nome_db'),
            'host' => $request->input('ip_db'),
            'database_name' => $request->input('data_db'),
            'username' => $request->input('usu_db'),
            'password' => $request->input('senha_db'),
            'table_name' => $request->input('table_db'),
            'lookup_key' => $request->input('chave_db'),
            'base_query' => $request->input('query_db'),
            'where_clause' => $request->input('where_db'),
            'status' => $request->input('stt') === 'Y' ? 'ativo' : 'inativo',
        ];
    }
}
