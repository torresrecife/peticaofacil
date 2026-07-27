<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Setor;
use Illuminate\Http\Request;

class SetorController extends Controller
{
    public function index()
    {
        $setores = Setor::orderBy('id_setor')->paginate(20);

        return view('admin.setores.index', compact('setores'));
    }

    public function create()
    {
        return view('admin.setores.form', ['setor' => new Setor()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome_setor' => 'required|string|max:500',
            'cod_setor' => 'nullable|string|max:50',
        ]);

        $setor = new Setor($data);
        $setor->data_cad = now();
        $setor->save();

        return redirect()->route('admin.setores.index')->with('status', 'Setor criado.');
    }

    public function edit(Setor $setor)
    {
        return view('admin.setores.form', compact('setor'));
    }

    public function update(Request $request, Setor $setor)
    {
        $data = $request->validate([
            'nome_setor' => 'required|string|max:500',
            'cod_setor' => 'nullable|string|max:50',
        ]);

        $setor->fill($data)->save();

        return redirect()->route('admin.setores.index')->with('status', 'Setor atualizado.');
    }
}
