@extends('layouts.app')

@section('title', $user->exists ? 'Editar usuario' : 'Novo usuario')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $user->exists ? 'Editar usuario' : 'Novo usuario' }}</h2>
    <a class="button secondary link" href="{{ route('admin.usuarios.index') }}">Voltar</a>
</div>

<div class="panel">
    <form method="post" action="{{ $user->exists ? route('admin.usuarios.update', $user) : route('admin.usuarios.store') }}">
        @csrf
        @if($user->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Nome</label>
                <input name="nome_usu" value="{{ old('nome_usu', $user->nome_usu) }}" required>
            </div>
            <div class="form-group">
                <label>Login</label>
                <input name="login_usu" value="{{ old('login_usu', $user->login_usu) }}" required>
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input name="email_usu" type="email" value="{{ old('email_usu', $user->email_usu) }}">
            </div>
            <div class="form-group">
                <label>Setor</label>
                <select name="id_setor">
                    @if(auth()->user()->nivel_usu === 'ADM')
                        <option value="">Selecione</option>
                    @endif
                    @foreach($setores as $setor)
                        <option value="{{ $setor->id_setor }}" @if((string) old('id_setor', $user->id_setor) === (string) $setor->id_setor) selected @endif>{{ $setor->nome_setor }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Nivel</label>
                <select name="nivel_usu">
                    @foreach((auth()->user()->nivel_usu === 'ADM' ? ['ADM' => 'Administrador', 'GER' => 'Gerencial', 'USU' => 'Usuario'] : ['USU' => 'Usuario']) as $value => $label)
                        <option value="{{ $value }}" @if(old('nivel_usu', $user->nivel_usu) === $value) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status_usu">
                    @foreach(['ATI' => 'Ativo', 'INA' => 'Inativo'] as $value => $label)
                        <option value="{{ $value }}" @if(old('status_usu', $user->status_usu) === $value) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full">
                <label>Clientes</label>
                @if(auth()->user()->nivel_usu === 'GER')
                    <p>Selecione ao menos um cliente da sua carteira. Voce pode gerenciar apenas usuarios de nivel USU, do seu setor e inteiramente vinculados a essa carteira.</p>
                @endif
                <select name="cliente_ids[]" multiple size="8">
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->cliente_id }}" @if(in_array((string) $cliente->cliente_id, array_map('strval', old('cliente_ids', $selectedClients)), true)) selected @endif>{{ $cliente->cliente_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Senha {{ $user->exists ? '(opcional)' : '' }}</label>
                <input name="password" type="password" {{ $user->exists ? '' : 'required' }}>
            </div>
            <div class="form-group">
                <label>Confirmacao de senha</label>
                <input name="password_confirmation" type="password" {{ $user->exists ? '' : 'required' }}>
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
