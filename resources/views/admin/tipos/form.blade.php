@extends('layouts.app')

@section('title', $modelo->exists ? 'Editar modelo' : 'Novo modelo')

@push('head')
@php
    $legacyAppUrl = rtrim((string) config('legacy.app_url'), '/');
@endphp
@if($legacyAppUrl !== '')
    <script src="{{ $legacyAppUrl }}/ckeditor/ckeditor.js"></script>
    <script src="{{ $legacyAppUrl }}/ckfinder/ckfinder.js"></script>
@endif
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $modelo->exists ? 'Editar modelo' : 'Novo modelo' }}</h2>
    <div class="actions">
        @php
            $montagemRoute = null;
            $saveRoute = null;
            $storeParagrafoRoute = null;
            $storeCampoRoute = null;
            if ($modelo->exists) {
                $montagemRoute = route('peticoes.normalized.show', $modelo);
                $saveRoute = route('admin.modelos-normalizados.update', $modelo);
                $storeParagrafoRoute = route('admin.modelos-normalizados.paragrafos.store', $modelo);
                $storeCampoRoute = route('admin.modelos-normalizados.campos.store', $modelo);
            }
        @endphp
        @if($modelo->exists)
            <a class="button link" href="{{ $montagemRoute }}">Abrir montagem</a>
        @endif
        <a class="button secondary link" href="{{ route('admin.modelos-normalizados.index') }}">Voltar</a>
    </div>
</div>

<div class="stack">
    <div class="panel">
        <div class="section-title">
            <h3>Configuracao do modelo</h3>
            <div class="editor-note">Setor, cliente, servidor externo, cabecalho e rodape.</div>
        </div>
        <form method="post" action="{{ $modelo->exists ? $saveRoute : route('admin.modelos-normalizados.store') }}">
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
                    <textarea class="js-rich-editor" name="cod_cabec">{{ old('cod_cabec', $modelo->cod_cabec) }}</textarea>
                </div>
                <div class="form-group full">
                    <label>Rodape</label>
                    <textarea class="js-rich-editor" name="cod_rodap">{{ old('cod_rodap', $modelo->cod_rodap) }}</textarea>
                </div>
            </div>
            <div style="margin-top:20px;">
                <button type="submit">Salvar modelo</button>
            </div>
        </form>
    </div>

    @if($modelo->exists && $mirror)
        <div class="panel">
            <div class="section-title">
                <h3>Leitura normalizada</h3>
                <div class="editor-note">Fonte principal atual da edicao.</div>
            </div>
            <div class="grid">
                <div class="stat">
                    <span>Modelo normalizado</span>
                    <strong>#{{ $mirror->id }}</strong>
                </div>
                <div class="stat">
                    <span>Slug</span>
                    <strong style="font-size:18px;">{{ $mirror->slug }}</strong>
                </div>
                <div class="stat">
                    <span>Paragrafos</span>
                    <strong>{{ $mirror->paragrafos->count() }}</strong>
                </div>
                <div class="stat">
                    <span>Campos</span>
                    <strong>{{ $mirror->campos->count() }}</strong>
                </div>
            </div>
        </div>
    @endif

    @if($modelo->exists)
        <div class="panel">
            <div class="section-title">
                <h3>Paragrafos</h3>
                <div class="editor-note">{{ $modelo->paragrafos->count() }} item(ns) neste modelo.</div>
            </div>

            <div class="panel-muted" style="margin-bottom:20px;">
                <form method="post" action="{{ $storeParagrafoRoute }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Titulo</label>
                            <input name="fund_titulo" required>
                        </div>
                        <div class="form-group full">
                            <label>Texto</label>
                            <textarea class="js-rich-editor" name="fund_text"></textarea>
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <button type="submit">Adicionar paragrafo</button>
                    </div>
                </form>
            </div>

            <div class="stack">
                @foreach($modelo->paragrafos as $paragrafo)
                    <details class="accordion-item" open>
                        <summary>{{ $paragrafo->fund_order }}. {{ $paragrafo->fund_titulo }}</summary>
                        <div class="accordion-body">
                            @php
                                $updateParagrafoRoute = route('admin.modelos-normalizados.paragrafos.update', [$modelo, $paragrafo]);
                            @endphp
                            <form method="post" action="{{ $updateParagrafoRoute }}">
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
                                        <textarea class="js-rich-editor" name="fund_text">{{ $paragrafo->fund_text }}</textarea>
                                    </div>
                                </div>
                                <div style="margin-top:12px;">
                                    <button type="submit">Salvar paragrafo</button>
                                </div>
                            </form>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div class="section-title">
                <h3>Campos dinamicos</h3>
                <div class="editor-note">{{ $modelo->campos->count() }} campo(s) vinculados a este modelo.</div>
            </div>

            <div class="panel-muted" style="margin-bottom:20px;">
                <form method="post" action="{{ $storeCampoRoute }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Titulo</label>
                            <input name="input_title" required>
                            <div class="editor-note">O token gerado sera criado automaticamente apos salvar.</div>
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
                            <div class="editor-note">Formato: `Rotulo|Retorno`. O rotulo aparece para o usuario; o retorno entra na montagem.</div>
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <button type="submit">Adicionar campo</button>
                    </div>
                </form>
            </div>

            <div class="stack">
                @foreach($modelo->campos as $campo)
                    <details class="accordion-item">
                        <summary>{{ $campo->input_order }}. {{ $campo->input_title }} ({{ $campo->input_tipo }})</summary>
                        <div class="accordion-body">
                            <div class="panel-muted" style="margin-bottom:16px;">
                                <strong>Token:</strong> {{ $campo->placeholder }}
                                @if($campo->input_tipo === 'SELECT')
                                    <div class="editor-note" style="margin-top:6px;">As opcoes seguem o formato `Rotulo|Retorno`.</div>
                                @endif
                            </div>
                            @php
                                $updateCampoRoute = route('admin.modelos-normalizados.campos.update', [$modelo, $campo]);
                            @endphp
                            <form method="post" action="{{ $updateCampoRoute }}">
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
                                        <div class="editor-note">Uma linha por opcao. Ex.: `Sim|SIM`.</div>
                                    </div>
                                </div>
                                <div style="margin-top:12px;">
                                    <button type="submit">Salvar campo</button>
                                </div>
                            </form>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.CKEDITOR === 'undefined') {
        return;
    }

    var ckfinderBaseUrl = @json(config('legacy.ckfinder_base_url'));
    var textareas = document.querySelectorAll('.js-rich-editor');

    Array.prototype.forEach.call(textareas, function (textarea, index) {
        if (!textarea.id) {
            textarea.id = 'editor_' + index + '_' + Math.random().toString(36).slice(2, 8);
        }

        if (CKEDITOR.instances[textarea.id]) {
            CKEDITOR.instances[textarea.id].destroy(true);
        }

        var instance = CKEDITOR.replace(textarea.id, {
            height: 240,
            allowedContent: true
        });

        if (window.CKFinder && ckfinderBaseUrl) {
            CKFinder.setupCKEditor(instance, ckfinderBaseUrl);
        }
    });
});
</script>
@endpush
