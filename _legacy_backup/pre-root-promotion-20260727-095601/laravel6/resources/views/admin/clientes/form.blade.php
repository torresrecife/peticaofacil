@extends('layouts.app')

@section('title', $cliente->exists ? 'Editar cliente' : 'Novo cliente')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $cliente->exists ? 'Editar cliente' : 'Novo cliente' }}</h2>
    <a class="button secondary link" href="{{ route('admin.clientes.index') }}">Voltar</a>
</div>

<div class="panel">
    <form method="post" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}">
        @csrf
        @if($cliente->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Nome</label>
                <input name="cliente_name" value="{{ old('cliente_name', $cliente->cliente_name) }}" required>
            </div>
            <div class="form-group">
                <label>Codigo</label>
                <input name="cliente_cod" value="{{ old('cliente_cod', $cliente->cliente_cod) }}">
            </div>
            <div class="form-group">
                <label>Setor</label>
                <select name="cliente_area">
                    <option value="">Selecione</option>
                    @foreach($setores as $setor)
                        <option value="{{ $setor->id_setor }}" @if((string) old('cliente_area', $cliente->cliente_area) === (string) $setor->id_setor) selected @endif>{{ $setor->nome_setor }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="cliente_status">
                    <option value="Y" @if(old('cliente_status', $cliente->cliente_status) === 'Y') selected @endif>Ativo</option>
                    <option value="N" @if(old('cliente_status', $cliente->cliente_status) === 'N') selected @endif>Inativo</option>
                </select>
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
