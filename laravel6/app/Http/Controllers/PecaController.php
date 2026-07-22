<?php

namespace App\Http\Controllers;

use App\PeticaoNormalizada;
use App\Tipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PecaController extends Controller
{
    public function index(Request $request)
    {
        $tipoId = $request->query('tipo_id');
        $search = trim((string) $request->query('search', ''));

        $query = PeticaoNormalizada::with(['modelo', 'legacyPeca', 'legacyUsuario'])->orderByDesc('gerado_em')->orderByDesc('id');

        $user = Auth::user();
        if ($user && $user->nivel_usu !== 'ADM') {
            $query->where('legacy_usuario_id', $user->id_usu);
        }

        if ($tipoId) {
            $query->whereHas('modelo', function ($builder) use ($tipoId) {
                $builder->where('legacy_tipo_id', $tipoId);
            });
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('cliente_referencia', 'like', '%' . $search . '%')
                    ->orWhere('nome_arquivo', 'like', '%' . $search . '%')
                    ->orWhere('legacy_peca_id', 'like', '%' . $search . '%')
                    ->orWhere('codigo_externo', 'like', '%' . $search . '%');
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
