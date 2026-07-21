<?php

namespace App\Http\Controllers;

use App\Peca;
use App\Tipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PecaController extends Controller
{
    public function index(Request $request)
    {
        $tipoId = $request->query('tipo_id');
        $search = trim((string) $request->query('search', ''));

        $query = Peca::with(['tipo', 'usuario'])->orderByDesc('data_cad');

        $user = Auth::user();
        if ($user && $user->nivel_usu !== 'ADM') {
            $query->where('id_usu', $user->id_usu);
        }

        if ($tipoId) {
            $query->where('tipo_id', $tipoId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('nome_cli', 'like', '%' . $search . '%')
                    ->orWhere('nome_pecas', 'like', '%' . $search . '%')
                    ->orWhere('id_pecas', 'like', '%' . $search . '%');
            });
        }

        $pecas = $query->paginate(20)->appends($request->query());
        $modelos = Tipo::orderBy('tipo_nome')->get();

        return view('pecas.index', [
            'pecas' => $pecas,
            'modelos' => $modelos,
            'selectedTipo' => $tipoId,
            'search' => $search,
        ]);
    }
}
