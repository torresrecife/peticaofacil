<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\NormalizedTipoController;
use App\PeticaoModelo;
use App\Setor;
use App\SqlServerConfig;
use App\Support\LegacyEditorContent;
use App\Services\LegacyModeloSyncService;
use App\Services\NormalizedModeloLegacySyncService;
use App\Tipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoController extends Controller
{
    public function index()
    {
        $modelos = PeticaoModelo::with(['setor', 'cliente', 'servidor'])
            ->withCount(['paragrafos', 'campos'])
            ->orderBy('legacy_setor_id')
            ->orderBy('nome')
            ->paginate(20);

        $legacyFallbacks = Tipo::with(['setor', 'cliente', 'servidor'])
            ->orderBy('id_setor')
            ->orderBy('tipo_nome')
            ->whereNotIn('tipo_id', PeticaoModelo::whereNotNull('legacy_tipo_id')->pluck('legacy_tipo_id'))
            ->get();

        return view('admin.tipos.index', compact('modelos', 'legacyFallbacks'));
    }

    public function create()
    {
        return redirect()->route('admin.modelos-normalizados.create');
    }

    public function store(Request $request, LegacyModeloSyncService $syncService)
    {
        $tipo = new Tipo($this->validateData($request));
        $tipo->tipo_data = now();
        $tipo->save();
        $syncService->syncTipo($tipo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos.edit', $tipo)->with('status', 'Modelo criado.');
    }

    public function edit(Tipo $modelo)
    {
        $mirror = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->first();
        if ($mirror) {
            return redirect()->route('admin.modelos-normalizados.edit', $mirror);
        }

        $modelo->load(['paragrafos', 'campos.dados', 'setor', 'cliente', 'servidor']);
        $modelo = $this->prepareForEditor($modelo);

        return view('admin.tipos.form', array_merge($this->formData($modelo), ['mirror' => $mirror]));
    }

    public function update(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService, NormalizedModeloLegacySyncService $normalizedSyncService)
    {
        $mirror = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->first();
        if ($mirror) {
            return app(NormalizedTipoController::class)->update($request, $mirror, $normalizedSyncService);
        }

        $modelo->fill($this->validateData($request))->save();
        $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos.edit', $modelo)->with('status', 'Modelo atualizado.');
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

    protected function formData(Tipo $modelo)
    {
        return [
            'modelo' => $modelo,
            'mirror' => null,
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'servidores' => SqlServerConfig::active()->orderBy('nome_db')->get(),
        ];
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
