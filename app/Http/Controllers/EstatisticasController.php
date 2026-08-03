<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Setor;
use App\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Schema;

class EstatisticasController extends Controller
{
    public function __invoke()
    {
        $series = $this->buildDailySeries(14);
        $originBreakdown = $this->buildOriginBreakdown();
        $topModelos = $this->buildTopModelos(6);
        $topUsuarios = $this->buildTopUsuarios(6);

        return view('estatisticas.index', [
            'totalPeticoes' => $this->hasTable('peticoes') ? PeticaoNormalizada::count() : 0,
            'peticoesUltimos30Dias' => $this->hasTable('peticoes') ? PeticaoNormalizada::whereDate('gerado_em', '>=', now()->subDays(29)->toDateString())->count() : 0,
            'totalUsuarios' => $this->hasTable('users') ? User::count() : 0,
            'usuariosAtivos' => $this->hasTable('users') ? User::active()->count() : 0,
            'totalModelos' => $this->hasTable('peticao_modelos') ? PeticaoModelo::count() : 0,
            'totalClientes' => $this->hasTable('clientes') ? Cliente::count() : 0,
            'totalSetores' => $this->hasTable('setores') ? Setor::count() : 0,
            'series' => $series,
            'originBreakdown' => $originBreakdown,
            'topModelos' => $topModelos,
            'topUsuarios' => $topUsuarios,
            'seriesMax' => max(1, collect($series)->max('total')),
            'originTotal' => max(1, collect($originBreakdown)->sum('total')),
            'topModelosMax' => max(1, collect($topModelos)->max('total')),
            'topUsuariosMax' => max(1, collect($topUsuarios)->max('total')),
        ]);
    }

    protected function buildDailySeries($days)
    {
        if (!$this->hasTable('peticoes')) {
            return collect(range(0, $days - 1))
                ->map(function ($offset) use ($days) {
                    $date = now()->startOfDay()->subDays(($days - 1) - $offset);

                    return [
                        'date' => $date->format('Y-m-d'),
                        'label' => $date->format('d/m'),
                        'total' => 0,
                    ];
                })
                ->all();
        }

        $start = now()->startOfDay()->subDays($days - 1);
        $end = now()->endOfDay();

        $totals = PeticaoNormalizada::query()
            ->whereBetween('gerado_em', [$start, $end])
            ->get(['gerado_em'])
            ->groupBy(function ($peticao) {
                return optional($peticao->gerado_em)->format('Y-m-d');
            })
            ->map->count();

        return collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(function ($date) use ($totals) {
                $key = $date->format('Y-m-d');

                return [
                    'date' => $key,
                    'label' => $date->format('d/m'),
                    'total' => (int) ($totals[$key] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    protected function buildOriginBreakdown()
    {
        if (!$this->hasTable('peticoes')) {
            return [
                ['label' => 'Modelo', 'total' => 0, 'color' => '#1f5f8b'],
                ['label' => 'Avulsa', 'total' => 0, 'color' => '#8b5cf6'],
            ];
        }

        $rows = PeticaoNormalizada::query()->get(['campos_resolvidos']);

        $totals = [
            'Modelo' => 0,
            'Avulsa' => 0,
        ];

        foreach ($rows as $peticao) {
            $origin = data_get($peticao->campos_resolvidos, 'origem') === 'avulsa' ? 'Avulsa' : 'Modelo';
            $totals[$origin]++;
        }

        return collect([
            ['label' => 'Modelo', 'total' => $totals['Modelo'], 'color' => '#1f5f8b'],
            ['label' => 'Avulsa', 'total' => $totals['Avulsa'], 'color' => '#8b5cf6'],
        ])->values()->all();
    }

    protected function buildTopModelos($limit)
    {
        if (!$this->hasTable('peticao_modelos') || !$this->hasTable('peticoes')) {
            return [];
        }

        $modeloMap = PeticaoModelo::query()
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->keyBy('id');

        return PeticaoNormalizada::query()
            ->selectRaw('modelo_id, COUNT(*) as total')
            ->whereNotNull('modelo_id')
            ->groupBy('modelo_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($modeloMap) {
                $modelo = $modeloMap->get($row->modelo_id);

                return [
                    'label' => $modelo ? $modelo->nome : ('Modelo #' . $row->modelo_id),
                    'total' => (int) $row->total,
                ];
            })
            ->values()
            ->all();
    }

    protected function buildTopUsuarios($limit)
    {
        if (!$this->hasTable('users') || !$this->hasTable('peticoes')) {
            return [];
        }

        $userMap = User::query()
            ->orderBy('nome_usu')
            ->get(['id', 'nome_usu', 'login_usu'])
            ->keyBy('id');

        return PeticaoNormalizada::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($userMap) {
                $user = $userMap->get($row->user_id);

                return [
                    'label' => $user ? ($user->nome_usu ?: $user->login_usu) : ('Usuario #' . $row->user_id),
                    'total' => (int) $row->total,
                ];
            })
            ->values()
            ->all();
    }

    protected function hasTable($table)
    {
        return Schema::hasTable($table);
    }
}
