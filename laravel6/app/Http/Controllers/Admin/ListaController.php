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
        $lista->id_grupo = ((int) ListaGrupo::max('id_grupo')) + 1;
        $lista->setRelation('itens', collect());

        return view('admin.listas.form', compact('lista'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, true);

        DB::transaction(function () use ($data) {
            $lista = new ListaGrupo();
            $lista->id_grupo = $data['id_grupo'];
            $lista->nome_grupo = $data['nome_grupo'];
            $lista->data_cad = now();
            $lista->save();

            $this->syncItems($lista, $data['items'] ?? []);
        });

        return redirect()->route('admin.listas.index')->with('status', 'Lista criada.');
    }

    public function edit(ListaGrupo $lista)
    {
        $lista->load(['itens' => function ($query) {
            $query->orderBy('id_lista');
        }]);

        return view('admin.listas.form', compact('lista'));
    }

    public function update(Request $request, ListaGrupo $lista)
    {
        $data = $this->validateData($request, false, $lista);

        DB::transaction(function () use ($lista, $data) {
            $lista->nome_grupo = $data['nome_grupo'];
            $lista->data_cad = now();
            $lista->save();

            $this->syncItems($lista, $data['items'] ?? []);
        });

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

    protected function validateData(Request $request, $isCreate = false, ListaGrupo $lista = null)
    {
        $rules = [
            'nome_grupo' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.id_lista' => 'nullable|integer',
            'items.*.nome_lista' => 'nullable|string|max:255',
            'items.*.return_1' => 'nullable|string|max:255',
            'items.*.return_2' => 'nullable|string|max:255',
            'items.*.return_3' => 'nullable|string|max:255',
            'items.*.return_4' => 'nullable|string|max:255',
            'items.*.return_5' => 'nullable|string|max:255',
            'items.*.return_6' => 'nullable|string|max:255',
            'items.*.id_setor' => 'nullable|integer',
        ];

        if ($isCreate) {
            $rules['id_grupo'] = 'required|integer|min:1|unique:tp_grupo_tb,id_grupo';
        }

        $data = $request->validate($rules);
        $data['items'] = array_values(array_filter($data['items'] ?? [], function ($item) {
            foreach (['nome_lista', 'return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'] as $field) {
                if (trim((string) ($item[$field] ?? '')) !== '') {
                    return true;
                }
            }

            return false;
        }));

        return $data;
    }

    protected function syncItems(ListaGrupo $lista, array $items)
    {
        $keepIds = [];

        foreach ($items as $itemData) {
            $payload = [
                'nome_lista' => $itemData['nome_lista'] ?? '',
                'return_1' => $itemData['return_1'] ?? '',
                'return_2' => $itemData['return_2'] ?? '',
                'return_3' => $itemData['return_3'] ?? '',
                'return_4' => $itemData['return_4'] ?? '',
                'return_5' => $itemData['return_5'] ?? '',
                'return_6' => $itemData['return_6'] ?? '',
                'id_setor' => $itemData['id_setor'] ?? (auth()->user()->id_setor ?: 1),
            ];

            if (!empty($itemData['id_lista'])) {
                $item = $lista->itens()->where('id_lista', $itemData['id_lista'])->first();
                if ($item) {
                    $item->fill($payload)->save();
                    $keepIds[] = (int) $item->id_lista;
                }
                continue;
            }

            $payload['data_cad'] = now();
            $item = $lista->itens()->create($payload);
            $keepIds[] = (int) $item->id_lista;
        }

        $query = $lista->itens();
        if (!empty($keepIds)) {
            $query->whereNotIn('id_lista', $keepIds);
        }
        $query->delete();
    }
}
