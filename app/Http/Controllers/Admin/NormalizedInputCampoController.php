<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\Services\LegacyModeloMirrorService;
use Illuminate\Http\Request;

class NormalizedInputCampoController extends Controller
{
    public function store(Request $request, PeticaoModelo $modeloNormalizado, LegacyModeloMirrorService $mirrorService)
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
            'eventos_frontend' => [],
        ]);

        $campo->origem_alias = $this->resolveOrigemAlias($campo, $data);
        $campo->eventos_frontend = $this->buildFrontendEvents($campo, $data);
        $campo->token = $campo->placeholder;
        $campo->save();

        $this->syncOpcoes($campo, $data['opcoes'] ?? '', $data);
        $mirrorService->syncIfEnabled($modeloNormalizado->fresh(['paragrafos', 'campos.opcoes']));

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)->with('status', 'Campo criado.');
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado, PeticaoModeloCampo $campo, LegacyModeloMirrorService $mirrorService)
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
            'eventos_frontend' => [],
        ])->save();

        $campo->origem_alias = $this->resolveOrigemAlias($campo, $data);
        $campo->eventos_frontend = $this->buildFrontendEvents($campo, $data);
        $campo->save();

        $this->syncOpcoes($campo, $data['opcoes'] ?? '', $data);
        $mirrorService->syncIfEnabled($modeloNormalizado->fresh(['paragrafos', 'campos.opcoes']));

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
            'input_list_group_id' => 'nullable|integer|min:1',
            'input_list_return_column' => 'nullable|in:return_1,return_2,return_3,return_4,return_5,return_6',
            'input_list_target_field' => 'nullable|integer|min:1',
        ]);
    }

    protected function syncOpcoes(PeticaoModeloCampo $campo, $opcoes, array $data = [])
    {
        PeticaoModeloCampoOpcao::where('campo_id', $campo->id)->delete();

        if ($campo->input_tipo !== 'SELECT' || !empty($data['input_list_group_id'])) {
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

    protected function resolveOrigemAlias(PeticaoModeloCampo $campo, array $data)
    {
        if (
            $campo->input_tipo === 'SELECT'
            && !empty($data['input_list_group_id'])
            && !empty($data['input_list_return_column'])
        ) {
            return sprintf(
                'tp_lista_tb_|_nome_lista_|_%s_|_id_grupo=%d_|_vert',
                $data['input_list_return_column'],
                (int) $data['input_list_group_id']
            );
        }

        return $data['input_db'] ?? null;
    }

    protected function buildFrontendEvents(PeticaoModeloCampo $campo, array $data)
    {
        $focus = trim((string) ($data['input_focu'] ?? ''));
        $load = trim((string) ($data['input_load'] ?? ''));
        $blur = trim((string) ($data['input_blur'] ?? ''));

        if (
            $campo->input_tipo === 'SELECT'
            && !empty($data['input_list_target_field'])
            && !empty($data['input_list_return_column'])
        ) {
            $generated = sprintf(
                'fc_ajax_comp("tp_lista_tb","%s","campo%d","unir","id_lista",this,1); mcampo("campo%d_|_campo%d"); $("#campo%d").focus();',
                $data['input_list_return_column'],
                (int) $data['input_list_target_field'],
                (int) $campo->id_input,
                (int) $data['input_list_target_field'],
                (int) $data['input_list_target_field']
            );

            if ($focus === '') {
                $focus = $generated;
            }
            if ($load === '') {
                $load = $generated;
            }
            if ($blur === '') {
                $blur = $generated;
            }
        }

        return array_filter([
            'focus' => $focus !== '' ? $focus : null,
            'load' => $load !== '' ? $load : null,
            'blur' => $blur !== '' ? $blur : null,
        ]);
    }
}
