<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\InputCampo;
use App\InputDado;
use App\Services\LegacyModeloSyncService;
use App\Tipo;
use Illuminate\Http\Request;

class LegacyInputCampoFallbackController extends Controller
{
    public function store(Request $request, Tipo $modelo, LegacyModeloSyncService $syncService)
    {
        $data = $this->validateData($request);
        $campo = new InputCampo();
        $this->fillCampo($campo, $modelo, $data);
        $campo->save();
        $this->syncDados($campo, $data['opcoes'] ?? '');
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos-normalizados.edit', $mirror)->with('status', 'Campo criado.');
    }

    public function update(Request $request, Tipo $modelo, InputCampo $campo, LegacyModeloSyncService $syncService)
    {
        $data = $this->validateData($request);
        $this->fillCampo($campo, $modelo, $data);
        $campo->save();
        $this->syncDados($campo, $data['opcoes'] ?? '');
        $mirror = $syncService->syncTipo($modelo->fresh(['paragrafos', 'campos.dados']));

        return redirect()->route('admin.modelos-normalizados.edit', $mirror)->with('status', 'Campo atualizado.');
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

    protected function fillCampo(InputCampo $campo, Tipo $modelo, array $data)
    {
        $campo->fill($data);
        $campo->tipo_id = $modelo->tipo_id;
        $campo->listsel = 'N';
        $campo->input_order = $data['input_order'] ?: (((int) InputCampo::where('tipo_id', $modelo->tipo_id)->where('listsel', 'N')->max('input_order')) + 1);
        $campo->input_width = $data['input_width'] ?: $this->resolveWidth((int) $data['input_cols']);
        $campo->input_req = $data['input_req'] ?? 0;
        $campo->input_rols = $data['input_rols'] ?? 0;
        $campo->nomepet = $data['nomepet'] ?? 'N';
        $campo->hide = $data['hide'] ?? 'true';
    }

    protected function syncDados(InputCampo $campo, $opcoes)
    {
        InputDado::where('id_input', $campo->id_input)->where('listsel', 'N')->delete();

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
            InputDado::create([
                'id_input' => $campo->id_input,
                'nome_dados' => trim($parts[0]),
                'return_1' => trim($parts[1] ?? $parts[0]),
                'data_cad' => now(),
                'dados_order' => $order,
                'id_setor' => 1,
                'listsel' => 'N',
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
