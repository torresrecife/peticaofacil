<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ListaGrupo;
use App\ListaItem;
use Illuminate\Http\Request;

class ListaItemController extends Controller
{
    public function create(ListaGrupo $lista)
    {
        $item = new ListaItem();
        $item->id_setor = auth()->user()->id_setor ?: 1;

        return view('admin.listas.item-form', compact('lista', 'item'));
    }

    public function store(Request $request, ListaGrupo $lista)
    {
        $data = $this->validateData($request);
        $data['data_cad'] = now();

        $lista->itens()->create($data);

        return redirect()->route('admin.listas.edit', $lista)->with('status', 'Item criado.');
    }

    public function edit(ListaGrupo $lista, ListaItem $item)
    {
        $this->ensureBelongsToLista($lista, $item);

        return view('admin.listas.item-form', compact('lista', 'item'));
    }

    public function update(Request $request, ListaGrupo $lista, ListaItem $item)
    {
        $this->ensureBelongsToLista($lista, $item);

        $item->fill($this->validateData($request))->save();

        return redirect()->route('admin.listas.edit', $lista)->with('status', 'Item atualizado.');
    }

    public function destroy(ListaGrupo $lista, ListaItem $item)
    {
        $this->ensureBelongsToLista($lista, $item);
        $item->delete();

        return redirect()->route('admin.listas.edit', $lista)->with('status', 'Item removido.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'nome_lista' => 'required|string|max:255',
            'return_1' => 'nullable|string|max:255',
            'return_2' => 'nullable|string|max:255',
            'return_3' => 'nullable|string|max:255',
            'return_4' => 'nullable|string|max:255',
            'return_5' => 'nullable|string|max:255',
            'return_6' => 'nullable|string|max:255',
            'id_setor' => 'nullable|integer',
        ]);
    }

    protected function ensureBelongsToLista(ListaGrupo $lista, ListaItem $item)
    {
        abort_unless((int) $item->id_grupo === (int) $lista->id_grupo, 404);
    }
}
