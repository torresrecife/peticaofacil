<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\SqlServerConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SqlServerConfigController extends Controller
{
    public function index()
    {
        $configs = SqlServerConfig::orderBy('id_db')->paginate(20);

        return view('admin.sqlserver.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.sqlserver.form', [
            'config' => new SqlServerConfig([
                'stt' => 'Y',
                'where_db' => 'where 1=1',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        SqlServerConfig::create($this->validateData($request));

        return redirect()->route('admin.servidores.index')->with('status', 'Servidor SQL criado.');
    }

    public function edit(SqlServerConfig $servidore)
    {
        return view('admin.sqlserver.form', ['config' => $servidore]);
    }

    public function update(Request $request, SqlServerConfig $servidore)
    {
        $servidore->fill($this->validateData($request))->save();

        return redirect()->route('admin.servidores.index')->with('status', 'Servidor SQL atualizado.');
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
