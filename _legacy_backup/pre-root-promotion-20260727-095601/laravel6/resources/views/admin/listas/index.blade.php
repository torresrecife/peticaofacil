@extends('layouts.app')

@section('title', 'Listas')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Listas</h2>
    <a class="button link" href="{{ route('admin.listas.create') }}">Nova lista</a>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>Grupo</th>
                <th>Nome</th>
                <th>Itens</th>
                <th>Atualizacao</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($listas as $lista)
                <tr>
                    <td>{{ $lista->id_grupo }}</td>
                    <td>{{ $lista->nome_grupo }}</td>
                    <td>{{ $lista->itens_count }}</td>
                    <td>{{ $lista->data_cad }}</td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('admin.listas.edit', $lista) }}">Editar</a>
                            <form method="post" action="{{ route('admin.listas.destroy', $lista) }}" onsubmit="return confirm('Remover esta lista?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="button secondary" style="padding:6px 10px;">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $listas->links('vendor.pagination.default') }}
</div>
@endsection
