<?php

namespace App\Http\Controllers;

use App\Services\SqlServerLookupService;
use App\Services\PeticaoComposerService;
use App\Tipo;
use Illuminate\Http\Request;

class PeticaoAssemblyController extends Controller
{
    public function index()
    {
        $modelos = Tipo::with(['setor', 'cliente'])
            ->where('tipo_stt', 'Y')
            ->orderBy('tipo_nome')
            ->paginate(20);

        return view('peticao.index', compact('modelos'));
    }

    public function show(Tipo $modelo)
    {
        $modelo->load(['campos.dados', 'paragrafos', 'setor', 'cliente', 'servidor']);

        return view('peticao.assemble', [
            'modelo' => $modelo,
            'preview' => null,
            'values' => [],
            'codigoProcesso' => '',
            'lookupStatus' => null,
        ]);
    }

    public function compose(Request $request, Tipo $modelo, PeticaoComposerService $composer, SqlServerLookupService $lookup)
    {
        $modelo->load(['campos.dados', 'paragrafos', 'setor', 'cliente', 'servidor']);

        $values = [];
        foreach ($modelo->campos as $campo) {
            $values['campo_' . $campo->id_input] = $request->input('campo_' . $campo->id_input);
        }

        $codigoProcesso = trim((string) $request->input('codigo_processo', ''));
        $lookupStatus = null;

        if ($codigoProcesso !== '' && $modelo->servidor) {
            $externalData = $lookup->fetchByCode($modelo->servidor, $codigoProcesso);

            if (is_array($externalData)) {
                foreach ($modelo->campos as $campo) {
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
            $preview = $composer->compose($modelo, $values);
        }

        return view('peticao.assemble', [
            'modelo' => $modelo,
            'preview' => $preview,
            'values' => $values,
            'codigoProcesso' => $codigoProcesso,
            'lookupStatus' => $lookupStatus,
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
}
