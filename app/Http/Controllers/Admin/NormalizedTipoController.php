<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\ListaGrupo;
use App\PeticaoModelo;
use App\Setor;
use App\SqlServerProfile;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\PeticaoModeloAiBuilderService;
use Throwable;

class NormalizedTipoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $setorId = (int) $request->query('setor_id', 0);
        $clienteId = (int) $request->query('cliente_id', 0);

        $modelos = PeticaoModelo::with(['setor', 'cliente', 'servidor'])
            ->withCount(['paragrafos', 'campos'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('nome', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%')
                        ->orWhere('legacy_tipo_id', 'like', '%' . $search . '%');
                });
            })
            ->when($setorId > 0, function ($query) use ($setorId) { return $query->where('legacy_setor_id', $setorId); })
            ->when($clienteId > 0, function ($query) use ($clienteId) { return $query->where('legacy_cliente_id', $clienteId); })
            ->orderBy('legacy_setor_id')
            ->orderBy('nome')
            ->paginate(20)
            ->appends($request->except('page'));

        $suggestions = PeticaoModelo::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('nome', 'like', '%' . $search . '%');
            })
            ->orderBy('nome')
            ->limit(30)
            ->pluck('nome')
            ->filter()
            ->unique()
            ->values();

        return view('admin.tipos.index', [
            'modelos' => $modelos, 'search' => $search, 'suggestions' => $suggestions,
            'setorId' => $setorId, 'clienteId' => $clienteId,
            'setores' => Setor::orderBy('nome_setor')->get(), 'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
        ]);
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
            'listaGrupos' => ListaGrupo::orderBy('nome_grupo')->get(),
        ]);
    }

    public function store(Request $request)
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

        return redirect()->route('admin.modelos-normalizados.edit', $modelo)->with('status', 'Modelo criado.');
    }

    public function analyzeWord(Request $request, PeticaoModeloAiBuilderService $builder)
    {
        $data = $request->validate(['word_file' => 'required|file|mimes:doc,docx|max:25600']);
        try {
            $analysis = $builder->analyze($data['word_file']);
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->withErrors([
                'word_file' => 'A analise demorou mais que o limite ou nao respondeu. Tente novamente com um arquivo menor ou aumente OPENAI_TIMEOUT (atual: 180 segundos).',
            ]);
        }
        $request->session()->put('ai_model_analysis', $analysis);
        return view('admin.tipos.ai-review', ['analysis' => $analysis, 'setores' => Setor::orderBy('nome_setor')->get(), 'clientes' => Cliente::active()->orderBy('cliente_name')->get(), 'servidores' => $this->availableServidores()]);
    }

    public function createFromAi(Request $request)
    {
        $analysis = $request->session()->get('ai_model_analysis');
        abort_unless(is_array($analysis), 422, 'A analise expirou. Envie o arquivo novamente.');
        $data = $request->validate(['id_setor' => 'required|integer', 'id_cliente' => 'nullable|integer', 'id_db' => 'nullable|integer', 'tipo_arq' => ['required', Rule::in(['pdf', 'word', 'pdf,word'])]]);
        if ($request->user()->nivel_usu === 'GER') {
            abort_unless((int) $data['id_setor'] === (int) $request->user()->id_setor, 403);
            if (!empty($data['id_cliente'])) {
                abort_unless(in_array((string) $data['id_cliente'], array_map('strval', $request->user()->client_ids), true), 403);
            }
        }
        $modelo = DB::transaction(function () use ($analysis, $data) {
            $modelo = PeticaoModelo::create(['nome' => $analysis['nome'], 'slug' => $this->buildSlug($analysis['nome']), 'status' => 'ativo', 'arquivo_padrao' => $data['tipo_arq'], 'legacy_setor_id' => $data['id_setor'], 'legacy_cliente_id' => $data['id_cliente'] ?: null, 'legacy_sql_config_id' => $data['id_db'] ?: null, 'cabecalho_html' => null, 'rodape_html' => null, 'metadata' => ['nome_pre' => $analysis['descricao'], 'ai_generated' => true]]);
            $tokenMap = [];
        foreach ($analysis['campos'] as $field) {
            $campo = $modelo->campos()->create(['rotulo' => $field['rotulo'], 'token' => '@ai_' . Str::random(32) . '@', 'tipo' => $field['tipo'], 'comportamento' => $field['comportamento'], 'origem_coluna' => $field['origem_coluna'], 'prefixo' => $field['prefixo'], 'sufixo' => $field['sufixo'], 'ordem' => $field['ordem'], 'obrigatorio' => $field['obrigatorio'], 'visivel' => true, 'colunas_layout' => 1, 'eventos_frontend' => $this->frontendEventsForBehavior($field['comportamento'])]);
            $campo->token = $campo->placeholder;
            $campo->save();
            $tokenMap[(string) $field['token']] = $campo->token;
            foreach ($field['opcoes'] as $order => $option) $campo->opcoes()->create(['rotulo' => $option, 'valor_retorno' => $option, 'ordem' => $order + 1]);
        }
            $replace = function ($value) use ($tokenMap) { return str_ireplace(array_keys($tokenMap), array_values($tokenMap), (string) $value); };
            $modelo->update(['cabecalho_html' => $replace($analysis['cabecalho_html']), 'rodape_html' => $replace($analysis['rodape_html'])]);
            foreach ($analysis['paragrafos'] as $paragraph) $modelo->paragrafos()->create(['titulo' => mb_strtoupper($paragraph['titulo'], 'UTF-8'), 'conteudo_html' => $replace($paragraph['conteudo_html']), 'ordem' => $paragraph['ordem'], 'visivel' => true, 'ativo' => true]);
            return $modelo;
        });
        $request->session()->forget('ai_model_analysis');
        return redirect()->route('admin.modelos-normalizados.edit', $modelo)->with('status', 'Modelo criado a partir da analise IA. Revise os campos e paragrafos.');
    }

    protected function frontendEventsForBehavior($behavior)
    {
        $events = ['focus' => null, 'load' => null, 'blur' => null];
        if ($behavior === 'date') $events['blur'] = 'data_atual(this);';
        if ($behavior === 'decimal') $events['blur'] = 'fc_newstring(this);';
        return $events;
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
            'listaGrupos' => ListaGrupo::orderBy('nome_grupo')->get(),
        ]);
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado)
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

        return $data;
    }

    protected function prepareForEditor(PeticaoModelo $modelo)
    {
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
        return SqlServerProfile::active()->orderBy('nome')->get();
    }
}
