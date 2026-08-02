@extends('layouts.app')

@section('title', 'Criar peticao avulsa')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Criar peticao avulsa</h2>
    <div class="actions">
        @can('access-admin')
            <a class="button secondary link" href="{{ route('admin.peticoes-avulsas.config.edit') }}">Configurar cabecalho/rodape</a>
        @endcan
        <a class="button secondary link" href="{{ route('pecas.index') }}">Voltar para pecas salvas</a>
    </div>
</div>

<div class="panel" style="max-width:760px;">
    <div class="panel-muted" style="margin-bottom:16px;">
        <strong>Cadastro inicial</strong>
        <div class="editor-note">Informe o tipo da peticao e o nome da parte contraria. Depois disso o editor abre para redacao livre.</div>
    </div>

    <form method="post" action="{{ route('peticoes.avulsas.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label for="tipo_peticao">Tipo de peticao</label>
                <input id="tipo_peticao" name="tipo_peticao" value="{{ old('tipo_peticao') }}" required maxlength="255" placeholder="Ex.: Manifestacao, Peticao intermediaria, Contestacao">
            </div>
            <div class="form-group">
                <label for="parte_contraria">Nome da parte contraria</label>
                <input id="parte_contraria" name="parte_contraria" value="{{ old('parte_contraria') }}" required maxlength="500" placeholder="Nome da parte contraria para cadastro">
            </div>
            <div class="form-group">
                <label for="codigo_processo">Codigo do processo (opcional)</label>
                <input id="codigo_processo" name="codigo_processo" value="{{ old('codigo_processo') }}" maxlength="255" placeholder="Ex.: 0030623-76.2021.8.17.2810">
            </div>
        </div>

        <div class="actions" style="margin-top:16px;">
            <button type="submit">Abrir editor avulso</button>
            <a class="button secondary link" href="{{ route('pecas.index') }}">Cancelar</a>
        </div>
    </form>
</div>
@endsection
