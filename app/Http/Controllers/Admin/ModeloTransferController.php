<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use App\Services\PeticaoModeloPackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
class ModeloTransferController extends Controller
{
    public function export(PeticaoModelo $modeloNormalizado, PeticaoModeloPackageService $packages)
    {
        abort_if($modeloNormalizado->is_system, 404);

        $package = $packages->export($modeloNormalizado);

        return response()->streamDownload(function () use ($package) {
            echo json_encode($package, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }, Str::slug($modeloNormalizado->nome) . '.json', ['Content-Type' => 'application/json']);
    }

    public function import(Request $request)
    {
        $request->validate(['pacote' => 'required|file|max:5120']);
        $file = $request->file('pacote');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['json', 'txt'], true)) {
            return back()->withErrors(['pacote' => 'O pacote deve ser um arquivo .json ou .txt.'])->withInput();
        }

        $directory = storage_path('app/modelo-pacotes');
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }
        $target = $directory . DIRECTORY_SEPARATOR . 'modelo-' . date('YmdHis') . '.json';
        $file->move($directory, basename($target));
        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $target, '--activate' => true]);
        $output = trim(Artisan::output());
        if ((int) $exitCode !== 0) {
            return back()->withErrors(['pacote' => $output ?: 'O pacote nao pode ser importado.'])->withInput();
        }

        // O comando segue em frente com avisos (cliente ausente, por exemplo); quem importou precisa ve-los.
        preg_match_all('/^\s*Aviso: (.+)$/mu', $output, $warnings);
        $status = 'Modelo importado e ativado com sucesso.';
        if (!empty($warnings[1])) {
            $status .= ' Atenção: ' . implode(' ', array_map('trim', $warnings[1]));
        }

        return redirect()->route('admin.modelos-normalizados.index')->with('status', $status);
    }
}

