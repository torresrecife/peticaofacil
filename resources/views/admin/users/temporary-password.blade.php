@extends('layouts.app')
@section('title', 'Acesso temporario')
@section('content')
<div class="panel">
    <h2>Acesso temporario emitido</h2>
    <p>Copie os dados agora e entregue diretamente ao usuario por um canal seguro. A senha nao podera ser consultada novamente.</p>
    <p>Login: <strong>{{ $user->login_usu }}</strong></p>
    <p>Senha temporaria: <code>{{ $temporaryPassword }}</code></p>
    <p>Valida por 24 horas. No primeiro acesso, o usuario devera criar sua propria senha antes de utilizar o sistema.</p>
    <p>Se a senha expirar ou for perdida, edite o usuario e solicite uma nova senha temporaria. A anterior deixara de funcionar.</p>
    <a class="button link" href="{{ route('admin.usuarios.index') }}">Voltar aos usuarios</a>
</div>
@endsection
