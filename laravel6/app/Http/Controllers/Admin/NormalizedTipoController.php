<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\Setor;
use App\SqlServerConfig;
use App\Support\LegacyEditorContent;
use App\Services\NormalizedModeloLegacySyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NormalizedTipoController extends Controller
{
    public function edit(PeticaoModelo $modeloNormalizado)
    {
        $modeloNormalizado->load(['paragrafos', 'campos.opcoes', 'setor', 'cliente', 'servidor']);
        $modeloNormalizado = $this->prepareForEditor($modeloNormalizado);

        return view('admin.tipos.form', [
            'modelo' => $modeloNormalizado,
            'mirror' => $modeloNormalizado,
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'servidores' => SqlServerConfig::active()->orderBy('nome_db')->get(),
        ]);
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado, NormalizedModeloLegacySyncService $syncService)
    {
        $data = $this->validateData($request);

        $metadata = $modeloNormalizado->metadata ?: [];
        $metadata['nome_pre'] = $data['nome_pre'] ?? null;
        $metadata['nome_pos'] = $data['nome_pos'] ?? null;

        $modeloNormalizado->fill([
            'legacy_sql_config_id' => $data['id_db'] ?: null,
            'legacy_cliente_id' => $data['id_cliente'] ?: null,
            'legacy_setor_id' => $data['id_setor'],
            'nome' => $data['tipo_nome'],
            'status' => $data['tipo_stt'] === 'Y' ? 'ativo' : 'inativo',
            'arquivo_padrao' => $data['tipo_arq'],
            'cabecalho_html' => $data['cod_cabec'] ?? null,
            'rodape_html' => $data['cod_rodap'] ?? null,
            'metadata' => $metadata,
        ])->save();

        $syncService->sync($modeloNormalizado->fresh(['paragrafos', 'campos.opcoes']));

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Modelo atualizado.');
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

    protected function prepareForEditor(PeticaoModelo $modelo)
    {
        $modelo->cabecalho_html = LegacyEditorContent::normalize($modelo->cabecalho_html);
        $modelo->rodape_html = LegacyEditorContent::normalize($modelo->rodape_html);

        if ($modelo->relationLoaded('paragrafos')) {
            $modelo->paragrafos->transform(function ($paragrafo) {
                $paragrafo->conteudo_html = LegacyEditorContent::normalize($paragrafo->conteudo_html);

                return $paragrafo;
            });
        }

        return $modelo;
    }
}
