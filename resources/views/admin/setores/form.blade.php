@extends('layouts.app')

@section('title', $setor->exists ? 'Editar setor' : 'Novo setor')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $setor->exists ? 'Editar setor' : 'Novo setor' }}</h2>
    <a class="button secondary link" href="{{ route('admin.setores.index') }}">Voltar</a>
</div>

<div class="panel">
    <form method="post" action="{{ $setor->exists ? route('admin.setores.update', $setor) : route('admin.setores.store') }}">
        @csrf
        @if($setor->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Nome</label>
                <input name="nome_setor" value="{{ old('nome_setor', $setor->nome_setor) }}" required>
            </div>
            <div class="form-group">
                <label>Codigo</label>
                <input name="cod_setor" value="{{ old('cod_setor', $setor->cod_setor) }}">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
