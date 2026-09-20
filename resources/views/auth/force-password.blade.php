@extends('layouts.app')

@section('title', 'Primeiro acesso')

@section('content')
<div class="panel" style="max-width: 540px;">
    <div class="section-title">
        <h2>Troca obrigatoria de senha</h2>
    </div>

    <p class="editor-note">Este usuario ainda nao concluiu o primeiro acesso. Defina uma nova senha para continuar.</p>
    <p>Use uma senha exclusiva com pelo menos 12 caracteres, incluindo letra maiuscula, letra minuscula, numero e simbolo (como !, @ ou #). Espacos sao permitidos, mas nao contam como simbolo. Nao reutilize a senha temporaria.</p>

    <form method="post" action="{{ route('password.force.update') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group full">
                <label for="password">Nova senha</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="64">
            </div>
            <div class="form-group full">
                <label for="password_confirmation">Confirmar senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="64">
            </div>
        </div>

        <div style="margin-top:20px;" class="actions">
            <button type="submit">Salvar senha</button>
        </div>
    </form>
</div>
@endsection
