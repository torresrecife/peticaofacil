@extends('layouts.app')

@section('title', 'Painel')

@section('content')
<div class="grid">
    <div class="stat">
        Usuarios
        <strong>{{ $userCount }}</strong>
    </div>
    <div class="stat">
        Usuarios ativos
        <strong>{{ $activeUserCount }}</strong>
    </div>
    <div class="stat">
        Setores
        <strong>{{ $setorCount }}</strong>
    </div>
    <div class="stat">
        Clientes
        <strong>{{ $clienteCount }}</strong>
    </div>
</div>

<div class="panel" style="margin-top:24px;">
    <h2 style="margin-top:0;">Etapa atual</h2>
    <p>A base Laravel 6 ja esta conectada ao banco atual e os primeiros modulos administrativos usam dados reais do sistema legado.</p>
</div>
@endsection
