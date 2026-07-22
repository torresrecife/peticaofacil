@extends('layouts.app')

@section('title', 'Painel')

@section('content')
<div class="grid">
    <div class="stat">
        Usuarios
        <strong>{{ $userCount }}</strong>
    </div>
    <div class="stat">
        Usuarios ativos
        <strong>{{ $activeUserCount }}</strong>
    </div>
    <div class="stat">
        Setores
        <strong>{{ $setorCount }}</strong>
    </div>
    <div class="stat">
        Clientes
        <strong>{{ $clienteCount }}</strong>
    </div>
</div>

<div style="display:grid; gap:24px; grid-template-columns:minmax(0, 1.4fr) minmax(320px, 0.9fr); margin-top:24px;">
    <div class="panel">
        <h2 style="margin-top:0;">Etapa atual</h2>
        <p>A base Laravel 6 ja esta conectada ao banco atual e os primeiros modulos administrativos usam dados reais do sistema legado.</p>
    </div>

    <div class="stack">
        <div class="panel">
            <div class="section-title">
                <h3>Peticoes de hoje</h3>
                <div class="editor-note">{{ $todayLabel }} - ultimas 10</div>
            </div>
            @if($peticoesHoje->isEmpty())
                <div class="editor-note">Nenhuma peticao salva hoje.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Horario</th>
                            <th>Cliente</th>
                            <th>Modelo</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($peticoesHoje as $peticao)
                        <tr>
                            <td>{{ optional($peticao->momento)->format('H:i') }}</td>
                            <td><a href="{{ $peticao->link }}">{{ $peticao->cliente }}</a></td>
                            <td>{{ $peticao->modelo }}</td>
                            <td>{{ $peticao->origem }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="panel">
            <div class="section-title">
                <h3>Usuarios do dia</h3>
                <div class="editor-note">{{ $todayLabel }}</div>
            </div>
            @if($usuariosHoje->isEmpty())
                <div class="editor-note">Nenhuma peticao atribuida a usuarios hoje.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Qtd.</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($usuariosHoje as $linha)
                        <tr>
                            <td>{{ $linha->nome_usu }}</td>
                            <td>{{ $linha->total_peticoes }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
