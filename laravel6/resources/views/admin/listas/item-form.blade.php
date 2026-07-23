@extends('layouts.app')

@section('title', $item->exists ? 'Editar item da lista' : 'Novo item da lista')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <div>
        <h2 style="margin:0;">{{ $item->exists ? 'Editar item da lista' : 'Novo item da lista' }}</h2>
        <div style="font-size:13px; color:#6b7280; margin-top:4px;">
            Lista {{ $lista->id_grupo }} - {{ $lista->nome_grupo }}
        </div>
    </div>
    <a class="button secondary link" href="{{ route('admin.listas.edit', $lista) }}">Voltar para a lista</a>
</div>

<form method="post" action="{{ $item->exists ? route('admin.listas.itens.update', [$lista, $item]) : route('admin.listas.itens.store', $lista) }}">
    @csrf
    @if($item->exists)
        @method('put')
    @endif

    <div class="panel">
        <div class="form-grid">
            <div class="form-group" style="grid-column:1 / -1;">
                <label>Nome do item</label>
                <input name="nome_lista" value="{{ old('nome_lista', $item->nome_lista) }}" required>
            </div>
            <div class="form-group">
                <label>Retorno 1</label>
                <input name="return_1" value="{{ old('return_1', $item->return_1) }}">
            </div>
            <div class="form-group">
                <label>Retorno 2</label>
                <input name="return_2" value="{{ old('return_2', $item->return_2) }}">
            </div>
            <div class="form-group">
                <label>Retorno 3</label>
                <input name="return_3" value="{{ old('return_3', $item->return_3) }}">
            </div>
            <div class="form-group">
                <label>Retorno 4</label>
                <input name="return_4" value="{{ old('return_4', $item->return_4) }}">
            </div>
            <div class="form-group">
                <label>Retorno 5</label>
                <input name="return_5" value="{{ old('return_5', $item->return_5) }}">
            </div>
            <div class="form-group">
                <label>Retorno 6</label>
                <input name="return_6" value="{{ old('return_6', $item->return_6) }}">
            </div>
            <div class="form-group">
                <label>Setor</label>
                <input name="id_setor" value="{{ old('id_setor', $item->id_setor ?: (auth()->user()->id_setor ?: 1)) }}">
            </div>
        </div>

        <div style="margin-top:16px;">
            <button type="submit">{{ $item->exists ? 'Salvar item' : 'Criar item' }}</button>
        </div>
    </div>
</form>
@endsection
