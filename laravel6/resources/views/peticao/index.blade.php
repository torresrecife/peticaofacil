@extends('layouts.app')

@section('title', 'Montagem de peticoes')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">Montagem de peticoes</h2>
</div>

<div class="panel" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Modelo</th>
                <th>Setor</th>
                <th>Cliente</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($modelos as $modelo)
                <tr>
                    <td>{{ $modelo->tipo_id }}</td>
                    <td>{{ $modelo->tipo_nome }}</td>
                    <td>{{ optional($modelo->setor)->nome_setor }}</td>
                    <td>{{ optional($modelo->cliente)->cliente_name ?: 'Todos do setor' }}</td>
                    <td><a href="{{ route('peticoes.show', $modelo) }}">Montar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $modelos->links('vendor.pagination.default') }}
</div>
@endsection
