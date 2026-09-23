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
        $request->validate(['pacote'=>'required|file|mimes:json,txt|max:5120']); $target=database_path('data/stone-execucao-titulo-extrajudicial.json'); $request->file('pacote')->move(dirname($target),basename($target)); Artisan::call('peticao:modelo-stone-import',['--dry-run'=>true]); return redirect()->route('admin.modelos-normalizados.index')->with('status','Pacote importado e validado. Execute o comando de importaÃ§Ã£o para gravar o modelo.');
    }
}

