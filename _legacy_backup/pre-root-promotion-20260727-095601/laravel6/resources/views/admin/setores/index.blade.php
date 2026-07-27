@extends('layouts.app')

@section('title', 'Setores')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Setores</h2>
    <a class="button link" href="{{ route('admin.setores.create') }}">Novo setor</a>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Codigo</th>
                <th>Cadastro</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($setores as $setor)
                <tr>
                    <td>{{ $setor->id_setor }}</td>
                    <td>{{ $setor->nome_setor }}</td>
                    <td>{{ $setor->cod_setor }}</td>
                    <td>{{ optional($setor->data_cad)->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('admin.setores.edit', $setor) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $setores->links('vendor.pagination.default') }}
</div>
@endsection
