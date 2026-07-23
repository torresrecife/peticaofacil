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
                <input value="{{ $lista->id_grupo }}" readonly>
            </div>
            <div class="form-group">
                <label>Nome do grupo</label>
                <input name="nome_grupo" value="{{ old('nome_grupo', $lista->nome_grupo) }}" required>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button type="submit">Salvar lista</button>
        </div>
    </div>
</form>

@if($lista->exists)
    <div class="panel">
        <div class="section-title">
            <h3>Itens da lista</h3>
            <a class="button link" href="{{ route('admin.listas.itens.create', $lista) }}">Novo item</a>
        </div>

        @if($lista->itens->isEmpty())
            <p style="margin:0; color:#6b7280;">Nenhum item cadastrado nesta lista.</p>
        @else
            <div style="display:grid; gap:12px;">
                @foreach($lista->itens as $item)
                    <div style="border:1px solid #d7dce5; border-radius:8px; padding:14px 16px; background:#fff;">
                        <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start;">
                            <div style="min-width:0;">
                                <div style="font-size:13px; color:#6b7280; margin-bottom:6px;">Item #{{ $item->id_lista }}</div>
                                <div style="font-weight:600; font-size:15px; color:#1f2937; margin-bottom:10px;">
                                    {{ $item->nome_lista ?: 'Sem nome' }}
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                    @php
                                        $returns = collect([
                                            'Retorno 1' => $item->return_1,
                                            'Retorno 2' => $item->return_2,
                                            'Retorno 3' => $item->return_3,
                                            'Retorno 4' => $item->return_4,
                                            'Retorno 5' => $item->return_5,
                                            'Retorno 6' => $item->return_6,
                                        ])->filter(function ($value) {
                                            return trim((string) $value) !== '';
                                        });
                                    @endphp
                                    @forelse($returns as $label => $value)
                                        <span style="display:inline-flex; align-items:center; gap:6px; border:1px solid #d7dce5; border-radius:999px; padding:4px 10px; background:#f8fafc; font-size:12px; color:#334155;">
                                            <strong style="font-weight:600;">{{ $label }}</strong>
                                            <span>{{ $value }}</span>
                                        </span>
                                    @empty
                                        <span style="font-size:12px; color:#6b7280;">Sem retornos configurados.</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="actions" style="flex-shrink:0;">
                                <a href="{{ route('admin.listas.itens.edit', [$lista, $item]) }}">Editar</a>
                                <form method="post" action="{{ route('admin.listas.itens.destroy', [$lista, $item]) }}" onsubmit="return confirm('Remover este item da lista?');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="button secondary" style="padding:6px 10px;">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
@endsection
