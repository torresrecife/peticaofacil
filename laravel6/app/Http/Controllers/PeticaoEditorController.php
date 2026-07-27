<?php

namespace App\Http\Controllers;

use App\Peca;
use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\Services\PeticaoExportService;
use App\Services\PeticaoModeloResolverService;
use App\Services\PeticaoNormalizedStorageService;
use App\Tipo;
use Illuminate\Http\Request;

class PeticaoEditorController extends Controller
{
    public function create(Request $request, Tipo $modelo)
    {
        $modeloNormalizado = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($modeloNormalizado) {
            return $this->createNormalized($request, $modeloNormalizado);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function createNormalized(Request $request, PeticaoModelo $modeloNormalizado)
    {
        return $this->renderCreateEditor($request, $modeloNormalizado);
    }

    protected function renderCreateEditor(Request $request, $modelo)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'nome_cli' => 'required|string|max:500',
        ]);

        return view('peticao.editor', [
            'modelo' => $modelo,
            'peca' => null,
            'content' => $data['content'],
            'nomeCli' => $data['nome_cli'],
        ]);
    }

    public function edit(Peca $peca)
    {
        $mirror = PeticaoNormalizada::where('legacy_peca_id', $peca->id_pecas)->first();

        if ($mirror) {
            return redirect()->route('peticoes.saved.edit', $mirror);
        }

        return redirect()
            ->route('pecas.index')
            ->with('status', 'Peca legada sem espelho normalizado. Use a sincronizacao historica antes de editar.');
    }

    public function save(Request $request, Tipo $modelo, PeticaoNormalizedStorageService $normalizedStorage, PeticaoModeloResolverService $modeloResolver)
    {
        $modeloNormalizado = $modeloResolver->findNormalizedForLegacyTipo($modelo);
        if ($modeloNormalizado) {
            return $this->saveNormalized($request, $modeloNormalizado, $normalizedStorage);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function saveNormalized(Request $request, PeticaoModelo $modeloNormalizado, PeticaoNormalizedStorageService $storage)
    {
        return $this->handleSaveNormalized($request, $modeloNormalizado, $storage);
    }

    protected function handleSaveNormalized(Request $request, PeticaoModelo $modelo, PeticaoNormalizedStorageService $storage)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
            'peca_id' => 'nullable|integer',
        ]);

        $peticao = null;
        if (!empty($data['peca_id'])) {
            $legacyPeca = Peca::findOrFail($data['peca_id']);
            $peticao = PeticaoNormalizada::firstOrNew(['legacy_peca_id' => $legacyPeca->id_pecas]);

            if (!$peticao->exists) {
                $peticao->fill([
                    'modelo_id' => $modelo->id,
                    'user_id' => auth()->id(),
                    'legacy_usuario_id' => $legacyPeca->id_usu,
                    'codigo_externo' => $legacyPeca->cod_sav,
                    'nome_arquivo' => $legacyPeca->nome_pecas ?: $modelo->nome,
                    'cliente_referencia' => $legacyPeca->nome_cli,
                    'conteudo_html' => $legacyPeca->cod_pecas,
                    'campos_resolvidos' => [
                        'legacy_tipo_id' => $modelo->legacy_tipo_id,
                        'legacy_cod_sav' => $legacyPeca->cod_sav,
                    ],
                    'gerado_em' => $legacyPeca->data_cad ?: now(),
                    'salvo_em' => now(),
                ]);
            }
        }

        if (!$peticao) {
            $peticao = new PeticaoNormalizada([
                'modelo_id' => $modelo->id,
                'user_id' => auth()->id(),
                'legacy_usuario_id' => optional(auth()->user())->legacy_usuario_id ?: optional(auth()->user())->id_usu,
                'codigo_externo' => null,
                'nome_arquivo' => $modelo->nome,
                'cliente_referencia' => $data['nome_cli'],
                'conteudo_html' => $data['cod_pecas'],
                'campos_resolvidos' => [
                    'legacy_tipo_id' => $modelo->legacy_tipo_id,
                ],
                'gerado_em' => now(),
                'salvo_em' => now(),
            ]);
        }

        $peticao = $storage->save($peticao, $data);

        return redirect()->route('peticoes.saved.edit', $peticao)->with('status', 'Peca salva.');
    }

    public function exportWord(Request $request, Tipo $modelo, PeticaoExportService $exportService)
    {
        $modeloNormalizado = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($modeloNormalizado) {
            return $this->exportNormalizedWord($request, $modeloNormalizado, $exportService);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function exportNormalizedWord(Request $request, PeticaoModelo $modeloNormalizado, PeticaoExportService $exportService)
    {
        return $this->handleExportWord($request, $exportService);
    }

    protected function handleExportWord(Request $request, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportWord($data['nome_cli'], $data['cod_pecas']);
    }

    public function exportPdf(Request $request, Tipo $modelo, PeticaoExportService $exportService)
    {
        $modeloNormalizado = app(PeticaoModeloResolverService::class)->findNormalizedForLegacyTipo($modelo);
        if ($modeloNormalizado) {
            return $this->exportNormalizedPdf($request, $modeloNormalizado, $exportService);
        }

        return $this->redirectLegacyModelWithoutMirror();
    }

    public function exportNormalizedPdf(Request $request, PeticaoModelo $modeloNormalizado, PeticaoExportService $exportService)
    {
        return $this->handleExportPdf($request, $exportService);
    }

    protected function handleExportPdf(Request $request, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportPdf($request, $data['nome_cli'], $data['cod_pecas']);
    }

    protected function redirectLegacyModelWithoutMirror()
    {
        return redirect()
            ->route('peticoes.index')
            ->with('status', 'Modelo legado sem mirror normalizado. Use a sincronizacao administrativa antes da montagem.');
    }
}
