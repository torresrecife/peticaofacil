<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tipo = \Illuminate\Support\Facades\DB::table('tp_tipo_tb_archive_20260727')->where('tipo_id', 48)->first();
echo "TIPO\n";
var_export($tipo);
echo "\n\nCAMPOS\n";

$campos = \Illuminate\Support\Facades\DB::table('tp_inputs_tb_archive_20260727')
    ->where('tipo_id', 48)
    ->orderBy('input_order')
    ->get();

foreach ($campos as $campo) {
    if (stripos((string) $campo->input_title, 'AUTOR') === false
        && stripos((string) $campo->input_title, 'NOME DO AUTOR') === false) {
        continue;
    }

    var_export($campo);
    echo "\n\nDADOS DO CAMPO\n";

    $dados = \Illuminate\Support\Facades\DB::table('tp_dados_tb_archive_20260727')
        ->where('id_input', $campo->id_input)
        ->orderBy('dados_order')
        ->get();

    foreach ($dados as $dado) {
        var_export($dado);
        echo "\n";
    }

    echo "\n";
}

echo "\nGRUPOS CLIENTE\n";
$grupos = \Illuminate\Support\Facades\DB::table('tp_grupo_tb_archive_20260727')
    ->where('nome_grupo', 'like', '%CLIENTE%')
    ->orderBy('id_grupo')
    ->get();

foreach ($grupos as $grupo) {
    var_export($grupo);
    echo "\n";
}
