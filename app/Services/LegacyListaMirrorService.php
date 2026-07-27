<?php

namespace App\Services;

use App\LegacyListaGrupo;
use App\LegacyListaItem;
use App\ListaGrupo;
use App\ListaItem;

class LegacyListaMirrorService
{
    public function enabled(): bool
    {
        return (bool) config('legacy.mirror_legacy_listas', false);
    }

    public function createGroup(ListaGrupo $grupo): void
    {
        if (!$this->enabled()) {
            return;
        }

        LegacyListaGrupo::create([
            'id_grupo' => $grupo->legacy_grupo_id ?: $grupo->id_grupo,
            'nome_grupo' => $grupo->nome_grupo,
            'data_cad' => $grupo->data_cad ?: now(),
        ]);
    }

    public function updateGroup(ListaGrupo $grupo): void
    {
        if (!$this->enabled()) {
            return;
        }

        $legacyGrupo = LegacyListaGrupo::find($grupo->legacy_grupo_id ?: $grupo->id_grupo);
        if (!$legacyGrupo) {
            return;
        }

        $legacyGrupo->nome_grupo = $grupo->nome_grupo;
        $legacyGrupo->save();
    }

    public function deleteGroup(ListaGrupo $grupo): void
    {
        if (!$this->enabled()) {
            return;
        }

        $legacyGrupoId = $grupo->legacy_grupo_id ?: $grupo->id_grupo;
        LegacyListaItem::where('id_grupo', $legacyGrupoId)->delete();
        LegacyListaGrupo::where('id_grupo', $legacyGrupoId)->delete();
    }

    public function createItem(ListaGrupo $grupo, ListaItem $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        LegacyListaItem::create([
            'id_lista' => $item->legacy_lista_id ?: $item->id_lista,
            'id_grupo' => $grupo->legacy_grupo_id ?: $grupo->id_grupo,
            'nome_lista' => $item->nome_lista,
            'return_1' => $item->return_1,
            'return_2' => $item->return_2,
            'return_3' => $item->return_3,
            'return_4' => $item->return_4,
            'return_5' => $item->return_5,
            'return_6' => $item->return_6,
            'id_setor' => $item->id_setor,
            'data_cad' => $item->data_cad ?: now(),
        ]);
    }

    public function updateItem(ListaItem $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        $legacyItem = LegacyListaItem::find($item->legacy_lista_id ?: $item->id_lista);
        if (!$legacyItem) {
            return;
        }

        $legacyItem->fill([
            'nome_lista' => $item->nome_lista,
            'return_1' => $item->return_1,
            'return_2' => $item->return_2,
            'return_3' => $item->return_3,
            'return_4' => $item->return_4,
            'return_5' => $item->return_5,
            'return_6' => $item->return_6,
            'id_setor' => $item->id_setor,
        ])->save();
    }

    public function deleteItem(ListaItem $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        $legacyItemId = $item->legacy_lista_id ?: $item->id_lista;
        LegacyListaItem::where('id_lista', $legacyItemId)->delete();
    }
}
