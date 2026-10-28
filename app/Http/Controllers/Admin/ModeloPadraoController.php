<?php

namespace App\Http\Controllers\Admin;

use App\Configuracao;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Cabecalho e rodape que todo modelo novo recebe automaticamente.
 * Modelos que ja existem nao mudam.
 */
class ModeloPadraoController extends Controller
{
    public function edit()
    {
        return view('admin.tipos.padrao', [
            'cabecalho' => Configuracao::cabecalhoModelo(),
            'rodape' => Configuracao::rodapeModelo(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'cod_cabec' => 'nullable|string',
            'cod_rodap' => 'nullable|string',
        ]);

        Configuracao::gravar(Configuracao::CABECALHO_MODELO, $data['cod_cabec'] ?? null);
        Configuracao::gravar(Configuracao::RODAPE_MODELO, $data['cod_rodap'] ?? null);

        return redirect()
            ->route('admin.modelos-normalizados.padrao.edit')
            ->with('status', 'Cabecalho e rodape padrao salvos. Valem para os proximos modelos criados.');
    }
}
