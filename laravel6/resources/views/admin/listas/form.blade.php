@extends('layouts.app')

@section('title', $lista->exists ? 'Editar lista' : 'Nova lista')

@section('content')
<div class="topbar" style="margin-bottom:16px;">
    <h2 style="margin:0;">{{ $lista->exists ? 'Editar lista' : 'Nova lista' }}</h2>
    <a class="button secondary link" href="{{ route('admin.listas.index') }}">Voltar</a>
</div>

<form method="post" action="{{ $lista->exists ? route('admin.listas.update', $lista) : route('admin.listas.store') }}">
    @csrf
    @if($lista->exists)
        @method('put')
    @endif

    <div class="panel" style="margin-bottom:16px;">
        <div class="form-grid">
            <div class="form-group">
                <label>Grupo</label>
                <input name="id_grupo" value="{{ old('id_grupo', $lista->id_grupo) }}" {{ $lista->exists ? 'readonly' : 'readonly' }}>
            </div>
            <div class="form-group">
                <label>Nome do grupo</label>
                <input name="nome_grupo" value="{{ old('nome_grupo', $lista->nome_grupo) }}" required>
            </div>
        </div>
    </div>

    @php
        $items = old('items');
        if ($items === null) {
            $items = $lista->exists ? $lista->itens->map(function ($item) {
                return [
                    'id_lista' => $item->id_lista,
                    'nome_lista' => $item->nome_lista,
                    'return_1' => $item->return_1,
                    'return_2' => $item->return_2,
                    'return_3' => $item->return_3,
                    'return_4' => $item->return_4,
                    'return_5' => $item->return_5,
                    'return_6' => $item->return_6,
                    'id_setor' => $item->id_setor,
                ];
            })->toArray() : [];
        }
        if (empty($items)) {
            $items = [[
                'id_lista' => '',
                'nome_lista' => '',
                'return_1' => '',
                'return_2' => '',
                'return_3' => '',
                'return_4' => '',
                'return_5' => '',
                'return_6' => '',
                'id_setor' => auth()->user()->id_setor ?: 1,
            ]];
        }
    @endphp

    <div class="panel">
        <div class="section-title">
            <h3>Itens da lista</h3>
            <button type="button" id="add-list-item" class="button secondary">Adicionar linha</button>
        </div>

        <div style="overflow:auto;">
            <table id="list-items-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Retorno 1</th>
                        <th>Retorno 2</th>
                        <th>Retorno 3</th>
                        <th>Retorno 4</th>
                        <th>Retorno 5</th>
                        <th>Retorno 6</th>
                        <th>Setor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][id_lista]" value="{{ $item['id_lista'] ?? '' }}">
                                <input name="items[{{ $index }}][nome_lista]" value="{{ $item['nome_lista'] ?? '' }}">
                            </td>
                            <td><input name="items[{{ $index }}][return_1]" value="{{ $item['return_1'] ?? '' }}"></td>
                            <td><input name="items[{{ $index }}][return_2]" value="{{ $item['return_2'] ?? '' }}"></td>
                            <td><input name="items[{{ $index }}][return_3]" value="{{ $item['return_3'] ?? '' }}"></td>
                            <td><input name="items[{{ $index }}][return_4]" value="{{ $item['return_4'] ?? '' }}"></td>
                            <td><input name="items[{{ $index }}][return_5]" value="{{ $item['return_5'] ?? '' }}"></td>
                            <td><input name="items[{{ $index }}][return_6]" value="{{ $item['return_6'] ?? '' }}"></td>
                            <td><input name="items[{{ $index }}][id_setor]" value="{{ $item['id_setor'] ?? '' }}" style="width:90px;"></td>
                            <td><button type="button" class="button secondary js-remove-row" style="padding:6px 10px;">Remover</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            <button type="submit">Salvar lista</button>
        </div>
    </div>
</form>

<template id="list-item-row-template">
    <tr>
        <td>
            <input type="hidden" data-name="id_lista" value="">
            <input data-name="nome_lista" value="">
        </td>
        <td><input data-name="return_1" value=""></td>
        <td><input data-name="return_2" value=""></td>
        <td><input data-name="return_3" value=""></td>
        <td><input data-name="return_4" value=""></td>
        <td><input data-name="return_5" value=""></td>
        <td><input data-name="return_6" value=""></td>
        <td><input data-name="id_setor" value="{{ auth()->user()->id_setor ?: 1 }}" style="width:90px;"></td>
        <td><button type="button" class="button secondary js-remove-row" style="padding:6px 10px;">Remover</button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tableBody = document.querySelector('#list-items-table tbody');
    var addButton = document.getElementById('add-list-item');
    var template = document.getElementById('list-item-row-template');

    function reindexRows() {
        Array.prototype.forEach.call(tableBody.querySelectorAll('tr'), function (row, index) {
            Array.prototype.forEach.call(row.querySelectorAll('[data-name], input[name]'), function (input) {
                var field = input.getAttribute('data-name');
                if (!field) {
                    var match = input.name.match(/\]\[(.*?)\]$/);
                    field = match ? match[1] : '';
                }
                if (!field) {
                    return;
                }
                input.name = 'items[' + index + '][' + field + ']';
            });
        });
    }

    addButton.addEventListener('click', function () {
        var row = document.importNode(template.content, true);
        tableBody.appendChild(row);
        reindexRows();
    });

    tableBody.addEventListener('click', function (event) {
        if (!event.target.classList.contains('js-remove-row')) {
            return;
        }
        event.preventDefault();
        var row = event.target.closest('tr');
        if (!row) {
            return;
        }
        row.remove();
        if (!tableBody.querySelector('tr')) {
            addButton.click();
        } else {
            reindexRows();
        }
    });

    reindexRows();
});
</script>
@endpush
