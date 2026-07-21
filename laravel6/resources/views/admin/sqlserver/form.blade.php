@extends('layouts.app')

@section('title', $config->exists ? 'Editar servidor SQL' : 'Novo servidor SQL')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $config->exists ? 'Editar servidor SQL' : 'Novo servidor SQL' }}</h2>
    <a class="button secondary link" href="{{ route('admin.servidores.index') }}">Voltar</a>
</div>

<div class="panel">
    <form method="post" action="{{ $config->exists ? route('admin.servidores.update', $config) : route('admin.servidores.store') }}">
        @csrf
        @if($config->exists)
            @method('put')
        @endif
        <div class="form-grid">
            <div class="form-group">
                <label>Nome</label>
                <input name="nome_db" value="{{ old('nome_db', $config->nome_db) }}" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="stt">
                    <option value="Y" @if(old('stt', $config->stt) === 'Y') selected @endif>Ativo</option>
                    <option value="N" @if(old('stt', $config->stt) === 'N') selected @endif>Inativo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Host</label>
                <input name="ip_db" value="{{ old('ip_db', $config->ip_db) }}" required>
            </div>
            <div class="form-group">
                <label>Banco</label>
                <input name="data_db" value="{{ old('data_db', $config->data_db) }}" required>
            </div>
            <div class="form-group">
                <label>Usuario</label>
                <input name="usu_db" value="{{ old('usu_db', $config->usu_db) }}" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input name="senha_db" value="{{ old('senha_db', $config->senha_db) }}">
            </div>
            <div class="form-group">
                <label>Tabela</label>
                <input name="table_db" value="{{ old('table_db', $config->table_db) }}">
            </div>
            <div class="form-group">
                <label>Chave</label>
                <input name="chave_db" value="{{ old('chave_db', $config->chave_db) }}">
            </div>
            <div class="form-group full">
                <label>Query</label>
                <textarea name="query_db">{{ old('query_db', $config->query_db) }}</textarea>
            </div>
            <div class="form-group full">
                <label>Where</label>
                <textarea name="where_db">{{ old('where_db', $config->where_db) }}</textarea>
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
