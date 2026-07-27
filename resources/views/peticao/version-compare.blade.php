@extends('layouts.app')

@section('title', 'Comparacao de versoes')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Comparacao de versoes</h2>
    <div class="actions">
        <a class="button secondary link" href="{{ route('peticoes.saved.edit', $peticao) }}">Voltar ao editor</a>
    </div>
</div>

<div class="stack">
    <div class="panel">
        <div class="section-title">
            <h3>{{ optional($peticao->modelo)->nome ?: 'Peticao normalizada' }}</h3>
            <div class="editor-note">Auditoria entre snapshots por bloco/paragrafo.</div>
        </div>
        <div class="grid">
            <div class="stat">
                <span>Base</span>
                <strong>{{ $comparison['base']->origem_snapshot }} #{{ $comparison['base']->versao_numero }}</strong>
            </div>
            <div class="stat">
                <span>Alvo</span>
                <strong>{{ $comparison['target']->origem_snapshot }} @if($comparison['target']->versao_numero) #{{ $comparison['target']->versao_numero }} @else atual @endif</strong>
            </div>
            <div class="stat">
                <span>Alteradas</span>
                <strong>{{ $comparison['summary']['changed'] }}</strong>
            </div>
            <div class="stat">
                <span>Adicionadas / removidas</span>
                <strong>{{ $comparison['summary']['added'] }} / {{ $comparison['summary']['removed'] }}</strong>
            </div>
        </div>
    </div>

    @if(!empty($comparison['changes']))
        <div class="panel">
            <div class="section-title">
                <h3>Navegar alteracoes</h3>
                <div class="editor-note">{{ count($comparison['changes']) }} bloco(s) com diferenca.</div>
            </div>
            <div class="actions" style="flex-wrap:wrap;">
                @foreach($comparison['changes'] as $row)
                    <a class="button secondary link" href="#{{ $row['anchor'] }}">Bloco {{ $row['line'] }} ({{ $row['status'] }})</a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="panel" style="padding:0;">
        <table>
            <thead>
                <tr>
                    <th>Bloco</th>
                    <th>Base</th>
                    <th>Alvo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comparison['rows'] as $row)
                    <tr id="{{ $row['anchor'] }}">
                        <td>{{ $row['line'] }}</td>
                        <td><pre style="white-space:pre-wrap;margin:0;">{!! $row['left_html'] !!}</pre></td>
                        <td><pre style="white-space:pre-wrap;margin:0;">{!! $row['right_html'] !!}</pre></td>
                        <td>{{ $row['status'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Sem diferencas processadas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
