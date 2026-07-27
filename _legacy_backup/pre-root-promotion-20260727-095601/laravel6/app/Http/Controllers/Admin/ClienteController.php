<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Setor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('setor')->orderBy('cliente_id')->paginate(20);

        return view('admin.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('admin.clientes.form', [
            'cliente' => new Cliente(['cliente_status' => 'Y']),
            'setores' => Setor::orderBy('nome_setor')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $cliente = new Cliente($data);
        $cliente->cliente_creator = now();
        $cliente->save();

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente criado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('admin.clientes.form', [
            'cliente' => $cliente,
            'setores' => Setor::orderBy('nome_setor')->get(),
        ]);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $this->validateData($request);

        $cliente->fill($data)->save();

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente atualizado.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'cliente_name' => 'required|string|max:500',
            'cliente_cod' => 'nullable|string|max:500',
            'cliente_area' => 'nullable|integer',
            'cliente_status' => ['required', Rule::in(['Y', 'N'])],
        ]);
    }
}
