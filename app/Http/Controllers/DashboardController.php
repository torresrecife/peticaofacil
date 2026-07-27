<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Setor;
use App\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();

        $peticoesHojeNormalizadas = PeticaoNormalizada::with(['modelo', 'user'])
            ->whereDate('gerado_em', $today)
            ->orderByDesc('gerado_em')
            ->orderByDesc('id')
            ->get();

        $peticoesHoje = $this->mapPeticoesHoje($peticoesHojeNormalizadas)
            ->take(10)
            ->values();

        $usuariosHoje = $this->buildUsuariosHoje($peticoesHojeNormalizadas);
        $favoritos = $this->buildFavoritos();

        return view('dashboard', [
            'userCount' => User::count(),
            'setorCount' => Setor::count(),
            'clienteCount' => Cliente::count(),
            'activeUserCount' => User::active()->count(),
            'todayLabel' => now()->format('d/m/Y'),
            'peticoesHoje' => $peticoesHoje,
            'usuariosHoje' => $usuariosHoje,
            'favoritos' => $favoritos,
        ]);
    }

    protected function buildFavoritos()
    {
        $favorites = Auth::user()
            ->favoriteModelos()
            ->orderBy('created_at')
            ->get();

        return $favorites->map(function ($favorite) {
            $modelo = null;

            if ($favorite->modelo_id) {
                $modelo = PeticaoModelo::with(['setor', 'cliente'])->find($favorite->modelo_id);
            }

            if (!$modelo && (int) $favorite->legacy_tipo_id > 0) {
                $modelo = PeticaoModelo::with(['setor', 'cliente'])
                    ->where('legacy_tipo_id', (int) $favorite->legacy_tipo_id)
                    ->first();
            }

            if ($modelo) {
                return (object) [
                    'nome' => $modelo->nome,
                    'subtitulo' => optional($modelo->setor)->nome_setor ?: 'Modelo normalizado',
                    'badge' => 'Normalizado',
                    'link' => route('peticoes.normalized.show', $modelo),
                ];
            }

            return null;
        })->filter()->unique('link')->values();
    }

    protected function mapPeticoesHoje($normalizadas)
    {
        $items = collect();

        foreach ($normalizadas as $peticao) {
            $items->push((object) [
                'momento' => $peticao->gerado_em ?: $peticao->created_at,
                'cliente' => $peticao->cliente_referencia,
                'modelo' => optional($peticao->modelo)->nome ?: $peticao->nome_arquivo,
                'usuario' => optional($peticao->user)->nome_usu,
                'link' => route('peticoes.saved.edit', $peticao),
            ]);
        }

        return $items->sortByDesc(function ($item) {
            return optional($item->momento)->timestamp ?: 0;
        });
    }

    protected function buildUsuariosHoje($normalizadas)
    {
        $totais = [];

        foreach ($normalizadas as $peticao) {
            $userId = $peticao->user_id ?: null;
            if (!$userId) {
                continue;
            }

            $indexKey = 'user:' . $userId;
            if (!isset($totais[$indexKey])) {
                $totais[$indexKey] = [
                    'user_id' => $userId,
                    'nome_usu' => optional($peticao->user)->nome_usu ?: ('Usuario #' . $userId),
                    'total_peticoes' => 0,
                ];
            }

            $totais[$indexKey]['total_peticoes']++;
        }

        return collect($totais)
            ->map(function ($item) {
                return (object) $item;
            })
            ->sort(function ($a, $b) {
                if ($a->total_peticoes === $b->total_peticoes) {
                    return $a->user_id <=> $b->user_id;
                }

                return $b->total_peticoes <=> $a->total_peticoes;
            })
            ->values();
    }
}
