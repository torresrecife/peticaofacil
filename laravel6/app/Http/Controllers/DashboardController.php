<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Peca;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Setor;
use App\Tipo;
use App\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();

        $peticoesHojeNormalizadas = PeticaoNormalizada::with(['modelo', 'legacyUsuario'])
            ->whereDate('gerado_em', $today)
            ->orderByDesc('gerado_em')
            ->orderByDesc('id')
            ->get();

        $legacyIdsEspelhadosHoje = $peticoesHojeNormalizadas
            ->pluck('legacy_peca_id')
            ->filter()
            ->values()
            ->all();

        $peticoesHojeLegadas = Peca::with(['modeloNormalizado', 'tipo', 'usuario'])
            ->whereDate('data_cad', $today)
            ->when(!empty($legacyIdsEspelhadosHoje), function ($query) use ($legacyIdsEspelhadosHoje) {
                $query->whereNotIn('id_pecas', $legacyIdsEspelhadosHoje);
            })
            ->orderByDesc('data_cad')
            ->orderByDesc('id_pecas')
            ->get();

        $peticoesHoje = $this->mergePeticoesHoje($peticoesHojeNormalizadas, $peticoesHojeLegadas)
            ->take(10)
            ->values();

        $usuariosHoje = $this->buildUsuariosHoje($peticoesHojeNormalizadas, $peticoesHojeLegadas);
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
            if ($favorite->source === 'normalized' && $favorite->modelo_id) {
                $modelo = PeticaoModelo::with(['setor', 'cliente'])->find($favorite->modelo_id);

                if ($modelo) {
                    return (object) [
                        'nome' => $modelo->nome,
                        'subtitulo' => optional($modelo->setor)->nome_setor ?: 'Modelo normalizado',
                        'badge' => 'Normalizado',
                        'link' => route('peticoes.normalized.show', $modelo),
                    ];
                }
            }

            $legacyTipoId = (int) $favorite->legacy_tipo_id;
            $mirror = $legacyTipoId > 0
                ? PeticaoModelo::with(['setor', 'cliente'])->where('legacy_tipo_id', $legacyTipoId)->first()
                : null;

            if ($mirror) {
                return (object) [
                    'nome' => $mirror->nome,
                    'subtitulo' => optional($mirror->setor)->nome_setor ?: 'Modelo normalizado',
                    'badge' => 'Normalizado',
                    'link' => route('peticoes.normalized.show', $mirror),
                ];
            }

            $tipo = $legacyTipoId > 0 ? Tipo::with(['setor', 'cliente'])->find($legacyTipoId) : null;

            if ($tipo) {
                return (object) [
                    'nome' => $tipo->tipo_nome,
                    'subtitulo' => optional($tipo->setor)->nome_setor ?: 'Modelo legado',
                    'badge' => 'Legado',
                    'link' => route('peticoes.show', $tipo),
                ];
            }

            return null;
        })->filter()->values();
    }

    protected function mergePeticoesHoje($normalizadas, $legadas)
    {
        $items = collect();

        foreach ($normalizadas as $peticao) {
            $items->push((object) [
                'momento' => $peticao->gerado_em ?: $peticao->created_at,
                'cliente' => $peticao->cliente_referencia,
                'modelo' => optional($peticao->modelo)->nome ?: $peticao->nome_arquivo,
                'usuario' => optional($peticao->legacyUsuario)->nome_usu,
                'origem' => 'Normalizada',
                'link' => route('peticoes.saved.edit', $peticao),
            ]);
        }

        foreach ($legadas as $peca) {
            $items->push((object) [
                'momento' => $peca->data_cad,
                'cliente' => $peca->nome_cli,
                'modelo' => optional($peca->modeloNormalizado)->nome ?: optional($peca->tipo)->tipo_nome ?: $peca->nome_pecas,
                'usuario' => optional($peca->usuario)->nome_usu,
                'origem' => 'Legada',
                'link' => route('peticoes.editor.edit', $peca),
            ]);
        }

        return $items->sortByDesc(function ($item) {
            return optional($item->momento)->timestamp ?: 0;
        });
    }

    protected function buildUsuariosHoje($normalizadas, $legadas)
    {
        $totais = [];

        foreach ($normalizadas as $peticao) {
            if (!$peticao->legacy_usuario_id) {
                continue;
            }

            $userId = (int) $peticao->legacy_usuario_id;
            if (!isset($totais[$userId])) {
                $totais[$userId] = [
                    'legacy_usuario_id' => $userId,
                    'nome_usu' => optional($peticao->legacyUsuario)->nome_usu ?: ('Usuario #' . $userId),
                    'total_peticoes' => 0,
                ];
            }

            $totais[$userId]['total_peticoes']++;
        }

        foreach ($legadas as $peca) {
            if (!$peca->id_usu) {
                continue;
            }

            $userId = (int) $peca->id_usu;
            if (!isset($totais[$userId])) {
                $totais[$userId] = [
                    'legacy_usuario_id' => $userId,
                    'nome_usu' => optional($peca->usuario)->nome_usu ?: ('Usuario #' . $userId),
                    'total_peticoes' => 0,
                ];
            }

            $totais[$userId]['total_peticoes']++;
        }

        return collect($totais)
            ->map(function ($item) {
                return (object) $item;
            })
            ->sort(function ($a, $b) {
                if ($a->total_peticoes === $b->total_peticoes) {
                    return $a->legacy_usuario_id <=> $b->legacy_usuario_id;
                }

                return $b->total_peticoes <=> $a->total_peticoes;
            })
            ->values();
    }
}
