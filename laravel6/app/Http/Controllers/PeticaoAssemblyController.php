<?php

namespace App\Http\Controllers;

use App\PeticaoModelo;
use App\Services\SqlServerLookupService;
use App\Services\PeticaoComposerService;
use App\Tipo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PeticaoAssemblyController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $favoriteNormalizedIds = [];
        $favoriteLegacyTipoIds = [];

        $favoriteCollection = Auth::user()
            ->favoriteModelos()
            ->get();

        $favoriteRows = $favoriteCollection
            ->mapWithKeys(function ($favorite) {
                $key = $favorite->source === 'normalized'
                    ? 'normalized:' . $favorite->modelo_id
                    : 'legacy:' . $favorite->legacy_tipo_id;

                return [$key => true];
            });

        $favoriteNormalizedIds = $favoriteCollection
            ->where('source', 'normalized')
            ->pluck('modelo_id')
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        $favoriteLegacyTipoIds = $favoriteCollection
            ->where('source', 'legacy')
            ->pluck('legacy_tipo_id')
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        $modelosQuery = PeticaoModelo::with(['setor', 'cliente'])
            ->where('status', 'ativo')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('nome', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('legacy_tipo_id', 'like', '%' . $search . '%');
                });
            });

        if (!empty($favoriteNormalizedIds)) {
            $modelosQuery->orderByRaw(
                'CASE WHEN id IN (' . implode(',', $favoriteNormalizedIds) . ') THEN 0 ELSE 1 END'
            );
        }

        $modelos = $modelosQuery
            ->orderBy('nome')
            ->paginate(18, ['*'], 'modelos_page')
            ->appends($request->except('modelos_page'));

        $legacyFallbacksQuery = Tipo::with(['setor', 'cliente'])
            ->where('tipo_stt', 'Y')
            ->whereNotIn('tipo_id', PeticaoModelo::whereNotNull('legacy_tipo_id')->pluck('legacy_tipo_id'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('tipo_nome', 'like', '%' . $search . '%')
                        ->orWhere('tipo_id', 'like', '%' . $search . '%');
                });
            });

        if (!empty($favoriteLegacyTipoIds)) {
            $legacyFallbacksQuery->orderByRaw(
                'CASE WHEN tipo_id IN (' . implode(',', $favoriteLegacyTipoIds) . ') THEN 0 ELSE 1 END'
            );
        }

        $legacyFallbacks = $legacyFallbacksQuery
            ->orderBy('tipo_nome')
            ->paginate(12, ['*'], 'legacy_page')
            ->appends($request->except('legacy_page'));

        $suggestions = collect();

        $normalizedSuggestions = PeticaoModelo::where('status', 'ativo')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('nome', 'like', '%' . $search . '%');
            })
            ->orderBy('nome')
            ->limit(30)
            ->pluck('nome');

        $legacySuggestions = Tipo::where('tipo_stt', 'Y')
            ->whereNotIn('tipo_id', PeticaoModelo::whereNotNull('legacy_tipo_id')->pluck('legacy_tipo_id'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where('tipo_nome', 'like', '%' . $search . '%');
            })
            ->orderBy('tipo_nome')
            ->limit(20)
            ->pluck('tipo_nome');

        $suggestions = $suggestions
            ->merge($normalizedSuggestions)
            ->merge($legacySuggestions)
            ->filter()
            ->unique()
            ->values();

        return view('peticao.index', compact('modelos', 'legacyFallbacks', 'favoriteRows', 'search', 'suggestions'));
    }

    public function showNormalized(PeticaoModelo $modeloNormalizado)
    {
        $modeloNormalizado->load(['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor', 'servidorLegacy']);

        return $this->renderAssemble($modeloNormalizado, $modeloNormalizado);
    }

    public function show(Tipo $modelo)
    {
        $modelo->load(['campos.dados', 'paragrafos', 'setor', 'cliente', 'servidor']);
        $modeloFonte = $this->resolvePreferredModelo($modelo);

        return $this->renderAssemble($modelo, $modeloFonte);
    }

    public function composeNormalized(Request $request, PeticaoModelo $modeloNormalizado, PeticaoComposerService $composer, SqlServerLookupService $lookup)
    {
        $modeloNormalizado->load(['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor', 'servidorLegacy']);

        return $this->renderComposedAssemble($request, $modeloNormalizado, $modeloNormalizado, $composer, $lookup);
    }

    public function compose(Request $request, Tipo $modelo, PeticaoComposerService $composer, SqlServerLookupService $lookup)
    {
        $modelo->load(['campos.dados', 'paragrafos', 'setor', 'cliente', 'servidor']);
        $modeloFonte = $this->resolvePreferredModelo($modelo);

        return $this->renderComposedAssemble($request, $modelo, $modeloFonte, $composer, $lookup);
    }

    protected function renderComposedAssemble(Request $request, $modelo, $modeloFonte, PeticaoComposerService $composer, SqlServerLookupService $lookup)
    {
        $values = [];
        foreach ($modeloFonte->campos as $campo) {
            $values['campo_' . $campo->id_input] = $request->input('campo_' . $campo->id_input);
        }

        $codigoProcesso = trim((string) $request->input('codigo_processo', ''));
        $lookupStatus = null;

        $lookupConfig = $this->resolveLookupConfig($modeloFonte);

        if ($codigoProcesso !== '' && $lookupConfig) {
            $externalData = $lookup->fetchByCode($lookupConfig, $codigoProcesso);

            if (is_array($externalData)) {
                foreach ($modeloFonte->campos as $campo) {
                    $fieldKey = 'campo_' . $campo->id_input;
                    if (($values[$fieldKey] ?? '') !== '') {
                        continue;
                    }

                    $column = trim((string) $campo->input_val);
                    if ($column === '' || !array_key_exists($column, $externalData)) {
                        continue;
                    }

                    $values[$fieldKey] = $this->mapExternalValueToCampo($campo, $externalData[$column]);
                }

                $lookupStatus = 'Dados externos carregados para o processo informado.';
            } else {
                $lookupStatus = 'Nao foi possivel localizar dados externos para o processo informado.';
            }
        }

        $preview = null;
        if ($request->input('action_type', 'preview') === 'preview') {
            $preview = $composer->compose($modeloFonte, $values);
        }

        return $this->renderAssemble($modelo, $modeloFonte, $preview, $values, $codigoProcesso, $lookupStatus);
    }

    protected function renderAssemble($modelo, $modeloFonte, $preview = null, array $values = [], $codigoProcesso = '', $lookupStatus = null)
    {
        $lookupConfig = $this->resolveLookupConfig($modeloFonte);

        return view('peticao.assemble', [
            'modelo' => $modelo,
            'modeloFonte' => $modeloFonte,
            'preview' => $preview,
            'values' => $values,
            'codigoProcesso' => $codigoProcesso,
            'lookupStatus' => $lookupStatus,
            'lookupConfig' => $lookupConfig,
        ]);
    }

    protected function mapExternalValueToCampo($campo, $value)
    {
        if ($value === null) {
            return '';
        }

        $value = is_scalar($value) ? (string) $value : '';

        if ($campo->input_tipo === 'SELECT') {
            foreach ($campo->select_options as $option) {
                if ((string) $option['return'] === $value || (string) $option['label'] === $value) {
                    return $option['label'];
                }
            }
        }

        return $value;
    }

    protected function resolvePreferredModelo(Tipo $modelo)
    {
        $mirror = PeticaoModelo::with(['campos.opcoes', 'paragrafos', 'setor', 'cliente', 'servidor', 'servidorLegacy'])
            ->where('legacy_tipo_id', $modelo->tipo_id)
            ->first();

        return $mirror ?: $modelo;
    }

    protected function resolveLookupConfig($modeloFonte)
    {
        if ($modeloFonte instanceof PeticaoModelo) {
            return $modeloFonte->servidor ?: $modeloFonte->servidorLegacy;
        }

        return $modeloFonte->servidor ?: null;
    }
}
