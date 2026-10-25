@extends('layouts.app')
@section('title', 'Revisar modelo criado por IA')
@section('content')
<div class="topbar"><h2>Revisar modelo criado por IA</h2><a class="button secondary link" href="{{ route('admin.modelos-normalizados.create') }}">Cancelar</a></div>
<div class="panel">
    <p class="editor-note">Revise o resultado antes de gravar. A IA pode interpretar incorretamente campos jurídicos. As dinâmicas propostas podem ser ajustadas depois, no painel "Dinâmica do campo" de cada campo.</p>
    <form method="post" action="{{ route('admin.modelos-normalizados.create-ai') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group"><label>Nome do modelo</label><input name="nome" value="{{ $analysis['nome'] }}" readonly></div>
            <div class="form-group"><label>Setor</label><select name="id_setor" required>@foreach($setores as $setor)<option value="{{ $setor->id_setor }}">{{ $setor->nome_setor }}</option>@endforeach</select></div>
            <div class="form-group"><label>Cliente (opcional)</label><select name="id_cliente"><option value="">Todos do setor</option>@foreach($clientes as $cliente)<option value="{{ $cliente->cliente_id }}">{{ $cliente->cliente_name }}</option>@endforeach</select></div>
            <div class="form-group"><label>Servidor NEO</label><select name="id_db"><option value="">Nenhum</option>@foreach($servidores as $servidor)<option value="{{ $servidor->id_db }}">{{ $servidor->nome_db }}</option>@endforeach</select></div>
            <div class="form-group"><label>Formato</label><select name="tipo_arq"><option value="pdf">PDF</option><option value="word">Word</option><option value="pdf,word">PDF e Word</option></select></div>
        </div>
        <h3>Campos identificados</h3>
        @foreach($analysis['campos'] as $field)
            <div class="panel-muted">
                <strong>{{ $field['rotulo'] }}</strong> — {{ $field['tipo'] }} — <code>{{ $field['token'] }}</code>
                @if(!empty($field['origem_coluna']))<br>Coluna NEO: <code>{{ $field['origem_coluna'] }}</code>@endif
                @if(!empty($field['lista_grupo']))<br>Lista pre-definida: <code>{{ $field['lista_grupo'] }}</code> / <code>{{ $field['lista_retorno'] ?: 'return_1' }}</code>@endif
                @if(!empty($field['comportamento']))<br>Máscara: {{ $field['comportamento'] }}@endif
                @if(!empty($field['marcador']) && $field['marcador'] !== 'COMO_DIGITADO')<br>No texto: {{ $field['marcador'] === 'MAIUSCULAS' ? 'MAIÚSCULAS' : 'Iniciais Maiúsculas' }}@endif
                @if(!empty($field['extenso']))<br>Extenso: {{ ['data_por_extenso' => 'data por extenso', 'data_atual_por_extenso' => 'data de hoje por extenso', 'valor_por_extenso' => 'valor por extenso'][$field['extenso']] ?? $field['extenso'] }}@endif
                @if($field['obrigatorio'])
                    <span>(obrigatório)</span>
                @endif
                @if(!empty($field['opcoes']))
                    <br>Opções:
                    @foreach($field['opcoes'] as $option)
                        @php
                            $option = is_array($option) ? $option : ['rotulo' => $option, 'retornos' => [$option]];
                            $retorno = trim(strip_tags(html_entity_decode((string) ($option['retornos'][0] ?? ''), ENT_QUOTES, 'UTF-8')));
                        @endphp
                        <br>&nbsp;&nbsp;• <strong>{{ $option['rotulo'] }}</strong>@if($retorno !== '' && $retorno !== $option['rotulo']) → <span class="editor-note" style="display:inline;">{{ \Illuminate\Support\Str::limit($retorno, 140) }}</span>@endif
                    @endforeach
                @endif
                @if(!empty($field['dinamica']['tipo']))
                    @php
                        $rotuloPorToken = collect($analysis['campos'])->mapWithKeys(function ($f) { return [strtolower($f['token']) => $f['rotulo']]; });
                        $nomes = function ($tokens) use ($rotuloPorToken) { return collect((array) $tokens)->map(function ($t) use ($rotuloPorToken) { return $rotuloPorToken[strtolower($t)] ?? $t; })->implode(', '); };
                        $dinamica = $field['dinamica'];
                    @endphp
                    <br><strong>Dinâmica:</strong> {{ \App\Support\CampoDinamica::TYPES[$dinamica['tipo']] ?? $dinamica['tipo'] }}
                    @if(!empty($dinamica['alvos'])) — campos: {{ $nomes($dinamica['alvos']) }}@endif
                    @if(!empty($dinamica['valor'])) — quando o valor for "{{ $dinamica['valor'] }}"@endif
                    @if($dinamica['tipo'] === 'qualificacao') — PESSOA: {{ $nomes([$dinamica['pessoa']]) }}; documento: {{ $nomes([$dinamica['documento']]) }}@endif
                @endif
            </div>
        @endforeach
        <h3>Parágrafos</h3>
        @foreach($analysis['paragrafos'] as $paragraph)
            <details open><summary>{{ $paragraph['titulo'] }}</summary><div class="panel-muted">{!! $paragraph['conteudo_html'] !!}</div></details>
        @endforeach
        <button type="submit">Confirmar e criar modelo</button>
    </form>
</div>
@endsection
