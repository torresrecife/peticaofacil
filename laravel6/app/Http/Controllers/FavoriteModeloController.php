<?php

namespace App\Http\Controllers;

use App\PeticaoModelo;
use App\Tipo;
use App\UserFavoriteModelo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FavoriteModeloController extends Controller
{
    public function storeNormalized(PeticaoModelo $modeloNormalizado): RedirectResponse
    {
        UserFavoriteModelo::firstOrCreate([
            'legacy_usuario_id' => Auth::user()->id_usu,
            'source' => 'normalized',
            'modelo_id' => $modeloNormalizado->id,
            'legacy_tipo_id' => 0,
        ]);

        return back()->with('status', 'Modelo adicionado aos favoritos.');
    }

    public function destroyNormalized(PeticaoModelo $modeloNormalizado): RedirectResponse
    {
        UserFavoriteModelo::where([
            'legacy_usuario_id' => Auth::user()->id_usu,
            'source' => 'normalized',
            'modelo_id' => $modeloNormalizado->id,
            'legacy_tipo_id' => 0,
        ])->delete();

        return back()->with('status', 'Modelo removido dos favoritos.');
    }

    public function storeLegacy(Tipo $modelo): RedirectResponse
    {
        UserFavoriteModelo::firstOrCreate([
            'legacy_usuario_id' => Auth::user()->id_usu,
            'source' => 'legacy',
            'modelo_id' => 0,
            'legacy_tipo_id' => $modelo->tipo_id,
        ]);

        return back()->with('status', 'Modelo adicionado aos favoritos.');
    }

    public function destroyLegacy(Tipo $modelo): RedirectResponse
    {
        UserFavoriteModelo::where([
            'legacy_usuario_id' => Auth::user()->id_usu,
            'source' => 'legacy',
            'modelo_id' => 0,
            'legacy_tipo_id' => $modelo->tipo_id,
        ])->delete();

        return back()->with('status', 'Modelo removido dos favoritos.');
    }
}
