@extends('layouts.app')
@section('title', 'Revisar modelo criado por IA')
@section('content')
<div class="topbar"><h2>Revisar modelo criado por IA</h2><a class="button secondary link" href="{{ route('admin.modelos-normalizados.create') }}">Cancelar</a></div>
<div class="panel">
    <p class="editor-note">Revise o resultado antes de gravar. A IA pode interpretar incorretamente campos jurídicos.</p>
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
                @if(!empty($field['comportamento']))<br>Máscara: {{ $field['comportamento'] }}@endif
                @if($field['obrigatorio'])
                    <span>(obrigatório)</span>
                @endif
                @if(!empty($field['opcoes']))
                    <br>Opções: {{ implode(', ', $field['opcoes']) }}
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
