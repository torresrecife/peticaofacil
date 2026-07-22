<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\Services\NormalizedModeloLegacySyncService;
use Illuminate\Http\Request;

class NormalizedInputCampoController extends Controller
{
    public function store(Request $request, PeticaoModelo $modeloNormalizado, NormalizedModeloLegacySyncService $syncService)
    {
        $data = $this->validateData($request);

        $campo = PeticaoModeloCampo::create([
            'modelo_id' => $modeloNormalizado->id,
            'legacy_input_id' => null,
            'rotulo' => $data['input_title'],
            'token' => '@campo_novo@',
            'tipo' => $data['input_tipo'],
            'origem_coluna' => $data['input_val'] ?? null,
            'origem_alias' => $data['input_db'] ?? null,
            'prefixo' => $data['input_pre'] ?? null,
            'sufixo' => $data['input_pos'] ?? null,
            'valor_padrao' => $data['texto_padrao'] ?? null,
            'classe_css' => $data['add_class'] ?? null,
            'largura' => $data['input_width'] ?: $this->resolveWidth((int) $data['input_cols']),
            'colunas_layout' => $data['input_cols'] ?: 1,
            'linhas_layout' => $data['input_rols'] ?? 0,
            'ordem' => $data['input_order'] ?: (((int) PeticaoModeloCampo::where('modelo_id', $modeloNormalizado->id)->max('ordem')) + 1),
            'obrigatorio' => (int) ($data['input_req'] ?? 0) === 1,
            'visivel' => ($data['hide'] ?? 'true') !== 'none',
            'gera_nome_arquivo' => ($data['nomepet'] ?? 'N') === 'Y',
            'eventos_frontend' => array_filter([
                'focus' => $data['input_focu'] ?? null,
                'load' => $data['input_load'] ?? null,
                'blur' => $data['input_blur'] ?? null,
            ]),
        ]);
        $campo->token = $campo->placeholder;
        $campo->save();

        $this->syncOpcoes($campo, $data['opcoes'] ?? '');
        $syncService->sync($modeloNormalizado->fresh(['paragrafos', 'campos.opcoes']));

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Campo criado.');
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado, PeticaoModeloCampo $campo, NormalizedModeloLegacySyncService $syncService)
    {
        abort_unless((int) $campo->modelo_id === (int) $modeloNormalizado->id, 404);

        $data = $this->validateData($request);

        $campo->fill([
            'rotulo' => $data['input_title'],
            'tipo' => $data['input_tipo'],
            'origem_coluna' => $data['input_val'] ?? null,
            'origem_alias' => $data['input_db'] ?? null,
            'prefixo' => $data['input_pre'] ?? null,
            'sufixo' => $data['input_pos'] ?? null,
            'valor_padrao' => $data['texto_padrao'] ?? null,
            'classe_css' => $data['add_class'] ?? null,
            'largura' => $data['input_width'] ?: $this->resolveWidth((int) $data['input_cols']),
            'colunas_layout' => $data['input_cols'] ?: 1,
            'linhas_layout' => $data['input_rols'] ?? 0,
            'ordem' => $data['input_order'] ?: $campo->ordem,
            'obrigatorio' => (int) ($data['input_req'] ?? 0) === 1,
            'visivel' => ($data['hide'] ?? 'true') !== 'none',
            'gera_nome_arquivo' => ($data['nomepet'] ?? 'N') === 'Y',
            'eventos_frontend' => array_filter([
                'focus' => $data['input_focu'] ?? null,
                'load' => $data['input_load'] ?? null,
                'blur' => $data['input_blur'] ?? null,
            ]),
        ])->save();

        $this->syncOpcoes($campo, $data['opcoes'] ?? '');
        $syncService->sync($modeloNormalizado->fresh(['paragrafos', 'campos.opcoes']));

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Campo atualizado.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'input_title' => 'required|string|max:500',
            'input_tipo' => 'required|in:TEXT,SELECT,TEXTAREA,HIDDEN,TITLE',
            'input_pre' => 'nullable|string',
            'input_pos' => 'nullable|string',
            'input_db' => 'nullable|string|max:100',
            'input_val' => 'nullable|string|max:100',
            'input_alt' => 'nullable|string|max:50',
            'input_cols' => 'required|integer|min:1|max:3',
            'input_rols' => 'nullable|integer|min:0',
            'input_focu' => 'nullable|string|max:2000',
            'input_load' => 'nullable|string|max:2000',
            'input_blur' => 'nullable|string|max:2000',
            'input_width' => 'nullable|integer|min:10|max:5000',
            'input_req' => 'nullable|integer|min:0|max:1',
            'input_order' => 'nullable|integer|min:1',
            'nomepet' => 'nullable|in:Y,N',
            'hide' => 'nullable|in:true,none',
            'texto_padrao' => 'nullable|string',
            'add_class' => 'nullable|string|max:500',
            'opcoes' => 'nullable|string',
        ]);
    }

    protected function syncOpcoes(PeticaoModeloCampo $campo, $opcoes)
    {
        PeticaoModeloCampoOpcao::where('campo_id', $campo->id)->delete();

        if ($campo->input_tipo !== 'SELECT') {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $opcoes);
        $order = 1;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode('|', $line, 2);
            PeticaoModeloCampoOpcao::create([
                'campo_id' => $campo->id,
                'legacy_dado_id' => null,
                'rotulo' => trim($parts[0]),
                'valor_retorno' => trim($parts[1] ?? $parts[0]),
                'ordem' => $order,
            ]);

            $order++;
        }
    }

    protected function resolveWidth($cols)
    {
        if ($cols === 2) {
            return 560;
        }
        if ($cols === 3) {
            return 860;
        }

        return 265;
    }
}
