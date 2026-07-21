<?php

namespace App\Http\Controllers;

use App\Peca;
use App\Services\PecaStorageService;
use App\Tipo;
use Illuminate\Http\Request;

class PeticaoEditorController extends Controller
{
    public function create(Request $request, Tipo $modelo)
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
        $peca->load('tipo');

        return view('peticao.editor', [
            'modelo' => $peca->tipo,
            'peca' => $peca,
            'content' => $peca->cod_pecas,
            'nomeCli' => $peca->nome_cli,
        ]);
    }

    public function save(Request $request, Tipo $modelo, PecaStorageService $storage)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
            'peca_id' => 'nullable|integer',
        ]);

        $peca = null;
        if (!empty($data['peca_id'])) {
            $peca = Peca::findOrFail($data['peca_id']);
        }

        $peca = $storage->save($modelo, $data, $peca);

        return redirect()->route('peticoes.editor.edit', $peca)->with('status', 'Peca salva.');
    }

    public function exportWord(Request $request, Tipo $modelo)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $filename = $this->sanitizeFileName($data['nome_cli']) . '.doc';

        return response($data['cod_pecas'], 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request, Tipo $modelo)
    {
        $data = $request->validate([
            'nome_cli' => 'required|string|max:500',
            'cod_pecas' => 'required|string',
        ]);

        $library = base_path('..\\html2pdf\\html2pdf.class.php');
        if (!file_exists($library)) {
            abort(500, 'Biblioteca de PDF nao encontrada.');
        }

        require_once $library;

        $content = '<style>p{margin:0;line-height:115%;font-size:11pt;} .titulos{text-align:center;border:solid 1px #000;font-weight:bold;}</style>'
            . '<page backtop="20mm" backbottom="15mm" backleft="20mm" backright="15mm">'
            . $data['cod_pecas']
            . '</page>';

        if (ob_get_length()) {
            ob_end_clean();
        }

        $pdf = new \HTML2PDF('P', 'A4', 'pt');
        $pdf->setDefaultFont('arial');
        $pdf->writeHTML($content);

        return response($pdf->Output($this->sanitizeFileName($data['nome_cli']) . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFileName($data['nome_cli']) . '.pdf"',
        ]);
    }

    protected function sanitizeFileName($value)
    {
        $value = preg_replace('/[^\pL\pN_\-]+/u', '_', $value);
        $value = trim($value, '_');

        return $value !== '' ? $value : 'peticao';
    }
}
