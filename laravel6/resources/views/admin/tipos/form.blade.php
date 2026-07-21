@extends('layouts.app')

@section('title', $modelo->exists ? 'Editar modelo' : 'Novo modelo')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $modelo->exists ? 'Editar modelo' : 'Novo modelo' }}</h2>
    <a class="button secondary link" href="{{ route('admin.modelos.index') }}">Voltar</a>
</div>

<div class="stack">
    <div class="panel">
        <form method="post" action="{{ $modelo->exists ? route('admin.modelos.update', $modelo) : route('admin.modelos.store') }}">
            @csrf
            @if($modelo->exists)
                @method('put')
            @endif
            <div class="form-grid">
                <div class="form-group">
                    <label>Titulo</label>
                    <input name="tipo_nome" value="{{ old('tipo_nome', $modelo->tipo_nome) }}" required>
                </div>
                <div class="form-group">
                    <label>Descricao</label>
                    <input name="nome_pre" value="{{ old('nome_pre', $modelo->nome_pre) }}">
                </div>
                <div class="form-group">
                    <label>Setor</label>
                    <select name="id_setor" required>
                        @foreach($setores as $setor)
                            <option value="{{ $setor->id_setor }}" @if((string) old('id_setor', $modelo->id_setor) === (string) $setor->id_setor) selected @endif>{{ $setor->nome_setor }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Cliente</label>
                    <select name="id_cliente">
                        <option value="">Todos do setor</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->cliente_id }}" @if((string) old('id_cliente', $modelo->id_cliente) === (string) $cliente->cliente_id) selected @endif>{{ $cliente->cliente_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Servidor SQL</label>
                    <select name="id_db">
                        <option value="">Nenhum</option>
                        @foreach($servidores as $servidor)
                            <option value="{{ $servidor->id_db }}" @if((string) old('id_db', $modelo->id_db) === (string) $servidor->id_db) selected @endif>{{ $servidor->nome_db }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Arquivo</label>
                    <select name="tipo_arq">
                        <option value="pdf" @if(old('tipo_arq', $modelo->tipo_arq) === 'pdf') selected @endif>PDF</option>
                        <option value="word" @if(old('tipo_arq', $modelo->tipo_arq) === 'word') selected @endif>Word</option>
                        <option value="pdf,word" @if(old('tipo_arq', $modelo->tipo_arq) === 'pdf,word') selected @endif>PDF e Word</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="tipo_stt">
                        <option value="Y" @if(old('tipo_stt', $modelo->tipo_stt) === 'Y') selected @endif>Ativo</option>
                        <option value="N" @if(old('tipo_stt', $modelo->tipo_stt) === 'N') selected @endif>Inativo</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Cabecalho</label>
                    <textarea name="cod_cabec">{{ old('cod_cabec', $modelo->cod_cabec) }}</textarea>
                </div>
                <div class="form-group full">
                    <label>Rodape</label>
                    <textarea name="cod_rodap">{{ old('cod_rodap', $modelo->cod_rodap) }}</textarea>
                </div>
            </div>
            <div style="margin-top:20px;">
                <button type="submit">Salvar modelo</button>
            </div>
        </form>
    </div>

    @if($modelo->exists)
        <div class="panel">
            <h3 style="margin-top:0;">Paragrafos</h3>
            <form method="post" action="{{ route('admin.modelos.paragrafos.store', $modelo) }}" style="margin-bottom:20px;">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Titulo</label>
                        <input name="fund_titulo" required>
                    </div>
                    <div class="form-group full">
                        <label>Texto</label>
                        <textarea name="fund_text"></textarea>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <button type="submit">Adicionar paragrafo</button>
                </div>
            </form>

            <div class="stack">
                @foreach($modelo->paragrafos as $paragrafo)
                    <form method="post" action="{{ route('admin.modelos.paragrafos.update', [$modelo, $paragrafo]) }}" class="panel" style="padding:16px;">
                        @csrf
                        @method('put')
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Titulo</label>
                                <input name="fund_titulo" value="{{ $paragrafo->fund_titulo }}" required>
                            </div>
                            <div class="form-group">
                                <label>Ordem</label>
                                <input name="fund_order" type="number" min="1" value="{{ $paragrafo->fund_order }}">
                            </div>
                            <div class="form-group full">
                                <label>Texto</label>
                                <textarea name="fund_text">{{ $paragrafo->fund_text }}</textarea>
                            </div>
                        </div>
                        <div style="margin-top:12px;">
                            <button type="submit">Salvar paragrafo</button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h3 style="margin-top:0;">Campos dinamicos</h3>
            <form method="post" action="{{ route('admin.modelos.campos.store', $modelo) }}" style="margin-bottom:20px;">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Titulo</label>
                        <input name="input_title" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="input_tipo">
                            <option value="TEXT">Texto</option>
                            <option value="TEXTAREA">Textarea</option>
                            <option value="SELECT">Select</option>
                            <option value="HIDDEN">Oculto</option>
                            <option value="TITLE">Titulo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Prefixo</label>
                        <input name="input_pre">
                    </div>
                    <div class="form-group">
                        <label>Sufixo</label>
                        <input name="input_pos">
                    </div>
                    <div class="form-group">
                        <label>Base externa</label>
                        <input name="input_db">
                    </div>
                    <div class="form-group">
                        <label>Coluna/valor</label>
                        <input name="input_val">
                    </div>
                    <div class="form-group">
                        <label>Colunas</label>
                        <select name="input_cols">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Obrigatorio</label>
                        <select name="input_req">
                            <option value="1">Sim</option>
                            <option value="0">Nao</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nome do arquivo</label>
                        <select name="nomepet">
                            <option value="N">Nao</option>
                            <option value="Y">Sim</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Classe CSS</label>
                        <input name="add_class">
                    </div>
                    <div class="form-group full">
                        <label>Texto padrao</label>
                        <textarea name="texto_padrao"></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Opcoes do select</label>
                        <textarea name="opcoes" placeholder="Uma linha por opcao. Use Nome|Retorno"></textarea>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <button type="submit">Adicionar campo</button>
                </div>
            </form>

            <div class="stack">
                @foreach($modelo->campos as $campo)
                    <form method="post" action="{{ route('admin.modelos.campos.update', [$modelo, $campo]) }}" class="panel" style="padding:16px;">
                        @csrf
                        @method('put')
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Titulo</label>
                                <input name="input_title" value="{{ $campo->input_title }}" required>
                            </div>
                            <div class="form-group">
                                <label>Tipo</label>
                                <select name="input_tipo">
                                    @foreach(['TEXT', 'TEXTAREA', 'SELECT', 'HIDDEN', 'TITLE'] as $tipoCampo)
                                        <option value="{{ $tipoCampo }}" @if($campo->input_tipo === $tipoCampo) selected @endif>{{ $tipoCampo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Prefixo</label>
                                <input name="input_pre" value="{{ $campo->input_pre }}">
                            </div>
                            <div class="form-group">
                                <label>Sufixo</label>
                                <input name="input_pos" value="{{ $campo->input_pos }}">
                            </div>
                            <div class="form-group">
                                <label>Base externa</label>
                                <input name="input_db" value="{{ $campo->input_db }}">
                            </div>
                            <div class="form-group">
                                <label>Coluna/valor</label>
                                <input name="input_val" value="{{ $campo->input_val }}">
                            </div>
                            <div class="form-group">
                                <label>Colunas</label>
                                <select name="input_cols">
                                    @foreach([1, 2, 3] as $cols)
                                        <option value="{{ $cols }}" @if((int) $campo->input_cols === $cols) selected @endif>{{ $cols }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Obrigatorio</label>
                                <select name="input_req">
                                    <option value="1" @if((int) $campo->input_req === 1) selected @endif>Sim</option>
                                    <option value="0" @if((int) $campo->input_req === 0) selected @endif>Nao</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nome do arquivo</label>
                                <select name="nomepet">
                                    <option value="N" @if($campo->nomepet === 'N') selected @endif>Nao</option>
                                    <option value="Y" @if($campo->nomepet === 'Y') selected @endif>Sim</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ordem</label>
                                <input name="input_order" type="number" min="1" value="{{ $campo->input_order }}">
                            </div>
                            <div class="form-group full">
                                <label>Texto padrao</label>
                                <textarea name="texto_padrao">{{ $campo->texto_padrao }}</textarea>
                            </div>
                            <div class="form-group full">
                                <label>Opcoes do select</label>
                                <textarea name="opcoes">@foreach($campo->dados as $dado){{ $dado->nome_dados }}|{{ $dado->return_1 }}
@endforeach</textarea>
                            </div>
                        </div>
                        <div style="margin-top:12px;">
                            <button type="submit">Salvar campo</button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
