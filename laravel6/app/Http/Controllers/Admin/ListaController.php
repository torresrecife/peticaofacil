<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ListaGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListaController extends Controller
{
    public function index()
    {
        $listas = ListaGrupo::withCount('itens')
            ->orderBy('id_grupo')
            ->paginate(20);

        return view('admin.listas.index', compact('listas'));
    }

    public function create()
    {
        $lista = new ListaGrupo();
        $lista->setRelation('itens', collect());

        return view('admin.listas.form', compact('lista'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        ListaGrupo::create([
            'nome_grupo' => $data['nome_grupo'],
            'data_cad' => now(),
        ]);

        return redirect()->route('admin.listas.index')->with('status', 'Lista criada.');
    }

    public function edit(Request $request, ListaGrupo $lista)
    {
        $search = trim((string) $request->query('search', ''));
        $itensQuery = $lista->itens()->orderBy('id_lista');

        if ($search !== '') {
            $itensQuery->where(function ($query) use ($search) {
                $query->where('nome_lista', 'like', '%' . $search . '%')
                    ->orWhere('return_1', 'like', '%' . $search . '%')
                    ->orWhere('return_2', 'like', '%' . $search . '%')
                    ->orWhere('return_3', 'like', '%' . $search . '%')
                    ->orWhere('return_4', 'like', '%' . $search . '%')
                    ->orWhere('return_5', 'like', '%' . $search . '%')
                    ->orWhere('return_6', 'like', '%' . $search . '%');
            });
        }

        $itens = $itensQuery->get();
        $lista->setRelation('itens', $itens);

        return view('admin.listas.form', compact('lista', 'search'));
    }

    public function update(Request $request, ListaGrupo $lista)
    {
        $data = $this->validateData($request);

        $lista->nome_grupo = $data['nome_grupo'];
        $lista->save();

        return redirect()->route('admin.listas.edit', $lista)->with('status', 'Lista atualizada.');
    }

    public function destroy(ListaGrupo $lista)
    {
        DB::transaction(function () use ($lista) {
            $lista->itens()->delete();
            $lista->delete();
        });

        return redirect()->route('admin.listas.index')->with('status', 'Lista removida.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'nome_grupo' => 'required|string|max:255',
        ]);
    }
}
