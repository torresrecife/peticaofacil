<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Setor;
use App\SqlServerProfile;
use App\Support\LegacyEditorContent;
use App\Services\LegacyModeloSyncService;
use App\Tipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegacyTipoFallbackController extends Controller
{
    public function edit(Tipo $modelo)
    {
        $modelo->load(['paragrafos', 'campos.dados', 'setor', 'cliente', 'servidor']);
        $modelo = $this->prepareForEditor($modelo);

        return view('admin.tipos.form', [
            'modelo' => $modelo,
            'mirror' => null,
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'servidores' => SqlServerProfile::active()->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService)
    {
        $modelo->fill($this->validateData($request))->save();
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos-normalizados.edit', $mirror)->with('status', 'Modelo atualizado.');
    }

    protected function validateData(Request $request)
    {
        $data = $request->validate([
            'tipo_nome' => 'required|string|max:300',
            'nome_pre' => 'nullable|string|max:300',
            'nome_pos' => 'nullable|string|max:300',
            'id_db' => 'nullable|integer',
            'id_cliente' => 'nullable|integer',
            'id_setor' => 'required|integer',
            'tipo_stt' => ['required', Rule::in(['Y', 'N'])],
            'tipo_arq' => ['required', Rule::in(['pdf', 'word', 'pdf,word'])],
            'cod_cabec' => 'nullable|string',
            'cod_rodap' => 'nullable|string',
        ]);

        $data['cod_cabec'] = LegacyEditorContent::denormalize($data['cod_cabec'] ?? null);
        $data['cod_rodap'] = LegacyEditorContent::denormalize($data['cod_rodap'] ?? null);

        return $data;
    }

    protected function prepareForEditor(Tipo $modelo)
    {
        $modelo->cod_cabec = LegacyEditorContent::normalize($modelo->cod_cabec);
        $modelo->cod_rodap = LegacyEditorContent::normalize($modelo->cod_rodap);

        if ($modelo->relationLoaded('paragrafos')) {
            $modelo->paragrafos->transform(function ($paragrafo) {
                $paragrafo->fund_text = LegacyEditorContent::normalize($paragrafo->fund_text);

                return $paragrafo;
            });
        }

        return $modelo;
    }
}
