<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\Setor;
use App\SqlServerProfile;
use App\SqlServerConfig;
use App\Support\LegacyEditorContent;
use App\Services\NormalizedModeloLegacySyncService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NormalizedTipoController extends Controller
{
    public function index()
    {
        $modelos = PeticaoModelo::with(['setor', 'cliente', 'servidor'])
            ->withCount(['paragrafos', 'campos'])
            ->orderBy('legacy_setor_id')
            ->orderBy('nome')
            ->paginate(20);

        $legacyFallbacks = \App\Tipo::with(['setor', 'cliente', 'servidor'])
            ->orderBy('id_setor')
            ->orderBy('tipo_nome')
            ->whereNotIn('tipo_id', PeticaoModelo::whereNotNull('legacy_tipo_id')->pluck('legacy_tipo_id'))
            ->get();

        return view('admin.tipos.index', compact('modelos', 'legacyFallbacks'));
    }

    public function create()
    {
        $modelo = new PeticaoModelo([
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'metadata' => [],
        ]);

        return view('admin.tipos.form', [
            'modelo' => $modelo,
            'mirror' => null,
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'servidores' => $this->availableServidores(),
        ]);
    }

    public function store(Request $request, NormalizedModeloLegacySyncService $syncService)
    {
        $data = $this->validateData($request);

        $modelo = PeticaoModelo::create([
            'legacy_tipo_id' => null,
            'legacy_sql_config_id' => $data['id_db'] ?: null,
            'legacy_cliente_id' => $data['id_cliente'] ?: null,
            'legacy_setor_id' => $data['id_setor'],
            'nome' => $data['tipo_nome'],
            'slug' => $this->buildSlug($data['tipo_nome']),
            'status' => $data['tipo_stt'] === 'Y' ? 'ativo' : 'inativo',
            'arquivo_padrao' => $data['tipo_arq'],
            'cabecalho_html' => $data['cod_cabec'] ?? null,
            'rodape_html' => $data['cod_rodap'] ?? null,
            'metadata' => [
                'nome_pre' => $data['nome_pre'] ?? null,
                'nome_pos' => $data['nome_pos'] ?? null,
            ],
        ]);

        $syncService->sync($modelo->fresh(['paragrafos', 'campos.opcoes']));

        return redirect()->route('admin.modelos-normalizados.edit', $modelo)->with('status', 'Modelo criado.');
    }

    public function edit(PeticaoModelo $modeloNormalizado)
    {
        $modeloNormalizado->load(['paragrafos', 'campos.opcoes', 'setor', 'cliente', 'servidor']);
        $modeloNormalizado = $this->prepareForEditor($modeloNormalizado);

        return view('admin.tipos.form', [
            'modelo' => $modeloNormalizado,
            'mirror' => $modeloNormalizado,
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'servidores' => $this->availableServidores(),
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

    protected function buildSlug($nome)
    {
        $slug = \Illuminate\Support\Str::slug($nome ?: 'modelo');

        if ($slug === '') {
            $slug = 'modelo';
        }

        return $slug . '-' . substr(md5((string) microtime(true)), 0, 8);
    }

    protected function availableServidores(): Collection
    {
        $profiles = SqlServerProfile::active()->orderBy('nome')->get();

        if ($profiles->isEmpty()) {
            return SqlServerConfig::active()->orderBy('nome_db')->get();
        }

        $legacyIds = $profiles->pluck('legacy_config_id')->filter()->values()->all();
        $legacyFallback = SqlServerConfig::active()
            ->when(!empty($legacyIds), function ($query) use ($legacyIds) {
                $query->whereNotIn('id_db', $legacyIds);
            })
            ->orderBy('nome_db')
            ->get();

        return $profiles->concat($legacyFallback)->values();
    }
}
