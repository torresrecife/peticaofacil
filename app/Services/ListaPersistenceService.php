<?php

namespace App\Services;

use App\ListaGrupo;
use App\ListaItem;
use Illuminate\Support\Facades\DB;

class ListaPersistenceService
{
    public function createGroup(array $data): ListaGrupo
    {
        return DB::transaction(function () use ($data) {
            $nextId = ((int) (ListaGrupo::max('id_grupo') ?: 0)) + 1;

            return ListaGrupo::create([
                'id_grupo' => $nextId,
                'legacy_grupo_id' => null,
                'nome_grupo' => $data['nome_grupo'],
                'data_cad' => now(),
            ]);
        });
    }

    public function updateGroup(ListaGrupo $grupo, array $data): ListaGrupo
    {
        return DB::transaction(function () use ($grupo, $data) {
            $grupo->nome_grupo = $data['nome_grupo'];
            $grupo->save();

            return $grupo;
        });
    }

    public function deleteGroup(ListaGrupo $grupo): void
    {
        DB::transaction(function () use ($grupo) {
            ListaItem::where('id_grupo', $grupo->id_grupo)->delete();
            $grupo->delete();
        });
    }

    public function createItem(ListaGrupo $grupo, array $data): ListaItem
    {
        return DB::transaction(function () use ($grupo, $data) {
            $nextId = ((int) (ListaItem::max('id_lista') ?: 0)) + 1;

            return ListaItem::create([
                'id_lista' => $nextId,
                'legacy_lista_id' => null,
                'id_grupo' => $grupo->id_grupo,
                'nome_lista' => $data['nome_lista'],
                'return_1' => $data['return_1'] ?? null,
                'return_2' => $data['return_2'] ?? null,
                'return_3' => $data['return_3'] ?? null,
                'return_4' => $data['return_4'] ?? null,
                'return_5' => $data['return_5'] ?? null,
                'return_6' => $data['return_6'] ?? null,
                'id_setor' => $data['id_setor'] ?? null,
                'data_cad' => now(),
            ]);
        });
    }

    public function updateItem(ListaItem $item, array $data): ListaItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->fill($data)->save();

            return $item;
        });
    }

    public function deleteItem(ListaItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->delete();
        });
    }
}
