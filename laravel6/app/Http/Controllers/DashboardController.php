<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\PeticaoNormalizada;
use App\Setor;
use App\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();

        $peticoesHoje = PeticaoNormalizada::with(['modelo', 'legacyUsuario'])
            ->whereDate('salvo_em', $today)
            ->orderByDesc('salvo_em')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $usuariosHoje = PeticaoNormalizada::select('legacy_usuario_id', DB::raw('count(*) as total_peticoes'))
            ->with('legacyUsuario')
            ->whereDate('salvo_em', $today)
            ->whereNotNull('legacy_usuario_id')
            ->groupBy('legacy_usuario_id')
            ->orderByDesc('total_peticoes')
            ->orderBy('legacy_usuario_id')
            ->get();

        return view('dashboard', [
            'userCount' => User::count(),
            'setorCount' => Setor::count(),
            'clienteCount' => Cliente::count(),
            'activeUserCount' => User::active()->count(),
            'todayLabel' => now()->format('d/m/Y'),
            'peticoesHoje' => $peticoesHoje,
            'usuariosHoje' => $usuariosHoje,
        ]);
    }
}
