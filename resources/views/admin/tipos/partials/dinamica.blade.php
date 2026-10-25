{{--
    Painel "Dinamica do campo" (criar e editar campo).
    Variaveis: $modelo, $campo (null ao criar), $dinamica (config reconhecida ou null).
--}}
@php
    $dinamica = $dinamica ?? null;
    $tipoAtual = $dinamica['tipo'] ?? '';
    $selfId = $campo ? (int) $campo->id_input : 0;
    $outrosCampos = $modelo->campos->filter(function ($item) use ($selfId) {
        return (int) $item->id_input !== $selfId && $item->input_tipo !== 'TITLE';
    });
    $sel = function ($id, $ids) { return in_array((int) $id, array_map('intval', (array) $ids), true) ? 'selected' : ''; };
    $preenche = array_values($dinamica['preenche'] ?? []);
    $alvos = $dinamica['alvos'] ?? [];
@endphp
<div class="form-group full panel-muted js-dinamica-panel">
    <strong>Dinâmica do campo</strong>
    <div class="editor-note" style="margin:6px 0 12px;">
        Como este campo age sobre os outros na montagem. Com uma dinâmica escolhida, os scripts
        "Ao Entrar / Ao Carregar / Ao Sair" são gerados ao salvar.
    </div>
    <input type="hidden" name="dinamica_tipo_original" value="{{ $tipoAtual }}">
    <input type="hidden" name="dinamica_eventos" value="{{ implode(',', $dinamica['eventos'] ?? []) }}">
    <input type="hidden" name="dinamica_seletor" value="{{ $dinamica['seletor'] ?? '' }}">
    <div class="form-grid">
        <div class="form-group full">
            <label>Dinâmica</label>
            <select name="dinamica_tipo" class="js-dinamica-select">
                @foreach(\App\Support\CampoDinamica::TYPES as $valor => $rotulo)
                    <option value="{{ $valor }}" @if($tipoAtual === $valor) selected @endif>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="retorno_opcao">
            <div class="editor-note">Cada opção deste campo tem retornos (coluna 2 em diante das opções: <code>Rótulo|Retorno 1|Retorno 2…</code>). Escolha qual retorno vai para qual campo. Ex.: SIM → parágrafo do segredo de justiça.</div>
            @for($i = 0; $i < 3; $i++)
                <div class="form-grid" style="margin-top:6px;">
                    <div class="form-group">
                        <label>Campo que recebe</label>
                        <select name="dinamica_alvos[]">
                            <option value="">—</option>
                            @foreach($outrosCampos as $item)
                                <option value="{{ $item->id_input }}" @if((int) ($preenche[$i]['alvo'] ?? 0) === (int) $item->id_input) selected @endif>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Retorno</label>
                        <select name="dinamica_colunas[]">
                            @foreach(\App\Support\CampoDinamica::RETURN_COLUMNS as $n => $coluna)
                                <option value="{{ $coluna }}" @if(($preenche[$i]['coluna'] ?? 'return_1') === $coluna) selected @endif>Retorno {{ $n + 1 }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endfor
            <label style="display:flex;gap:8px;align-items:center;font-weight:normal;margin-top:8px;">
                <input type="checkbox" name="dinamica_mostrar_alvos" value="1" @if(!empty($dinamica['mostrar_alvos'])) checked @endif style="width:auto;">
                Esconder os campos que recebem quando a resposta for NÃO
            </label>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="mostrar_sim_nao">
            <div class="editor-note">Com SIM, os campos aparecem e ficam obrigatórios; com NÃO, somem e são esvaziados. Deixe-os "Visível inicialmente = Não".</div>
            <label>Campos</label>
            <select name="dinamica_campos[]" multiple size="6">
                @foreach($outrosCampos as $item)
                    <option value="{{ $item->id_input }}" {{ $sel($item->id_input, $alvos) }}>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="mostrar_por_valor">
            <div class="form-grid">
                <div class="form-group">
                    <label>Quando o valor for</label>
                    <input name="dinamica_valor" value="{{ $dinamica['valor'] ?? '' }}" placeholder="Ex.: PESQUISA">
                </div>
                <div class="form-group">
                    <label style="display:flex;gap:8px;align-items:center;font-weight:normal;margin-top:24px;">
                        <input type="checkbox" name="dinamica_obrigatorio" value="1" @if(!empty($dinamica['obrigatorio'])) checked @endif style="width:auto;">
                        Campos exibidos ficam obrigatórios
                    </label>
                </div>
            </div>
            <label>Campos exibidos</label>
            <select name="dinamica_campos[]" multiple size="6">
                @foreach($outrosCampos as $item)
                    <option value="{{ $item->id_input }}" {{ $sel($item->id_input, $alvos) }}>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="quantidade">
            <div class="editor-note">Este campo traz a quantidade (ex.: 01 a 12). Escolha os campos de cada item, na ordem do formulário; só os da quantidade escolhida aparecem, os outros somem e ficam vazios (na peça, a linha da tabela também some).</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Campos por item</label>
                    <select name="dinamica_por_item">
                        <option value="1" @if((int) ($dinamica['por_item'] ?? 1) === 1) selected @endif>1 (ex.: bens)</option>
                        <option value="2" @if((int) ($dinamica['por_item'] ?? 1) === 2) selected @endif>2 (ex.: parcelas: vencimento e valor)</option>
                    </select>
                </div>
            </div>
            <label>Campos dos itens</label>
            <select name="dinamica_campos[]" multiple size="8">
                @foreach($outrosCampos as $item)
                    <option value="{{ $item->id_input }}" {{ $sel($item->id_input, $alvos) }}>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="copiar">
            <div class="editor-note">Ao sair deste campo, o valor é copiado para os campos escolhidos (também para campos ocultos).</div>
            <label>Copiar para</label>
            <select name="dinamica_campos[]" multiple size="6">
                @foreach($modelo->campos->filter(function ($item) use ($selfId) { return (int) $item->id_input !== $selfId; }) as $item)
                    <option value="{{ $item->id_input }}" {{ $sel($item->id_input, $alvos) }}>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="proxima_parcela">
            <div class="form-grid">
                <div class="form-group">
                    <label>Preencher</label>
                    <select name="dinamica_modo">
                        <option value="data" @if(($dinamica['modo'] ?? '') === 'data') selected @endif>A data + 1 mês (ao sair)</option>
                        <option value="valor" @if(($dinamica['modo'] ?? '') === 'valor') selected @endif>O mesmo valor (ao entrar)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No campo</label>
                    <select name="dinamica_alvo">
                        <option value="">—</option>
                        @foreach($outrosCampos as $item)
                            <option value="{{ $item->id_input }}" @if((int) ($dinamica['alvo'] ?? 0) === (int) $item->id_input) selected @endif>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="editor-note">Só preenche se o campo estiver visível (parcela dentro da quantidade escolhida).</div>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="tipo_pessoa">
            <div class="editor-note">Este campo deve ter as opções FÍSICA e JURÍDICA. A escolha troca o rótulo e a máscara do campo do documento entre CPF e CNPJ.</div>
            <label>Campo do documento</label>
            <select name="dinamica_alvo">
                <option value="">—</option>
                @foreach($outrosCampos as $item)
                    <option value="{{ $item->id_input }}" @if((int) ($dinamica['alvo'] ?? 0) === (int) $item->id_input) selected @endif>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="qualificacao">
            <div class="editor-note">Este campo recebe "pessoa física, inscrita no CPF sob o nº …" ou "pessoa jurídica de direito privado, inscrita no CNPJ sob o nº …". Sem PESSOA, decide pelo documento (11 ou 14 dígitos). Texto digitado pelo usuário não é sobrescrito.</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Campo PESSOA (física/jurídica)</label>
                    <select name="dinamica_pessoa">
                        <option value="">—</option>
                        @foreach($outrosCampos as $item)
                            <option value="{{ $item->id_input }}" @if((int) ($dinamica['pessoa'] ?? 0) === (int) $item->id_input) selected @endif>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Campo CPF/CNPJ</label>
                    <select name="dinamica_documento">
                        <option value="">—</option>
                        @foreach($outrosCampos as $item)
                            <option value="{{ $item->id_input }}" @if((int) ($dinamica['documento'] ?? 0) === (int) $item->id_input) selected @endif>{{ $item->input_title }} (#{{ $item->id_input }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group full js-dinamica-section" data-dinamica="nao_ajuizar">
            <div class="form-grid">
                <div class="form-group">
                    <label>Bloqueia quando a resposta for</label>
                    <select name="dinamica_modo">
                        <option value="nao" @if(($dinamica['modo'] ?? 'nao') === 'nao') selected @endif>NÃO</option>
                        <option value="excecao" @if(($dinamica['modo'] ?? '') === 'excecao') selected @endif>AUSENTE, MUDOU-SE ou DESCONHECIDO</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Observação (opcional)</label>
                    <input name="dinamica_argumento" value="{{ $dinamica['argumento'] ?? '' }}" placeholder="Ex.: RECEBIDA">
                </div>
            </div>
            <div class="editor-note">A montagem mostra o aviso "Esta petição não deve ser ajuizada" e bloqueia a prévia enquanto a resposta impedir.</div>
        </div>
    </div>
</div>
