<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
class ModeloTransferController extends Controller
{
    public function export(PeticaoModelo $modeloNormalizado)
    {
        $modeloNormalizado->load(['campos.opcoes','paragrafos','cliente','setor','servidor']); $fields=[];
        foreach ($modeloNormalizado->campos as $field) { $row=$field->toArray(); $row['key']=Str::slug($field->rotulo ?: ('campo_'.$field->id),'_') ?: ('campo_'.$field->id); $row['options']=$field->opcoes->map(function($o){ return $o->makeHidden(['id','campo_id'])->toArray(); })->values()->all(); unset($row['id'],$row['modelo_id'],$row['legacy_input_id'],$row['token'],$row['created_at'],$row['updated_at']); $fields[]=$row; }
        $package=['version'=>1,'dependencies'=>['client'=>optional($modeloNormalizado->cliente)->cliente_name,'sector_code'=>optional($modeloNormalizado->setor)->cod_setor,'server'=>optional($modeloNormalizado->servidor)->nome],'model'=>$modeloNormalizado->only(['nome','slug','status','arquivo_padrao','cabecalho_html','rodape_html','metadata']),'fields'=>$fields,'fields_by_key'=>collect($fields)->keyBy('key')->all(),'paragraphs'=>$modeloNormalizado->paragrafos->map(function($p){ return $p->makeHidden(['id','modelo_id','legacy_fund_id','created_at','updated_at'])->toArray(); })->values()->all()];
        return response()->streamDownload(function() use($package){echo json_encode($package,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);},Str::slug($modeloNormalizado->nome).'.json',['Content-Type'=>'application/json']);
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
        if ((int) $exitCode !== 0) {
            return back()->withErrors(['pacote' => trim(Artisan::output()) ?: 'O pacote nao pode ser importado.'])->withInput();
        }
        return redirect()->route('admin.modelos-normalizados.index')->with('status', 'Modelo importado e ativado com sucesso.');
    }
}

