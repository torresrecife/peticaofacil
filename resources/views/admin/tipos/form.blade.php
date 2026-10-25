@extends('layouts.app')

@section('title', $modelo->exists ? 'Editar modelo' : 'Novo modelo')

@push('head')
<script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
<script src="{{ asset('ckfinder/ckfinder.js') }}"></script>
<style>
    .field-behavior-note {
        margin-top: 6px;
        font-size: 12px;
        color: #7b8794;
    }
    .field-behavior-note.is-active {
        color: #8d5c00;
        font-weight: 600;
    }
    /* Presets are legacy implementation details; the editable event fields are
       the only event controls exposed in the simplified configuration screen. */
    .js-date-preset-row {
        display: none !important;
    }
</style>
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
                            <option value="{{ $cliente->cliente_id }}" @if((string) old('id_cliente', $modelo->id_cliente) === (string) $cliente->cliente_id) selected @endif>{{ $cliente->cliente_name }}@if($cliente->cliente_status !== 'Y') (inativo)@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Servidor SQL</label>
                    <select name="id_db">
                        <option value="">Nenhum</option>
                        @foreach($servidores as $servidor)
                            <option value="{{ $servidor->id_db }}" @if((string) old('id_db', $modelo->id_db) === (string) $servidor->id_db) selected @endif>{{ $servidor->nome_db }}@if($servidor->stt !== 'Y') (inativo)@endif</option>
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

    @if(!$modelo->exists)
        <div class="panel">
            <div class="section-title"><h3>Criar com IA a partir de Word</h3></div>
            <p class="editor-note">Envie um modelo .doc ou .docx. A IA identificará os campos e parágrafos, mas você revisará antes de gravar.</p>
            <form method="post" enctype="multipart/form-data" action="{{ route('admin.modelos-normalizados.analyze-word') }}">
                @csrf
                <input type="file" name="word_file" accept=".doc,.docx" required>
                <button type="submit">Analisar arquivo com IA</button>
            </form>
        </div>
    @endif

    @if($modelo->exists)
        <div class="panel">
            <div class="section-title">
                <h3>Paragrafos</h3>
                <div class="editor-note">{{ $modelo->paragrafos->count() }} item(ns) neste modelo.</div>
            </div>

            @if($modelo->campos->count())
                <div class="panel-muted js-token-helper" style="margin-bottom:16px;">
                    <strong>Inserir marcador de campo no texto</strong>
                    <div class="editor-note" style="margin:6px 0 10px;">
                        Clique no texto (parágrafo, cabeçalho ou rodapé) onde o valor deve entrar e escolha o campo e a forma.
                        A forma do marcador decide como o valor sai na peça: <code>@CAMPO1@</code> MAIÚSCULAS,
                        <code>@Campo1@</code> Iniciais Maiúsculas, <code>@campo1@</code> como digitado.
                        <code>@campo1_pre@</code>/<code>@campo1_pos@</code> trazem o prefixo/sufixo do campo e só aparecem quando ele tem valor (ex.: linhas da tabela de parcelas).
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <select class="js-token-field" style="max-width:360px;">
                            @foreach($modelo->campos as $campoExistente)
                                @if($campoExistente->input_tipo !== 'TITLE')
                                    <option value="{{ $campoExistente->id_input }}">{{ $campoExistente->input_title }} (#{{ $campoExistente->id_input }})</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" class="secondary js-token-insert" data-form="CAMPO">MAIÚSCULAS</button>
                        <button type="button" class="secondary js-token-insert" data-form="Campo">Iniciais</button>
                        <button type="button" class="secondary js-token-insert" data-form="campo">Como digitado</button>
                        <button type="button" class="secondary js-token-insert" data-form="campo" data-part="_pre">Prefixo</button>
                        <button type="button" class="secondary js-token-insert" data-form="campo" data-part="_pos">Sufixo</button>
                        <span class="editor-note js-token-status"></span>
                    </div>
                </div>
            @endif

            <details class="accordion-item" style="margin-bottom:20px;">
                <summary>Adicionar novo paragrafo</summary>
                <div class="accordion-body">
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
            </details>

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
                <h3>Textos predefinidos</h3>
                <div class="editor-note">{{ count($textTemplates) }} texto(s) disponível(is) para campos Textarea durante a montagem.</div>
            </div>

            <details class="accordion-item" style="margin-bottom:20px;">
                <summary>Adicionar texto predefinido</summary>
                <div class="accordion-body">
                    <form method="post" action="{{ route('admin.modelos-normalizados.textos-predefinidos.store', $modelo) }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Título</label>
                                <input name="template_title" required>
                            </div>
                            <div class="form-group full">
                                <label>Conteúdo</label>
                                <textarea class="js-rich-editor" name="template_content"></textarea>
                            </div>
                        </div>
                        <div style="margin-top:12px;"><button type="submit">Adicionar texto</button></div>
                    </form>
                </div>
            </details>

            <div class="stack">
                @foreach($textTemplates as $template)
                    @if(!empty($template['id']))
                        <details class="accordion-item">
                            <summary>{{ $template['title'] ?? 'Texto sem título' }}{{ isset($template['active']) && !$template['active'] ? ' (inativo)' : '' }}</summary>
                            <div class="accordion-body">
                                <form method="post" action="{{ route('admin.modelos-normalizados.textos-predefinidos.update', [$modelo, $template['id']]) }}">
                                    @csrf
                                    @method('put')
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Título</label>
                                            <input name="template_title" value="{{ $template['title'] ?? '' }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Disponível na montagem</label>
                                            <select name="template_active">
                                                <option value="1"{{ !isset($template['active']) || $template['active'] ? ' selected' : '' }}>Sim</option>
                                                <option value="0"{{ isset($template['active']) && !$template['active'] ? ' selected' : '' }}>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group full">
                                            <label>Conteúdo</label>
                                            <textarea class="js-rich-editor" name="template_content">{{ $template['content'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <div style="margin-top:12px;"><button type="submit">Salvar texto</button></div>
                                </form>
                            </div>
                        </details>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div class="section-title">
                <h3>Campos dinamicos</h3>
                <div class="editor-note">{{ $modelo->campos->count() }} campo(s) vinculados a este modelo.</div>
            </div>

            <details class="accordion-item" style="margin-bottom:20px;">
                <summary>Adicionar novo campo dinâmico</summary>
                <div class="accordion-body">
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
                            <select name="input_tipo" class="js-field-type-select">
                                <option value="TEXT">Texto</option>
                                <option value="SELECT">Opções</option>
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
                            <div class="editor-note">Use para mapeamento externo customizado. Para listas preexistentes, use o bloco abaixo.</div>
                        </div>
                        <div class="form-group">
                            <label>Coluna/valor</label>
                            <select name="input_val">
                                <option value="">Nenhuma</option>
                                @foreach(($neoColumns ?? []) as $neoColumn)
                                    <option value="{{ $neoColumn }}">{{ $neoColumn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Associar com Base existente</label>
                            <select name="input_list_group_id">
                                <option value="">Nenhuma</option>
                                @foreach($listaGrupos as $grupo)
                                    <option value="{{ $grupo->id_grupo }}">{{ $grupo->nome_grupo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Retorno da lista</label>
                            <select name="input_list_return_column[]" multiple size="3">
                                @foreach(['return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'] as $returnColumn)
                                    <option value="{{ $returnColumn }}">{{ strtoupper($returnColumn) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Campo alvo do retorno</label>
                            <select name="input_list_target_field">
                                <option value="">Nenhum</option>
                                @foreach($modelo->campos as $campoExistente)
                                    <option value="{{ $campoExistente->id_input }}">{{ $campoExistente->input_title }} (#{{ $campoExistente->id_input }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Colunas</label>
                            <select name="input_cols">
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Próxima linha</label>
                            <select name="input_rols">
                                <option value="0">Não</option>
                                <option value="1">Sim</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Padrao</label>
                            <select name="input_behavior" class="js-field-behavior-select">
                                <option value="">Padrão</option>
                                <option value="date">Data</option>
                                <option value="decimal">Valor</option>
                                <option value="cpf">Cpf</option>
                                <option value="cnpj">Cnpj</option>
                                <option value="cpf_cnpj">CPF/CNPJ</option>
                                <option value="fone">Fone</option>
                                <option value="cep">CEP</option>
                                <option value="integer">Número</option>
                                <option value="processo">Processo CNJ</option>
                            </select>
                            <div class="field-behavior-note js-date-behavior-note" style="display:none;">Os presets de data ficam disponiveis apenas para campos Texto e Textarea quando o padrao Data estiver ativo.</div>
                        </div>
                        <div class="form-group">
                            <label>Obrigatorio</label>
                            <select name="input_req">
                                <option value="1">Sim</option>
                                <option value="0">Nao</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Visível inicialmente</label>
                            <select name="hide">
                                <option value="true">Sim</option>
                                <option value="none">Não</option>
                            </select>
                            <div class="editor-note">Use “Não” quando uma regra condicional deve exibir este campo.</div>
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
                        <div class="form-group full panel-muted">
                            <strong>Regra condicional</strong>
                            <div class="editor-note" style="margin:6px 0 12px;">Exibe outros campos quando este campo tiver o valor informado. Exemplo: valor “SIM” exibe “Fundamentação mudou-se”.</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Exibir campos quando o valor for</label>
                                    <input name="conditional_value" placeholder="Ex.: SIM">
                                </div>
                                <div class="form-group">
                                    <label>Tornar campos exibidos obrigatórios</label>
                                    <select name="conditional_required">
                                        <option value="0">Não</option>
                                        <option value="1">Sim</option>
                                    </select>
                                </div>
                                <div class="form-group full">
                                    <label>Campos exibidos</label>
                                    <select name="conditional_targets[]" multiple size="6">
                                        @foreach($modelo->campos as $campoExistente)
                                            <option value="{{ $campoExistente->id_input }}">{{ $campoExistente->input_title }} (#{{ $campoExistente->id_input }})</option>
                                        @endforeach
                                    </select>
                                    <div class="editor-note">Use Ctrl para selecionar mais de um campo. Configure os campos de destino como não visíveis inicialmente.</div>
                                </div>
                            </div>
                        </div>
                        @include('admin.tipos.partials.dinamica', ['campo' => null, 'dinamica' => null])
                        <div class="form-group full js-date-preset-row" data-preset-row="focus" style="display:none;">
                            <label>Preset Ao Entrar</label>
                            <select name="input_focu_preset">
                                <option value="">Nenhum</option>
                                <option value="data_extenso_out">Data por extenso</option>
                                <option value="data_atual">Data atual</option>
                                <option value="dia_semana">Dia da semana</option>
                                <option value="valor_extenso">Valor por extenso</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Ao Entrar</label>
                            <textarea name="input_focu"></textarea>
                        </div>
                        <div class="form-group full js-date-preset-row" data-preset-row="load" style="display:none;">
                            <label>Preset Ao Carregar</label>
                            <select name="input_load_preset">
                                <option value="">Nenhum</option>
                                <option value="data_extenso_out">Data por extenso</option>
                                <option value="data_atual">Data atual</option>
                                <option value="dia_semana">Dia da semana</option>
                                <option value="valor_extenso">Valor por extenso</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Ao Carregar</label>
                            <textarea name="input_load"></textarea>
                        </div>
                        <div class="form-group full js-date-preset-row" data-preset-row="blur" style="display:none;">
                            <label>Preset Ao Sair</label>
                            <select name="input_blur_preset">
                                <option value="">Nenhum</option>
                                <option value="data_extenso_out">Data por extenso</option>
                                <option value="data_atual">Data atual</option>
                                <option value="dia_semana">Dia da semana</option>
                                <option value="valor_extenso">Valor por extenso</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Ao Sair</label>
                            <textarea name="input_blur"></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Texto padrao</label>
                            <textarea name="texto_padrao"></textarea>
                        </div>
                        <div class="form-group full js-options-config">
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
            </details>

            <div class="stack">
                @foreach($modelo->campos as $campo)
                    <details class="accordion-item">
                        @php
                            $resumoDinamica = \App\Support\CampoDinamica::summary(\App\Support\CampoDinamica::detect($campo->eventos_frontend ?: [], $campo->id_input));
                        @endphp
                        <summary>{{ $campo->input_order }}. {{ $campo->input_title }} ({{ $campo->input_tipo }})@if($resumoDinamica) <span class="editor-note" style="display:inline;margin-left:8px;">· {{ $resumoDinamica }}</span>@endif</summary>
                        <div class="accordion-body">
                            <div class="panel-muted" style="margin-bottom:16px;">
                                <strong>Token:</strong> {{ $campo->placeholder }}
                                @if($campo->input_tipo === 'SELECT')
                                    <div class="editor-note" style="margin-top:6px;">As opcoes seguem o formato `Rotulo|Retorno`.</div>
                                @endif
                                @if($campo->associated_list_config)
                                    <div class="editor-note" style="margin-top:6px;">Lista associada: {{ optional($listaGrupos->firstWhere('id_grupo', $campo->associated_list_config['group_id']))->nome_grupo ?: ('Grupo #' . $campo->associated_list_config['group_id']) }}.</div>
                                @endif
                            </div>
                            @php
                                $updateCampoRoute = route('admin.modelos-normalizados.campos.update', [$modelo, $campo]);
                                $listConfig = $campo->associated_list_config;
                                $dependentConfig = $campo->dependent_fill_config;
                                $conditionalConfig = $campo->conditional_config ?: [];
                                $conditionalTargetIds = array_map('intval', $conditionalConfig['target_ids'] ?? []);
                                $campoDinamica = \App\Support\CampoDinamica::detect($campo->eventos_frontend ?: [], $campo->id_input);
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
                                        <select name="input_tipo" class="js-field-type-select">
                                            @foreach(['TEXT', 'TEXTAREA', 'SELECT', 'RADIO2', 'HIDDEN', 'TITLE'] as $tipoCampo)
                                                <option value="{{ $tipoCampo }}" @if($campo->input_tipo === $tipoCampo) selected @endif>{{ $tipoCampo === 'TEXT' ? 'Texto' : ($tipoCampo === 'SELECT' ? 'Opções' : ($tipoCampo === 'HIDDEN' ? 'Oculto' : ($tipoCampo === 'TITLE' ? 'Título' : $tipoCampo))) }}</option>
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
                                        <div class="editor-note">Use para mapeamento externo customizado. Para listas preexistentes, use o bloco abaixo.</div>
                                    </div>
                                    <div class="form-group">
                                        <label>Coluna/valor</label>
                                        @php($currentNeoColumn = trim((string) $campo->input_val))
                                        <select name="input_val">
                                            <option value="">Nenhuma</option>
                                            @foreach(($neoColumns ?? []) as $neoColumn)
                                                <option value="{{ $neoColumn }}" @if($currentNeoColumn === $neoColumn) selected @endif>{{ $neoColumn }}</option>
                                            @endforeach
                                            @if($currentNeoColumn !== '' && !in_array($currentNeoColumn, ($neoColumns ?? []), true))
                                                <option value="{{ $currentNeoColumn }}" selected>{{ $currentNeoColumn }} (personalizada)</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Associar com Base existente</label>
                                        <select name="input_list_group_id">
                                            <option value="">Nenhuma</option>
                                            @foreach($listaGrupos as $grupo)
                                                <option value="{{ $grupo->id_grupo }}" @if((int) ($listConfig['group_id'] ?? 0) === (int) $grupo->id_grupo) selected @endif>{{ $grupo->nome_grupo }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Retorno da lista</label>
                                        @php($selectedReturnColumns = $listConfig['return_columns'] ?? (isset($listConfig['return_column']) ? preg_split('/\s*[+,;|]\s*/', $listConfig['return_column']) : []))
                                        <select name="input_list_return_column[]" multiple size="3">
                                            @foreach(['return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'] as $returnColumn)
                                                <option value="{{ $returnColumn }}" @if(in_array($returnColumn, $selectedReturnColumns, true)) selected @endif>{{ strtoupper($returnColumn) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Campo alvo do retorno</label>
                                        <select name="input_list_target_field">
                                            <option value="">Nenhum</option>
                                            @foreach($modelo->campos as $campoExistente)
                                                @if((int) $campoExistente->id_input !== (int) $campo->id_input)
                                                    <option value="{{ $campoExistente->id_input }}" @if((int) ($dependentConfig['target_field_id'] ?? 0) === (int) $campoExistente->id_input) selected @endif>{{ $campoExistente->input_title }} (#{{ $campoExistente->id_input }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Colunas</label>
                                        <select name="input_cols">
                                            @foreach([1, 2] as $cols)
                                                <option value="{{ $cols }}" @if((int) $campo->input_cols === $cols) selected @endif>{{ $cols }}</option>
                                            @endforeach
                                            @if((int) $campo->input_cols === 3)<option value="3" selected>3 (legado)</option>@endif
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Próxima linha</label>
                                        <select name="input_rols">
                                            <option value="0" @if((int) $campo->input_rols === 0) selected @endif>Não</option>
                                            <option value="1" @if((int) $campo->input_rols === 1) selected @endif>Sim</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Padrao</label>
                                        <select name="input_behavior" class="js-field-behavior-select">
                                            <option value="" @if($campo->input_behavior === '') selected @endif>Padrão</option>
                                            <option value="date" @if($campo->input_behavior === 'date') selected @endif>Data</option>
                                            <option value="decimal" @if($campo->input_behavior === 'decimal') selected @endif>Valor</option>
                                            <option value="cpf" @if($campo->input_behavior === 'cpf') selected @endif>Cpf</option>
                                            <option value="cnpj" @if($campo->input_behavior === 'cnpj') selected @endif>Cnpj</option>
                                            <option value="cpf_cnpj" @if($campo->input_behavior === 'cpf_cnpj') selected @endif>CPF/CNPJ</option>
                                            <option value="fone" @if($campo->input_behavior === 'fone') selected @endif>Fone</option>
                                            <option value="cep" @if($campo->input_behavior === 'cep') selected @endif>CEP</option>
                                            <option value="integer" @if($campo->input_behavior === 'integer') selected @endif>Número</option>
                                            <option value="processo" @if($campo->input_behavior === 'processo') selected @endif>Processo CNJ</option>
                                        </select>
                                        <div class="field-behavior-note js-date-behavior-note" style="display:none;">Os presets de data ficam disponiveis apenas para campos Texto e Textarea quando o padrao Data estiver ativo.</div>
                                    </div>
                                    <div class="form-group">
                                        <label>Obrigatorio</label>
                                        <select name="input_req">
                                            <option value="1" @if((int) $campo->input_req === 1) selected @endif>Sim</option>
                                            <option value="0" @if((int) $campo->input_req === 0) selected @endif>Nao</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Visível inicialmente</label>
                                        <select name="hide">
                                            <option value="true" @if($campo->visivel) selected @endif>Sim</option>
                                            <option value="none" @if(!$campo->visivel) selected @endif>Não</option>
                                        </select>
                                        <div class="editor-note">Use “Não” quando uma regra condicional deve exibir este campo.</div>
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
                                    <div class="form-group full panel-muted">
                                        <strong>Regra condicional</strong>
                                        <div class="editor-note" style="margin:6px 0 12px;">Exibe outros campos quando este campo tiver o valor informado.</div>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Exibir campos quando o valor for</label>
                                                <input name="conditional_value" value="{{ $conditionalConfig['value'] ?? '' }}" placeholder="Ex.: SIM">
                                            </div>
                                            <div class="form-group">
                                                <label>Tornar campos exibidos obrigatórios</label>
                                                <select name="conditional_required">
                                                    <option value="0" @if(empty($conditionalConfig['required'])) selected @endif>Não</option>
                                                    <option value="1" @if(!empty($conditionalConfig['required'])) selected @endif>Sim</option>
                                                </select>
                                            </div>
                                            <div class="form-group full">
                                                <label>Campos exibidos</label>
                                                <select name="conditional_targets[]" multiple size="6">
                                                    @foreach($modelo->campos as $campoExistente)
                                                        @if((int) $campoExistente->id_input !== (int) $campo->id_input)
                                                            <option value="{{ $campoExistente->id_input }}" @if(in_array((int) $campoExistente->id_input, $conditionalTargetIds, true)) selected @endif>{{ $campoExistente->input_title }} (#{{ $campoExistente->id_input }})</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <div class="editor-note">Use Ctrl para selecionar mais de um campo. Configure os campos de destino como não visíveis inicialmente.</div>
                                            </div>
                                        </div>
                                    </div>
                                    @include('admin.tipos.partials.dinamica', ['campo' => $campo, 'dinamica' => $campoDinamica])
                                    <div class="form-group full js-date-preset-row" data-preset-row="focus" style="display:none;">
                                        <label>Preset Ao Entrar</label>
                                        <select name="input_focu_preset">
                                            <option value="" @if($campo->input_focu_preset === '') selected @endif>Nenhum</option>
                                            <option value="data_extenso_out" @if($campo->input_focu_preset === 'data_extenso_out') selected @endif>Data por extenso</option>
                                            <option value="data_atual" @if($campo->input_focu_preset === 'data_atual') selected @endif>Data atual</option>
                                            <option value="dia_semana" @if($campo->input_focu_preset === 'dia_semana') selected @endif>Dia da semana</option>
                                            <option value="valor_extenso" @if($campo->input_focu_preset === 'valor_extenso') selected @endif>Valor por extenso</option>
                                        </select>
                                    </div>
                                    <div class="form-group full">
                                        <label>Ao Entrar</label>
                                        <textarea name="input_focu">{{ $campo->input_focu }}</textarea>
                                    </div>
                                    <div class="form-group full js-date-preset-row" data-preset-row="load" style="display:none;">
                                        <label>Preset Ao Carregar</label>
                                        <select name="input_load_preset">
                                            <option value="" @if($campo->input_load_preset === '') selected @endif>Nenhum</option>
                                            <option value="data_extenso_out" @if($campo->input_load_preset === 'data_extenso_out') selected @endif>Data por extenso</option>
                                            <option value="data_atual" @if($campo->input_load_preset === 'data_atual') selected @endif>Data atual</option>
                                            <option value="dia_semana" @if($campo->input_load_preset === 'dia_semana') selected @endif>Dia da semana</option>
                                            <option value="valor_extenso" @if($campo->input_load_preset === 'valor_extenso') selected @endif>Valor por extenso</option>
                                        </select>
                                    </div>
                                    <div class="form-group full">
                                        <label>Ao Carregar</label>
                                        <textarea name="input_load">{{ $campo->input_load }}</textarea>
                                    </div>
                                    <div class="form-group full js-date-preset-row" data-preset-row="blur" style="display:none;">
                                        <label>Preset Ao Sair</label>
                                        <select name="input_blur_preset">
                                            <option value="" @if($campo->input_blur_preset === '') selected @endif>Nenhum</option>
                                            <option value="data_extenso_out" @if($campo->input_blur_preset === 'data_extenso_out') selected @endif>Data por extenso</option>
                                            <option value="data_atual" @if($campo->input_blur_preset === 'data_atual') selected @endif>Data atual</option>
                                            <option value="dia_semana" @if($campo->input_blur_preset === 'dia_semana') selected @endif>Dia da semana</option>
                                            <option value="valor_extenso" @if($campo->input_blur_preset === 'valor_extenso') selected @endif>Valor por extenso</option>
                                        </select>
                                    </div>
                                    <div class="form-group full">
                                        <label>Ao Sair</label>
                                        <textarea name="input_blur">{{ $campo->input_blur }}</textarea>
                                    </div>
                                    <div class="form-group full">
                                        <label>Texto padrao</label>
                                        <textarea name="texto_padrao">{{ $campo->texto_padrao }}</textarea>
                                    </div>
                                    <div class="form-group full js-options-config">
                                        <label>Opcoes do select</label>
                                        <textarea name="opcoes">@foreach($campo->dados as $dado){{ \App\Support\CampoDinamica::optionLine($dado) }}
@endforeach</textarea>
                                        <div class="editor-note">Uma linha por opcao: `Rótulo|Retorno 1|Retorno 2|…|Retorno 5`. Ex.: `SIM|DO SEGREDO DE JUSTIÇA|&lt;p&gt;O trâmite…&lt;/p&gt;`. Os retornos alimentam a dinâmica "A opção escolhida preenche outros campos".</div>
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
    // Sem CKEditor, so os editores ficam como texto simples; o resto da tela funciona.
    var hasEditor = typeof window.CKEDITOR !== 'undefined';
    var ckfinderBaseUrl = @json(asset('ckfinder/'));
    var textareas = document.querySelectorAll('.js-rich-editor');
    var lastEditor = null;

    Array.prototype.forEach.call(hasEditor ? textareas : [], function (textarea, index) {
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

        // TAB recua somente a primeira linha do paragrafo atual, como no Word.
        // SHIFT+TAB remove o recuo. O foco permanece no CKEditor.
        instance.on('key', function (event) {
            if (event.data.keyCode !== 9) {
                return;
            }

            event.cancel();
            var selection = instance.getSelection();
            var range = selection && selection.getRanges()[0];
            var container = range ? range.startContainer : null;
            var block = container && container.getAscendant(function (element) {
                return element && element.type === CKEDITOR.NODE_ELEMENT && /^(p|div|li|h[1-6])$/i.test(element.getName());
            }, true);
            if (!block) {
                return;
            }

            var domEvent = event.data.domEvent;
            if (domEvent && domEvent.shiftKey) {
                block.removeStyle('text-indent');
            } else {
                block.setStyle('text-indent', '1.1cm');
            }
        });

        instance.on('contentDom', function () {
            var editable = instance.editable();
            editable.attachListener(editable, 'keydown', function (event) {
                var domEvent = event.data.$;
                if (!domEvent || domEvent.keyCode !== 9) {
                    return;
                }

                domEvent.preventDefault();
                domEvent.stopPropagation();
                var selection = instance.getSelection();
                var range = selection && selection.getRanges()[0];
                var container = range ? range.startContainer : null;
                var block = container && container.getAscendant(function (element) {
                    return element && element.type === CKEDITOR.NODE_ELEMENT && /^(p|div|li|h[1-6])$/i.test(element.getName());
                }, true);
                if (!block) {
                    return;
                }
                if (domEvent.shiftKey) {
                    block.removeStyle('text-indent');
                } else {
                    block.setStyle('text-indent', '1.1cm');
                }
            });
        });

        if (window.CKFinder && ckfinderBaseUrl) {
            CKFinder.setupCKEditor(instance, ckfinderBaseUrl);
        }

        instance.on('focus', function () { lastEditor = instance; });
    });

    // Inserir marcador de campo no ultimo editor clicado.
    Array.prototype.forEach.call(document.querySelectorAll('.js-token-insert'), function (button) {
        button.addEventListener('click', function () {
            var helper = button.closest('.js-token-helper');
            var field = helper.querySelector('.js-token-field').value;
            var status = helper.querySelector('.js-token-status');
            var token = '@' + button.getAttribute('data-form') + field + (button.getAttribute('data-part') || '') + '@';
            if (!lastEditor) {
                status.textContent = 'Clique antes no texto onde o marcador deve entrar.';
                return;
            }
            lastEditor.focus();
            lastEditor.insertText(token);
            status.textContent = 'Inserido ' + token + '.';
        });
    });

    function syncFieldBehavior(form) {
        var typeSelect = form.querySelector('.js-field-type-select');
        var behaviorSelect = form.querySelector('.js-field-behavior-select');
        var note = form.querySelector('.js-date-behavior-note');
        var presetRows = form.querySelectorAll('.js-date-preset-row');
        if (!typeSelect || !behaviorSelect || !presetRows.length) {
            return;
        }

        var type = String(typeSelect.value || '').toUpperCase();
        var behavior = String(behaviorSelect.value || '');
        var canUseBehavior = type === 'TEXT' || type === 'TEXTAREA';
        // Data/valor por extenso valem para qualquer campo de texto: no legado ha
        // campos sem mascara com data_extenso_out.
        var showEventPresets = canUseBehavior;
        var focusPreset = form.querySelector('[name="input_focu_preset"]');
        var loadPreset = form.querySelector('[name="input_load_preset"]');
        var blurPreset = form.querySelector('[name="input_blur_preset"]');
        var focusTextarea = form.querySelector('[name="input_focu"]');
        var loadTextarea = form.querySelector('[name="input_load"]');
        var blurTextarea = form.querySelector('[name="input_blur"]');

        function isSupportedDateScript(value) {
            var normalized = String(value || '').replace(/\s+/g, '').toLowerCase();
            return ['data_atual(this);', 'data_extenso_out(this);', 'dia_semana(this);'].indexOf(normalized) !== -1;
        }

        Array.prototype.forEach.call(presetRows, function (row) {
            row.style.display = showEventPresets ? '' : 'none';
            Array.prototype.forEach.call(row.querySelectorAll('select'), function (select) {
                select.disabled = !showEventPresets;
            });
        });

        if (note) {
            note.style.display = canUseBehavior ? '' : 'none';
            note.classList.toggle('is-active', showEventPresets);
        }

        if (!showEventPresets) {
            Array.prototype.forEach.call([focusPreset, loadPreset, blurPreset], function (select) {
                if (select) {
                    select.value = '';
                }
            });
        }

        // Os scripts escritos nos campos "Ao Entrar/Carregar/Sair" nao sao mais
        // apagados ao abrir a tela (antes, salvar o campo perdia o evento).

        if (!canUseBehavior && behavior !== '') {
            behaviorSelect.value = '';
        }
    }

    function syncDynamicFieldConfiguration(form) {
        var typeSelect = form.querySelector('.js-field-type-select');
        if (!typeSelect) return;
        var type = String(typeSelect.value || '').toUpperCase();
        var titleOnly = type === 'TITLE';
        var optionsType = type === 'SELECT' || type === 'RADIO2';
        var fieldGroups = form.querySelectorAll('.form-group');
        Array.prototype.forEach.call(fieldGroups, function (group) {
            var control = group.querySelector('[name]');
            if (!control) return;
            var name = control.getAttribute('name') || '';
            var visible = true;

            // Keep the editor close to the legacy configuration screen. These
            // auxiliary controls remain in the HTML so old values are preserved,
            // but are not shown while the simplified editor is active.
            var legacyAdvanced = ['hide', 'add_class', 'input_list_target_field', 'conditional_value', 'conditional_required', 'conditional_targets[]', 'input_focu_preset', 'input_load_preset', 'input_blur_preset'];
            if (legacyAdvanced.indexOf(name) !== -1) {
                visible = false;
            }

            if (titleOnly && visible) {
                // The type selector itself remains visible so the administrator can
                // switch back to another configuration without reopening the form.
                visible = ['input_tipo', 'input_title', 'input_pre', 'input_pos'].indexOf(name) !== -1;
            } else if (group.classList.contains('js-options-config')) {
                visible = optionsType;
            } else if (name === 'input_behavior' || name.indexOf('input_') === 0 || name === 'hide' || name === 'nomepet' || name === 'add_class' || name === 'texto_padrao' || name === 'opcoes') {
                visible = true;
            }

            group.style.display = visible ? '' : 'none';
        });

        Array.prototype.forEach.call(form.querySelectorAll('.panel-muted'), function (panel) {
            if (panel.querySelector('[name="conditional_value"]')) {
                panel.style.display = 'none';
            }
        });
    }

    // Painel "Dinamica do campo": mostra so a secao da dinamica escolhida e
    // desabilita as outras, para nao enviar campos com o mesmo nome.
    function syncDinamica(form) {
        var select = form.querySelector('.js-dinamica-select');
        if (!select) return;
        Array.prototype.forEach.call(form.querySelectorAll('.js-dinamica-section'), function (section) {
            var active = section.getAttribute('data-dinamica') === select.value;
            section.style.display = active ? '' : 'none';
            Array.prototype.forEach.call(section.querySelectorAll('input, select, textarea'), function (control) {
                control.disabled = !active;
            });
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
        if (!form.querySelector('.js-field-type-select')) {
            return;
        }

        syncFieldBehavior(form);
        syncDynamicFieldConfiguration(form);
        syncDinamica(form);
        var dinamicaSelect = form.querySelector('.js-dinamica-select');
        if (dinamicaSelect) {
            dinamicaSelect.addEventListener('change', function () { syncDinamica(form); });
        }

        var typeSelect = form.querySelector('.js-field-type-select');
        var behaviorSelect = form.querySelector('.js-field-behavior-select');

        typeSelect.addEventListener('change', function () {
            syncFieldBehavior(form);
            syncDynamicFieldConfiguration(form);
            syncDinamica(form);
        });

        if (behaviorSelect) {
            behaviorSelect.addEventListener('change', function () {
                syncFieldBehavior(form);
            });
        }
    });
});
</script>
@endpush
