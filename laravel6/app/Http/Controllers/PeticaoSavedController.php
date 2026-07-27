<?php

namespace App\Http\Controllers;

use App\PeticaoNormalizada;
use App\PeticaoModelo;
use App\PeticaoVersao;
use App\Services\PeticaoExportService;
use App\Services\PeticaoNormalizedDraftService;
use App\Services\PeticaoNormalizedStorageService;
use App\Services\PeticaoVersionAuditService;
use Illuminate\Http\Request;

class PeticaoSavedController extends Controller
{
    public function storeFromPreview(Request $request, \App\Tipo $modelo, PeticaoNormalizedDraftService $draftService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'content' => 'required|string',
            'resolved_fields' => 'nullable|string',
        ]);

        $mirrorModelo = PeticaoModelo::where('legacy_tipo_id', $modelo->tipo_id)->firstOrFail();

        $peticao = $draftService->createFromPreview($mirrorModelo, [
            'nome_cli' => $data['nome_cli'],
            'content' => $data['content'],
            'resolved_fields' => $data['resolved_fields'] ? json_decode($data['resolved_fields'], true) : null,
        ]);

        return redirect()->route('peticoes.saved.edit', $peticao);
    }

    public function storeFromNormalizedPreview(Request $request, PeticaoModelo $modeloNormalizado, PeticaoNormalizedDraftService $draftService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'content' => 'required|string',
            'resolved_fields' => 'nullable|string',
        ]);

        $peticao = $draftService->createFromPreview($modeloNormalizado, [
            'nome_cli' => $data['nome_cli'],
            'content' => $data['content'],
            'resolved_fields' => $data['resolved_fields'] ? json_decode($data['resolved_fields'], true) : null,
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

    public function exportWord(Request $request, PeticaoNormalizada $peticao, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportWord($data['nome_cli'], $data['cod_pecas']);
    }

    public function exportPdf(Request $request, PeticaoNormalizada $peticao, PeticaoExportService $exportService)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        return $exportService->exportPdf($request, $data['nome_cli'], $data['cod_pecas']);
    }

    public function exportVersionWord(PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoExportService $exportService)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        return $exportService->exportWord(
            $versao->cliente_referencia_snapshot ?: ('peticao_versao_' . $versao->versao_numero),
            $versao->conteudo_html_snapshot
        );
    }

    public function exportVersionPdf(Request $request, PeticaoNormalizada $peticao, PeticaoVersao $versao, PeticaoExportService $exportService)
    {
        abort_unless((int) $versao->peticao_id === (int) $peticao->id, 404);

        return $exportService->exportPdf(
            $request,
            $versao->cliente_referencia_snapshot ?: ('peticao_versao_' . $versao->versao_numero),
            $versao->conteudo_html_snapshot
        );
    }
}
