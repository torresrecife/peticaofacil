@extends('layouts.app')

@section('title', 'Estatisticas')

@push('head')
<style>
    .stats-page {
        display: grid;
        gap: 20px;
    }
    .stats-kpis {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }
    .stats-kpi {
        background: #fff;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 16px;
        display: grid;
        gap: 6px;
    }
    .stats-kpi span {
        font-size: 12px;
        color: #7b8794;
        text-transform: uppercase;
    }
    .stats-kpi strong {
        font-size: 30px;
        color: #102a43;
        line-height: 1;
    }
    .stats-layout {
        display: grid;
        gap: 20px;
        grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.8fr);
    }
    .stats-panel {
        background: #fff;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 18px;
        display: grid;
        gap: 16px;
    }
    .stats-panel h3 {
        margin: 0;
        color: #102a43;
        font-size: 18px;
    }
    .stats-subtitle {
        color: #7b8794;
        font-size: 13px;
    }
    .stats-bar-chart {
        display: grid;
        gap: 12px;
    }
    .stats-bar-chart--compact {
        gap: 10px;
    }
    .stats-bar-row {
        display: grid;
        gap: 8px;
        grid-template-columns: 60px minmax(0, 1fr) 52px;
        align-items: center;
    }
    .stats-bar-row--wide {
        grid-template-columns: minmax(120px, 210px) minmax(0, 1fr) 52px;
    }
    .stats-bar-label,
    .stats-bar-value {
        font-size: 13px;
        color: #486581;
    }
    .stats-bar-track {
        position: relative;
        height: 14px;
        border-radius: 999px;
        background: #eaf2f8;
        overflow: hidden;
    }
    .stats-bar-fill {
        position: absolute;
        inset: 0 auto 0 0;
        border-radius: 999px;
        background: linear-gradient(90deg, #2f80ed 0%, #1f5f8b 100%);
    }
    .stats-segments {
        display: flex;
        height: 18px;
        border-radius: 999px;
        overflow: hidden;
        background: #eaf2f8;
    }
    .stats-segment {
        min-width: 0;
    }
    .stats-legend {
        display: grid;
        gap: 10px;
    }
    .stats-legend-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        font-size: 13px;
        color: #486581;
    }
    .stats-swatch {
        width: 12px;
        height: 12px;
        border-radius: 999px;
    }
    @media (max-width: 980px) {
        .stats-layout {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Estatisticas do sistema</h2>
    <div class="editor-note">Visao consolidada de operacao, producao e uso dos modelos.</div>
</div>

<div class="stats-page">
    <section class="stats-kpis">
        <div class="stats-kpi">
            <span>Total de peticoes</span>
            <strong>{{ $totalPeticoes }}</strong>
        </div>
        <div class="stats-kpi">
            <span>Peticoes em 30 dias</span>
            <strong>{{ $peticoesUltimos30Dias }}</strong>
        </div>
        <div class="stats-kpi">
            <span>Usuarios ativos</span>
            <strong>{{ $usuariosAtivos }}</strong>
        </div>
        <div class="stats-kpi">
            <span>Total de usuarios</span>
            <strong>{{ $totalUsuarios }}</strong>
        </div>
        <div class="stats-kpi">
            <span>Modelos ativos</span>
            <strong>{{ $totalModelos }}</strong>
        </div>
        <div class="stats-kpi">
            <span>Clientes / Setores</span>
            <strong>{{ $totalClientes }} / {{ $totalSetores }}</strong>
        </div>
    </section>

    <section class="stats-layout">
        <div class="stats-panel">
            <div>
                <h3>Producao dos ultimos 14 dias</h3>
                <div class="stats-subtitle">Quantidade de peticoes geradas por dia.</div>
            </div>

            <div class="stats-bar-chart">
                @foreach($series as $item)
                    <div class="stats-bar-row">
                        <div class="stats-bar-label">{{ $item['label'] }}</div>
                        <div class="stats-bar-track">
                            <div class="stats-bar-fill" style="width: {{ round(($item['total'] / $seriesMax) * 100, 2) }}%;"></div>
                        </div>
                        <div class="stats-bar-value">{{ $item['total'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="stats-panel">
            <div>
                <h3>Origem das peticoes</h3>
                <div class="stats-subtitle">Distribuicao entre peticoes baseadas em modelo e avulsas.</div>
            </div>

            <div class="stats-segments" aria-hidden="true">
                @foreach($originBreakdown as $origin)
                    <div class="stats-segment" style="width: {{ round(($origin['total'] / $originTotal) * 100, 2) }}%; background: {{ $origin['color'] }};"></div>
                @endforeach
            </div>

            <div class="stats-legend">
                @foreach($originBreakdown as $origin)
                    <div class="stats-legend-row">
                        <span class="stats-swatch" style="background: {{ $origin['color'] }};"></span>
                        <span>{{ $origin['label'] }}</span>
                        <strong>{{ $origin['total'] }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="stats-layout">
        <div class="stats-panel">
            <div>
                <h3>Modelos mais usados</h3>
                <div class="stats-subtitle">Top modelos pelo total de peticoes geradas.</div>
            </div>

            @if(empty($topModelos))
                <div class="editor-note">Nenhuma peticao com modelo encontrada.</div>
            @else
                <div class="stats-bar-chart stats-bar-chart--compact">
                    @foreach($topModelos as $item)
                        <div class="stats-bar-row stats-bar-row--wide">
                            <div class="stats-bar-label">{{ $item['label'] }}</div>
                            <div class="stats-bar-track">
                                <div class="stats-bar-fill" style="width: {{ round(($item['total'] / $topModelosMax) * 100, 2) }}%;"></div>
                            </div>
                            <div class="stats-bar-value">{{ $item['total'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="stats-panel">
            <div>
                <h3>Usuarios com mais producao</h3>
                <div class="stats-subtitle">Top usuarios por quantidade de peticoes geradas.</div>
            </div>

            @if(empty($topUsuarios))
                <div class="editor-note">Nenhuma peticao vinculada a usuario encontrada.</div>
            @else
                <div class="stats-bar-chart stats-bar-chart--compact">
                    @foreach($topUsuarios as $item)
                        <div class="stats-bar-row stats-bar-row--wide">
                            <div class="stats-bar-label">{{ $item['label'] }}</div>
                            <div class="stats-bar-track">
                                <div class="stats-bar-fill" style="width: {{ round(($item['total'] / $topUsuariosMax) * 100, 2) }}%;"></div>
                            </div>
                            <div class="stats-bar-value">{{ $item['total'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
