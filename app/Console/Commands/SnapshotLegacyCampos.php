<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Grava em database/data/legacy-campos.json o input_req e o input_alt de cada
 * campo do sistema antigo (tp_inputs_tb), por id_input.
 *
 * O arquivo vai no repositorio e e lido pela migration que corrige o
 * obrigatorio e as mascaras: assim a producao nao precisa enxergar o banco
 * antigo. Rode na maquina que enxerga o peticaofacil_old.
 */
class SnapshotLegacyCampos extends Command
{
    const DEFAULT_PATH = 'database/data/legacy-campos.json';

    protected $signature = 'peticao:legacy-campos-snapshot {--path= : Arquivo de saida}';

    protected $description = 'Grava num JSON o obrigatorio (input_req) e a mascara (input_alt) dos campos do sistema antigo.';

    public function handle()
    {
        try {
            $rows = DB::connection('legacy_mysql')->table('tp_inputs_tb')
                ->orderBy('id_input')
                ->get(['id_input', 'input_req', 'input_alt']);
        } catch (\Throwable $e) {
            $this->error('Nao foi possivel ler o banco antigo (conexao legacy_mysql / peticaofacil_old).');
            $this->line('Detalhe: ' . $e->getMessage());
            return 1;
        }

        $fields = [];
        foreach ($rows as $row) {
            $field = ['req' => (int) $row->input_req];
            $alt = trim((string) $row->input_alt);
            if ($alt !== '') {
                $field['alt'] = $alt;
            }
            $fields[(string) $row->id_input] = $field;
        }

        $path = base_path($this->option('path') ?: self::DEFAULT_PATH);
        file_put_contents($path, json_encode([
            'source' => 'peticaofacil_old.tp_inputs_tb',
            'generated_at' => now()->toDateTimeString(),
            'fields' => $fields,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        $this->info(count($fields) . ' campos gravados em ' . $path);
        return 0;
    }
}
