@extends('layouts.app')

@section('title', 'Painel')

@push('head')
<style>
    .favorite-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    .favorite-card {
        display: grid;
        gap: 12px;
        min-height: 170px;
        padding: 16px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .favorite-card:hover {
        border-color: #1f5f8b;
        box-shadow: 0 8px 18px rgba(16, 42, 67, .08);
        transform: translateY(-1px);
    }
    .favorite-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .favorite-card__icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 6px;
        background: #eaf2f8;
        color: #1f5f8b;
        font-weight: bold;
        font-size: 17px;
    }
    .favorite-card__icon--legacy {
        background: #f8f0ff;
        color: #6b46c1;
    }
    .favorite-card__title {
        font-size: 15px;
        font-weight: bold;
        color: #102a43;
        line-height: 1.35;
        min-height: 40px;
    }
    .favorite-card__meta {
        display: grid;
        gap: 6px;
        font-size: 12px;
        color: #627d98;
    }
    .favorite-card__footer {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .favorite-card__badge {
        font-size: 11px;
        color: #486581;
        background: #f0f4f8;
        border: 1px solid #d9e2ec;
        border-radius: 999px;
        padding: 5px 8px;
    }
    .favorite-card__link {
        font-size: 13px;
        font-weight: bold;
    }
</style>
@endpush

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
        <div class="section-title">
            <h2 style="margin:0;">Favoritos</h2>
            <div class="editor-note">Atalhos pessoais para a montagem.</div>
        </div>
        @if($favoritos->isEmpty())
            <div class="editor-note">Nenhum modelo favorito selecionado ainda. Marque os favoritos na tela de montagem.</div>
        @else
            <div class="favorite-grid">
                @foreach($favoritos as $favorito)
                    <a href="{{ $favorito->link }}" class="favorite-card">
                        <div class="favorite-card__header">
                            <div class="favorite-card__icon @if($favorito->badge === 'Legado') favorite-card__icon--legacy @endif">
                                @if($favorito->badge === 'Legado') LG @else PJ @endif
                            </div>
                            <span class="favorite-card__badge">{{ $favorito->badge }}</span>
                        </div>
                        <div class="favorite-card__title">{{ $favorito->nome }}</div>
                        <div class="favorite-card__meta">
                            <span>{{ $favorito->subtitulo }}</span>
                        </div>
                        <div class="favorite-card__footer">
                            <span class="editor-note">Favorito</span>
                            <span class="favorite-card__link">Abrir montagem</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
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
