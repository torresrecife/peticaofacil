@extends('layouts.app')

@section('title', 'Assistente de peticoes')

@push('head')
<style>
    .assistant-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, 420px);
        gap: 16px;
        align-items: start;
    }
    .assistant-chat {
        display: grid;
        gap: 12px;
    }
    .assistant-message {
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        padding: 12px 14px;
        background: #fff;
    }
    .assistant-message--assistant {
        border-left: 4px solid #1f5f8b;
    }
    .assistant-message--user {
        border-left: 4px solid #627d98;
    }
    .assistant-message__meta {
        font-size: 12px;
        color: #627d98;
        margin-bottom: 6px;
    }
    .assistant-panel {
        display: grid;
        gap: 16px;
    }
    .assistant-data-list {
        display: grid;
        gap: 8px;
        margin: 0;
    }
    .assistant-data-list div {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 8px;
        font-size: 13px;
    }
    .assistant-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .assistant-chip-form {
        margin: 0;
    }
    .assistant-chip {
        background: #f0f4f8;
        border: 1px solid #bcccdc;
        border-radius: 999px;
        color: #243b53;
        padding: 8px 12px;
        font-size: 13px;
        cursor: pointer;
    }
    .assistant-warning {
        background: #fff7d6;
        border: 1px solid #f7d070;
        color: #8d5c00;
        border-radius: 6px;
        padding: 12px 14px;
        font-size: 13px;
    }
    @media (max-width: 1024px) {
        .assistant-layout {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Assistente de peticoes</h2>
    <div class="actions">
        <a class="button secondary link" href="{{ route('peticoes.index') }}">Voltar para montagem</a>
    </div>
</div>

<div class="assistant-layout">
    <div class="stack">
        <div class="panel">
            <div class="section-title">
                <h3>Conversa guiada</h3>
                <div class="actions">
                    <form method="post" action="{{ route('peticoes.assistente.reset') }}">
                        @csrf
                        <button type="submit" class="button secondary">Reiniciar</button>
                    </form>
                </div>
            </div>

            <div class="panel-muted" style="margin-bottom:16px;">
                <strong>Modo atual:</strong>
                @if($openAiEnabled)
                    IA conectada via OpenAI
                @else
                    orientacao local do sistema
                @endif
            </div>

            <div class="panel-muted" style="margin-bottom:16px;">
                <strong>Etapa atual:</strong> {{ $assistantState['conversation_stage_label'] ?? 'Consulta do processo' }}
                @if(!empty($assistantState['assistant_stage_guidance']))
                    <div style="margin-top:8px;">{{ $assistantState['assistant_stage_guidance'] }}</div>
                @endif
                @if(!empty($assistantState['assistant_questions']))
                    <ul style="margin:8px 0 0 18px; padding:0;">
                        @foreach($assistantState['assistant_questions'] as $question)
                            <li>{{ $question }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="assistant-chat">
                @foreach($assistantState['messages'] as $message)
                    <div class="assistant-message assistant-message--{{ $message['role'] }}">
                        <div class="assistant-message__meta">
                            {{ $message['role'] === 'assistant' ? 'Assistente' : 'Voce' }} - {{ $message['at'] }}
                        </div>
                        <div>{{ $message['content'] }}</div>
                    </div>
                @endforeach
            </div>

            <form method="post" action="{{ route('peticoes.assistente.message') }}" style="margin-top:16px;">
                @csrf
                <div class="form-group full">
                    <label>Mensagem</label>
                    <textarea name="message" style="min-height:120px;" placeholder="Ex.: 5001234-55.2026.8.26.0100 ou substabelecimento"></textarea>
                </div>
                <div class="actions" style="margin-top:12px;">
                    <button type="submit">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="assistant-panel">
        <div class="panel">
            <div class="section-title">
                <h3>Processo</h3>
                <div class="editor-note">Dados ja reconhecidos</div>
            </div>

            @if(!empty($assistantState['process_code']))
                <div class="panel-muted" style="margin-bottom:12px;">
                    <strong>Codigo:</strong> {{ $assistantState['process_code'] }}
                </div>
                <div class="assistant-data-list">
                    @foreach(collect($assistantState['process_data'])->take(8) as $key => $value)
                        <div>
                            <strong>{{ $key }}</strong>
                            <span>{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="editor-note">Nenhum processo carregado ainda.</div>
            @endif
        </div>

        <div class="panel">
            <div class="section-title">
                <h3>Modelos sugeridos</h3>
                <div class="editor-note">Escolha rapida</div>
            </div>

            @if(!empty($assistantState['model_suggestions']))
                <div class="assistant-chip-list">
                    @foreach($assistantState['model_suggestions'] as $suggestion)
                        <form method="post" action="{{ route('peticoes.assistente.select-model', $suggestion['id']) }}" class="assistant-chip-form">
                            @csrf
                            <button type="submit" class="assistant-chip">{{ $suggestion['nome'] }}</button>
                        </form>
                    @endforeach
                </div>
            @else
                <div class="editor-note">As sugestoes aparecem depois da consulta do processo ou da sua descricao da peticao.</div>
            @endif
        </div>

        @if(!empty($assistantState['model_rationale']))
            <div class="panel">
                <div class="section-title">
                    <h3>Justificativa do modelo</h3>
                    <div class="editor-note">Apoio da IA</div>
                </div>
                <div class="panel-muted">{{ $assistantState['model_rationale'] }}</div>
            </div>
        @endif

        @if(!empty($assistantState['missing_fields']) || !empty($assistantState['consistency_checks']) || !empty($assistantState['assistant_warnings']))
            <div class="panel">
                <div class="section-title">
                    <h3>Analise interna</h3>
                    <div class="editor-note">Faltantes e alertas</div>
                </div>

                @if(!empty($assistantState['missing_fields']))
                    <div class="panel-muted" style="margin-bottom:12px;">
                        <strong>Campos faltantes</strong>
                        <ul style="margin:8px 0 0 18px; padding:0;">
                            @foreach($assistantState['missing_fields'] as $field)
                                <li>{{ $field }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($assistantState['consistency_checks']))
                    <div class="panel-muted" style="margin-bottom:12px;">
                        <strong>Verificacoes</strong>
                        <ul style="margin:8px 0 0 18px; padding:0;">
                            @foreach($assistantState['consistency_checks'] as $check)
                                <li>{{ $check }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($assistantState['assistant_warnings']))
                    <div class="assistant-warning">
                        <strong>Aviso tecnico</strong>
                        <ul style="margin:8px 0 0 18px; padding:0;">
                            @foreach($assistantState['assistant_warnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if(!empty($assistantState['jurisprudencia_suggestions']))
            <div class="panel">
                <div class="section-title">
                    <h3>Jurisprudencia sugerida</h3>
                    <div class="editor-note">Fonte oficial citada</div>
                </div>

                <table>
                    <thead>
                    <tr>
                        <th>Fonte</th>
                        <th>Termos sugeridos</th>
                        <th>Observacao</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($assistantState['jurisprudencia_suggestions'] as $item)
                        <tr>
                            <td><a href="{{ $item['url'] }}" target="_blank" rel="noopener">{{ $item['fonte'] }}</a></td>
                            <td>{{ $item['termos'] }}</td>
                            <td>{{ $item['observacao'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($assistantState['duplicate_petitions']))
            <div class="panel">
                <div class="section-title">
                    <h3>Possivel duplicidade</h3>
                    <div class="editor-note">Historico com mesmo codigo</div>
                </div>

                <div class="assistant-warning" style="margin-bottom:12px;">
                    Ja existem peticoes salvas com esse codigo de processo. Revise antes de gerar uma nova minuta.
                </div>

                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Modelo</th>
                        <th>Cliente</th>
                        <th>Motivo</th>
                        <th>Salva em</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($assistantState['duplicate_petitions'] as $duplicate)
                        <tr>
                            <td>{{ $duplicate['id'] }}</td>
                            <td>{{ $duplicate['modelo'] ?: '-' }}</td>
                            <td>{{ $duplicate['cliente'] ?: '-' }}</td>
                            <td>{{ !empty($duplicate['reasons']) ? implode(', ', $duplicate['reasons']) : '-' }}</td>
                            <td>{{ $duplicate['salvo_em'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($assistantState['process_code']) && !empty($assistantState['selected_model_id']))
            <div class="panel">
                <div class="section-title">
                    <h3>Proximo passo</h3>
                    <div class="editor-note">Handoff para o fluxo atual</div>
                </div>

                <div class="panel-muted" style="margin-bottom:12px;">
                    <div><strong>Modelo:</strong> {{ $assistantState['selected_model_name'] }}</div>
                    <div><strong>Processo:</strong> {{ $assistantState['process_code'] }}</div>
                </div>

                <form method="post" action="{{ route('peticoes.normalized.compose', $assistantState['selected_model_id']) }}">
                    @csrf
                    <input type="hidden" name="codigo_processo" value="{{ $assistantState['process_code'] }}">
                    <input type="hidden" name="action_type" value="lookup">
                    <button type="submit">Abrir montagem assistida</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
