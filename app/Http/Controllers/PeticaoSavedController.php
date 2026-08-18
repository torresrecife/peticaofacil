<?php

namespace App\Http\Controllers;

use App\PeticaoNormalizada;
use App\PeticaoModelo;
use App\PeticaoVersao;
use App\Services\PeticaoDocumentLayoutService;
use App\Services\PeticaoExportService;
use App\Services\PeticaoSavedAiReviewService;
use App\Services\PeticaoNormalizedDraftService;
use App\Services\PeticaoNormalizedStorageService;
use App\Services\PeticaoSavedReviewService;
use App\Services\PeticaoVersionAuditService;
use App\Services\WordImportService;
use Illuminate\Http\Request;
use RuntimeException;

class PeticaoSavedController extends Controller
{
    public function storeFromNormalizedPreview(Request $request, PeticaoModelo $modeloNormalizado, PeticaoNormalizedDraftService $draftService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'content' => 'required|string',
            'resolved_fields' => 'nullable|string',
            'codigo_processo' => 'nullable|string|max:255',
        ]);

        $peticao = $draftService->createFromPreview($modeloNormalizado, [
            'nome_cli' => $data['nome_cli'],
            'content' => $data['content'],
            'resolved_fields' => !empty($data['resolved_fields']) ? json_decode($data['resolved_fields'], true) : null,
            'codigo_processo' => $data['codigo_processo'] ?? null,
        ]);

        return redirect()->route('peticoes.saved.edit', $peticao);
    }

    public function edit(PeticaoNormalizada $peticao)
    {
        $origin = request()->query('origin');
        $userId = request()->query('user_id');
        $dateFrom = request()->query('date_from');
        $dateTo = request()->query('date_to');

        $peticao->load(['modelo', 'user']);

        $versionsQuery = $peticao->versoes()->with('user');
        if ($origin) {
            $versionsQuery->where('origem_snapshot', $origin);
        }
        if ($userId) {
            $versionsQuery->where('user_id_snapshot', $userId);
        }
        if ($dateFrom) {
            $versionsQuery->whereDate('criado_em', '>=', $dateFrom);
        }
        if ($dateTo) {
            $versionsQuery->whereDate('criado_em', '<=', $dateTo);
        }

        $versoes = $versionsQuery->paginate(10)->appends(request()->query());
        $usuariosHistorico = $peticao->versoes()
            ->with('user')
            ->whereNotNull('user_id_snapshot')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('login_usu')
            ->values();

        return view('peticao.saved-editor', [
            'peticao' => $peticao,
            'versoes' => $versoes,
            'selectedOrigin' => $origin,
            'selectedUserId' => $userId,
            'selectedDateFrom' => $dateFrom,
            'selectedDateTo' => $dateTo,
            'usuariosHistorico' => $usuariosHistorico,
        ]);
    }

    public function update(Request $request, PeticaoNormalizada $peticao, PeticaoNormalizedStorageService $storage)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $peticao = $storage->save($peticao, $data);

        return redirect()->route('peticoes.saved.edit', $peticao)->with('status', 'Peticao salva.');
    }

    public function restoreVersion(PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoNormalizedStorageService $storage)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        $peticao = $storage->restoreVersion($peticao, $versao);

        return redirect()->route('peticoes.saved.edit', $peticao)->with('status', 'Versao restaurada.');
    }

    public function compareVersions(Request $request, PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoVersionAuditService $auditService)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        $targetVersion = null;
        $targetId = $request->query('target_version');
        if ($targetId) {
            $targetVersion = PeticaoVersao::where('peticao_id', $peticao->id)->findOrFail($targetId);
        }

        $peticao->load(['modelo', 'versoes']);
        $comparison = $auditService->compare($peticao, $versao, $targetVersion);

        return view('peticao.version-compare', [
            'peticao' => $peticao,
            'comparison' => $comparison,
        ]);
    }

    public function exportWord(Request $request, PeticaoNormalizada $peticao, PeticaoExportService $exportService, PeticaoDocumentLayoutService $layoutService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $peticao->load('modelo');
        $layout = $layoutService->fromSavedPeticao($peticao);
        $layout['title'] = $data['nome_cli'];
        $layout['body_html'] = $data['cod_pecas'];

        return $exportService->exportWordFromLayout($layout);
    }

    public function exportPdf(Request $request, PeticaoNormalizada $peticao, PeticaoExportService $exportService, PeticaoDocumentLayoutService $layoutService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $peticao->load('modelo');
        $layout = $layoutService->fromSavedPeticao($peticao);
        $layout['title'] = $data['nome_cli'];
        $layout['body_html'] = $data['cod_pecas'];

        return $exportService->exportPdfFromLayout($request, $layout);
    }

    public function importWord(Request $request, PeticaoNormalizada $peticao, WordImportService $wordImportService)
    {
        $data = $request->validate([
            'word_file' => 'required|file|mimes:doc,docx|max:25600',
        ]);

        try {
            $html = $wordImportService->importUploadedFile($data['word_file']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'html' => $html,
        ]);
    }

    public function review(Request $request, PeticaoNormalizada $peticao, PeticaoSavedReviewService $reviewService)
    {
        $data = $request->validate([
            'cod_pecas' => 'required|string',
            'plain_text' => 'nullable|string',
        ]);

        return response()->json(
            $reviewService->review($data['cod_pecas'], $data['plain_text'] ?? null, $request->user())
        );
    }

    public function storeLanguageToolPreference(Request $request, PeticaoNormalizada $peticao, PeticaoSavedReviewService $reviewService)
    {
        $data = $request->validate([
            'entry_type' => 'required|string|in:ignored_match,dictionary_word',
            'token' => 'required|string|max:255',
            'rule_id' => 'nullable|string|max:255',
        ]);

        try {
            $reviewService->storePreference($request->user(), $data);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Preferencia do LanguageTool salva.',
        ]);
    }

    public function reviewWithAi(Request $request, PeticaoNormalizada $peticao, PeticaoSavedAiReviewService $reviewService)
    {
        $data = $request->validate([
            'cod_pecas' => 'required|string',
            'plain_text' => 'nullable|string',
        ]);

        return response()->json(
            $reviewService->review($data['cod_pecas'], $data['plain_text'] ?? null)
        );
    }

    public function print(PeticaoNormalizada $peticao, PeticaoExportService $exportService, PeticaoDocumentLayoutService $layoutService)
    {
        return $exportService->renderPrintViewFromLayout($layoutService->fromSavedPeticao($peticao));
    }

    public function exportVersionWord(PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoExportService $exportService, PeticaoDocumentLayoutService $layoutService)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        return $exportService->exportWordFromLayout($layoutService->fromVersion($peticao, $versao));
    }

    public function exportVersionPdf(Request $request, PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoExportService $exportService, PeticaoDocumentLayoutService $layoutService)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        return $exportService->exportPdfFromLayout($request, $layoutService->fromVersion($peticao, $versao));
    }

    public function printVersion(PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoExportService $exportService, PeticaoDocumentLayoutService $layoutService)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        return $exportService->renderPrintViewFromLayout($layoutService->fromVersion($peticao, $versao));
    }
}
