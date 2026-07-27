<?php

namespace App\Http\Controllers;

use App\PeticaoModelo;
use App\UserFavoriteModelo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FavoriteModeloController extends Controller
{
    public function storeNormalized(PeticaoModelo $modeloNormalizado): RedirectResponse
    {
        UserFavoriteModelo::firstOrCreate([
            'user_id' => Auth::id(),
            'source' => 'normalized',
            'modelo_id' => $modeloNormalizado->id,
            'legacy_tipo_id' => $modeloNormalizado->legacy_tipo_id ?: 0,
        ]);

        return back()->with('status', 'Modelo adicionado aos favoritos.');
    }

    public function destroyNormalized(PeticaoModelo $modeloNormalizado): RedirectResponse
    {
        UserFavoriteModelo::where([
            'user_id' => Auth::id(),
            'source' => 'normalized',
            'modelo_id' => $modeloNormalizado->id,
        ])->delete();

        return back()->with('status', 'Modelo removido dos favoritos.');
    }
}
