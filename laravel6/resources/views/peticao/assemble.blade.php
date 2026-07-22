@extends('layouts.app')

@section('title', 'Montagem de peticao')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $modelo->tipo_nome }}</h2>
    <a class="button secondary link" href="{{ route('peticoes.index') }}">Voltar</a>
</div>

<div class="stack">
    <div class="panel">
        <div class="section-title">
            <h3>Dados da montagem</h3>
            <div class="editor-note">Campos dinamicos do modelo, com retorno aplicado para `SELECT`.</div>
        </div>

        @if($lookupStatus)
            <div class="flash">{{ $lookupStatus }}</div>
        @endif

        <form method="post" action="{{ route('peticoes.compose', $modelo) }}">
            @csrf
            @if($modelo->servidor)
                <div class="panel-muted" style="margin-bottom:20px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pesquisa por processo</label>
                            <input name="codigo_processo" value="{{ $codigoProcesso ?? '' }}" placeholder="{{ $modelo->servidor->chave_db ?: 'Codigo do processo' }}">
                            <div class="editor-note">Busca no SQL Server configurado para este modelo e preenche automaticamente os campos mapeados.</div>
                        </div>
                        <div class="form-group" style="justify-content:end;">
                            <label>&nbsp;</label>
                            <button type="submit" name="action_type" value="lookup">Buscar e preencher</button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="form-grid">
                @foreach($modelo->campos as $campo)
                    @if($campo->input_tipo === 'TITLE')
                        <div class="form-group full">
                            <div class="panel-muted"><strong>{{ $campo->input_title }}</strong></div>
                        </div>
                    @elseif($campo->input_tipo === 'HIDDEN')
                        <input type="hidden" name="campo_{{ $campo->id_input }}" value="{{ $values['campo_'.$campo->id_input] ?? '' }}">
                    @elseif($campo->input_tipo === 'SELECT')
                        <div class="form-group @if((int) $campo->input_cols >= 2) full @endif">
                            <label>{{ $campo->input_title }}</label>
                            <select name="campo_{{ $campo->id_input }}">
                                <option value=""></option>
                                @foreach($campo->select_options as $option)
                                    <option value="{{ $option['label'] }}" @if(($values['campo_'.$campo->id_input] ?? '') === $option['label']) selected @endif>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="editor-note">Token {{ $campo->placeholder }}. O retorno usa a segunda coluna cadastrada em cada opcao.</div>
                        </div>
                    @elseif($campo->input_tipo === 'TEXTAREA')
                        <div class="form-group full">
                            <label>{{ $campo->input_title }}</label>
                            <textarea name="campo_{{ $campo->id_input }}">{{ $values['campo_'.$campo->id_input] ?? '' }}</textarea>
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @else
                        <div class="form-group @if((int) $campo->input_cols >= 2) full @endif">
                            <label>{{ $campo->input_title }}</label>
                            <input name="campo_{{ $campo->id_input }}" value="{{ $values['campo_'.$campo->id_input] ?? '' }}">
                            <div class="editor-note">Token {{ $campo->placeholder }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            <div style="margin-top:20px;">
                <button type="submit" name="action_type" value="preview">Gerar preview</button>
            </div>
        </form>
    </div>

    @if($preview)
        <div class="panel">
            <div class="section-title">
                <h3>Preview da peticao</h3>
                <div class="editor-note">Nome sugerido: {{ $preview['suggested_filename'] }}</div>
            </div>
            <div class="panel-muted" style="background:#fff;">
                {!! $preview['html'] !!}
            </div>
            <form method="post" action="{{ route('peticoes.saved.store', $modelo) }}" style="margin-top:16px;">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ $preview['suggested_filename'] }}">
                <input type="hidden" name="resolved_fields" value="{{ e(json_encode($preview['resolved_fields'])) }}">
                <textarea name="content" style="display:none;">{{ $preview['html'] }}</textarea>
                <button type="submit">Abrir peticao normalizada</button>
            </form>
            <form method="post" action="{{ route('peticoes.editor.create', $modelo) }}" style="margin-top:12px;">
                @csrf
                <input type="hidden" name="nome_cli" value="{{ $preview['suggested_filename'] }}">
                <textarea name="content" style="display:none;">{{ $preview['html'] }}</textarea>
                <button type="submit" class="button secondary">Abrir editor legado</button>
            </form>
        </div>
    @endif
</div>
@endsection
