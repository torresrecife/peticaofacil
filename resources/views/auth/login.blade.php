@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <h1 style="margin-top:0;">Acesso ao novo painel</h1>
        <p style="margin-bottom:20px;">Autenticacao principal pela base normalizada de usuarios.</p>

        @if($errors->any())
            <div class="errors">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('login.attempt') }}">
            @csrf
            <div class="form-group">
                <label for="username">Usuario</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required>
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div style="margin-top:20px;">
                <button type="submit">Entrar</button>
            </div>
        </form>
    </div>
</div>
@endsection
