<?php

namespace App\Services;

use App\LegacyListaGrupo;
use App\LegacyListaItem;
use App\ListaGrupo;
use App\ListaItem;
use Illuminate\Support\Facades\DB;

class ListaSyncService
{
    public function syncAll(): int
    {
        $count = 0;

        LegacyListaGrupo::with('itens')->orderBy('id_grupo')->chunk(100, function ($grupos) use (&$count) {
            foreach ($grupos as $grupo) {
                $this->syncLegacyGroup($grupo);
                $count++;
            }
        });

        return $count;
    }

    public function syncLegacyGroup(LegacyListaGrupo $legacyGrupo): ListaGrupo
    {
        return DB::transaction(function () use ($legacyGrupo) {
            $grupo = ListaGrupo::updateOrCreate(
                ['id_grupo' => $legacyGrupo->id_grupo],
                [
                    'legacy_grupo_id' => $legacyGrupo->id_grupo,
                    'nome_grupo' => $legacyGrupo->nome_grupo,
                    'data_cad' => $legacyGrupo->data_cad,
                ]
            );

            foreach ($legacyGrupo->itens as $legacyItem) {
                $this->syncLegacyItem($legacyItem);
            }

            return $grupo;
        });
    }

    public function syncLegacyItem(LegacyListaItem $legacyItem): ListaItem
    {
        return ListaItem::updateOrCreate(
            ['id_lista' => $legacyItem->id_lista],
            [
                'legacy_lista_id' => $legacyItem->id_lista,
                'id_grupo' => $legacyItem->id_grupo,
                'nome_lista' => $legacyItem->nome_lista,
                'return_1' => $legacyItem->return_1,
                'return_2' => $legacyItem->return_2,
                'return_3' => $legacyItem->return_3,
                'return_4' => $legacyItem->return_4,
                'return_5' => $legacyItem->return_5,
                'return_6' => $legacyItem->return_6,
                'data_cad' => $legacyItem->data_cad,
                'id_setor' => $legacyItem->id_setor,
            ]
        );
    }

    public function createGroup(array $data): ListaGrupo
    {
        return DB::transaction(function () use ($data) {
            $nextId = ((int) (ListaGrupo::max('id_grupo') ?: 0)) + 1;

            $grupo = ListaGrupo::create([
                'id_grupo' => $nextId,
                'legacy_grupo_id' => $nextId,
                'nome_grupo' => $data['nome_grupo'],
                'data_cad' => now(),
            ]);

            LegacyListaGrupo::create([
                'id_grupo' => $nextId,
                'nome_grupo' => $data['nome_grupo'],
                'data_cad' => now(),
            ]);

            return $grupo;
        });
    }

    public function updateGroup(ListaGrupo $grupo, array $data): ListaGrupo
    {
        return DB::transaction(function () use ($grupo, $data) {
            $grupo->nome_grupo = $data['nome_grupo'];
            $grupo->save();

            $legacyGrupo = LegacyListaGrupo::find($grupo->legacy_grupo_id ?: $grupo->id_grupo);
            if ($legacyGrupo) {
                $legacyGrupo->nome_grupo = $data['nome_grupo'];
                $legacyGrupo->save();
            }

            return $grupo;
        });
    }

    public function deleteGroup(ListaGrupo $grupo): void
    {
        DB::transaction(function () use ($grupo) {
            $legacyGrupoId = $grupo->legacy_grupo_id ?: $grupo->id_grupo;

            ListaItem::where('id_grupo', $grupo->id_grupo)->delete();
            $grupo->delete();

            LegacyListaItem::where('id_grupo', $legacyGrupoId)->delete();
            LegacyListaGrupo::where('id_grupo', $legacyGrupoId)->delete();
        });
    }

    public function createItem(ListaGrupo $grupo, array $data): ListaItem
    {
        return DB::transaction(function () use ($grupo, $data) {
            $nextId = ((int) (ListaItem::max('id_lista') ?: 0)) + 1;

            $item = ListaItem::create([
                'id_lista' => $nextId,
                'legacy_lista_id' => $nextId,
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

            LegacyListaItem::create([
                'id_lista' => $nextId,
                'id_grupo' => $grupo->legacy_grupo_id ?: $grupo->id_grupo,
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

            return $item;
        });
    }

    public function updateItem(ListaItem $item, array $data): ListaItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->fill($data)->save();

            $legacyItem = LegacyListaItem::find($item->legacy_lista_id ?: $item->id_lista);
            if ($legacyItem) {
                $legacyItem->fill($data)->save();
            }

            return $item;
        });
    }

    public function deleteItem(ListaItem $item): void
    {
        DB::transaction(function () use ($item) {
            $legacyItemId = $item->legacy_lista_id ?: $item->id_lista;
            $item->delete();
            LegacyListaItem::where('id_lista', $legacyItemId)->delete();
        });
    }
}
