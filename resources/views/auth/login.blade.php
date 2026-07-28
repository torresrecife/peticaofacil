@extends('layouts.app')

@section('title', 'Login')

@push('head')
<style>
    .auth-login {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(520px, 820px);
        background: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
        overflow: hidden;
        box-sizing: border-box;
    }
    .auth-login__hero,
    .auth-login__panel {
        min-height: 100vh;
        box-sizing: border-box;
    }
    .auth-login__hero {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 40px;
        border-right: 1px solid rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .auth-login__hero-inner {
        max-width: 680px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
    }
    .auth-login__hero-image {
        width: min(100%, 620px);
        display: block;
    }
    .auth-login__hero-image img {
        width: 100%;
        height: auto;
        object-fit: contain;
        display: block;
    }
    .auth-login__panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 40px;
        background: rgba(248, 250, 252, 0.72);
        overflow: hidden;
    }
    .auth-login__card {
        width: min(100%, 500px);
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(188, 204, 220, 0.9);
        border-radius: 24px;
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.12);
        padding: 34px 32px 30px;
        box-sizing: border-box;
    }
    .auth-login__eyebrow {
        margin: 0 0 18px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #829ab1;
    }
    .auth-login__title {
        margin: 0;
        font-size: 28px;
        line-height: 1.1;
        color: #102a43;
    }
    .auth-login__subtitle {
        margin: 10px 0 24px;
        font-size: 15px;
        line-height: 1.6;
        color: #627d98;
    }
    .auth-login__form {
        display: grid;
        gap: 18px;
    }
    .auth-login__form .form-group {
        gap: 8px;
    }
    .auth-login__form label {
        font-size: 14px;
        font-weight: 700;
        color: #102a43;
    }
    .auth-login__form input {
        min-height: 44px;
        padding: 0 14px;
        border-radius: 10px;
        border-color: #cbd5e1;
        background: #f8fafc;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .auth-login__form input:focus {
        outline: 0;
        background: #fff;
        border-color: #1f5f8b;
        box-shadow: 0 0 0 3px rgba(31, 95, 139, 0.12);
    }
    .auth-login__submit {
        min-height: 46px;
        border-radius: 14px;
        font-weight: 700;
        background: linear-gradient(180deg, #ffffff 0%, #edf4fb 100%);
        color: #0f4c81;
        border: 1px solid #bfd2e6;
    }
    .auth-login__submit:hover {
        background: linear-gradient(180deg, #ffffff 0%, #e4eef9 100%);
    }
    @media (max-width: 1120px) {
        .auth-login {
            grid-template-columns: 1fr;
            overflow: visible;
        }
        .auth-login__hero {
            min-height: auto;
            padding: 28px 28px 12px;
            border-right: 0;
        }
        .auth-login__hero-inner {
            max-width: 560px;
        }
        .auth-login__hero-image {
            width: min(100%, 480px);
        }
        .auth-login__panel {
            min-height: auto;
            padding: 12px 28px 40px;
            background: transparent;
        }
    }
    @media (max-width: 640px) {
        .auth-login__hero,
        .auth-login__panel {
            padding-left: 20px;
            padding-right: 20px;
        }
        .auth-login__hero {
            padding-top: 20px;
            padding-bottom: 8px;
        }
        .auth-login__hero-image {
            width: min(100%, 360px);
        }
        .auth-login__card {
            border-radius: 20px;
            padding: 28px 22px 24px;
        }
    }
</style>
@endpush

@section('content')
<div class="auth-login">
    <section class="auth-login__hero">
        <div class="auth-login__hero-inner">
            <div class="auth-login__hero-image" aria-hidden="true">
                <img src="{{ asset('img/login-left-panel.png') }}" alt="">
            </div>
        </div>
    </section>

    <section class="auth-login__panel">
        <div class="auth-login__card">
            <p class="auth-login__eyebrow">Area administrativa</p>
            <h2 class="auth-login__title">Acessar</h2>
            <p class="auth-login__subtitle">Entre com suas credenciais para abrir o painel do sistema.</p>

            @if($errors->any())
                <div class="errors">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form class="auth-login__form" method="post" action="{{ route('login.attempt') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Nome de Usuario</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div>
                    <button class="auth-login__submit" type="submit">Acessar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
