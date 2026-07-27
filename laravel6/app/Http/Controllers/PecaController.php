<?php

namespace App\Http\Controllers;

use App\Peca;
use App\PeticaoNormalizada;
use App\PeticaoModelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PecaController extends Controller
{
    public function index(Request $request)
    {
        $modeloId = $request->query('modelo_id');
        $legacyTipoId = $request->query('tipo_id');
        $search = trim((string) $request->query('search', ''));

        $query = PeticaoNormalizada::with(['modelo', 'legacyPeca', 'user'])
            ->orderByRaw('COALESCE(gerado_em, created_at) DESC')
            ->orderByDesc('id');

        $user = Auth::user();
        if ($user && $user->nivel_usu !== 'ADM') {
            $query->where(function ($builder) use ($user) {
                $builder->where('user_id', $user->id);

                if ($user->legacy_usuario_id) {
                    $builder->orWhere(function ($legacyBuilder) use ($user) {
                        $legacyBuilder->whereNull('user_id')
                            ->where('legacy_usuario_id', $user->legacy_usuario_id);
                    });
                }
            });
        }

        if ($modeloId) {
            $query->where('modelo_id', $modeloId);
        } elseif ($legacyTipoId) {
            $query->whereHas('modelo', function ($builder) use ($legacyTipoId) {
                $builder->where('legacy_tipo_id', $legacyTipoId);
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
        $usingLegacyFallback = false;

        if ($pecas->total() === 0) {
            $legacyQuery = Peca::with(['modeloNormalizado', 'tipo', 'usuario'])->orderByDesc('data_cad')->orderByDesc('id_pecas');

            if ($user && $user->nivel_usu !== 'ADM') {
                $legacyQuery->where('id_usu', $user->id_usu);
            }

            if ($modeloId) {
                $legacyModelo = PeticaoModelo::find($modeloId);
                $legacyTipoIdFromModelo = optional($legacyModelo)->legacy_tipo_id;
                if ($legacyTipoIdFromModelo) {
                    $legacyQuery->where('tipo_id', $legacyTipoIdFromModelo);
                } else {
                    $legacyQuery->whereRaw('1 = 0');
                }
            } elseif ($legacyTipoId) {
                $legacyQuery->where('tipo_id', $legacyTipoId);
            }

            if ($search !== '') {
                $legacyQuery->where(function ($builder) use ($search) {
                    $builder->where('nome_cli', 'like', '%' . $search . '%')
                        ->orWhere('nome_pecas', 'like', '%' . $search . '%')
                        ->orWhere('id_pecas', 'like', '%' . $search . '%')
                        ->orWhere('cod_sav', 'like', '%' . $search . '%');
                });
            }

            $pecas = $legacyQuery->paginate(20)->appends($request->query());
            $usingLegacyFallback = true;
        }

        $modelos = PeticaoModelo::orderBy('nome')->get();

        return view('pecas.index', [
            'pecas' => $pecas,
            'modelos' => $modelos,
            'selectedModelo' => $modeloId,
            'search' => $search,
            'usingLegacyFallback' => $usingLegacyFallback,
        ]);
    }
}
